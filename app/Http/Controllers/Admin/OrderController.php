<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Exports\AdminOrderExport;
use Illuminate\Http\Request;
use App\OrderProductOption;
use App\OrderProduct;
use Carbon\Carbon;
use App\PdfHelper;
use App\Product;
use App\System;
use App\Helper;
use ZipArchive;
use App\Order;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Services\DeliveryOrderSyncService;
use App\Services\InvoiceSyncService;
use App\SqlSyncRecord;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_admin');
    }

    public function index(Request $request)
    {
        // Request Parameters
        $order_id = $request->input('id');
        $user_id = $request->input('customer');
        $fdate = $request->input('fdate');
        $tdate = $request->input('tdate');
        $price_range = $request->input('price_range');
        $status = $request->input('status');
        $orderby = $request->input('orderby');

        $orders = Order::leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->select(
                "users.id as customer_id",
                "users.name as customer_name",
                "orders.id",
                "orders.status",
                "orders.billing_address",
                "orders.shipping_address",
                "orders.do_no",
                "orders.delivering_date",
                "orders.created_at",
                "orders.updated_at",
                "orders.sql_sync_status",
                "orders.sql_sync_respond",
                DB::raw("COALESCE(
                    (SELECT GROUP_CONCAT(
                        CONCAT(
                            product_name,
                            ' (Qty: ', COALESCE(quantity, 0),
                            ', Wt: ', COALESCE(weight, 0), ')'
                        )
                        SEPARATOR '<br>'
                    )
                    FROM order_products
                    WHERE order_products.order_id = orders.id 
                    AND order_products.status = 'active'
                    ), 'No products'
                ) as products")
            )
            ->when($order_id != null, function ($q) use ($order_id) {
                return $q->where('orders.id', $order_id);
            })
            ->when($user_id != null, function ($q) use ($user_id) {
                return $q->where('orders.user_id', $user_id);
            })
            ->when($fdate != null, function ($q) use ($fdate) {
                return $q->where('orders.created_at', '>=', $fdate);
            })
            ->when($tdate != null, function ($q) use ($tdate) {
                return $q->where('orders.created_at', '<=', $tdate . " 23:59:59");
            })
            ->when($price_range != null, function ($q) use ($price_range) {
                $filter_price_range = explode(',', $price_range);
                $from_price = $filter_price_range[0];
                $to_price = $filter_price_range[1];

                return $q->where('orders.total_price', '>=', $from_price)->where('orders.total_price', '<=', $to_price);
            })
            ->when($status != null, function ($q) use ($status) {
                return $q->where('orders.status', $status);
            })
            ->when($orderby != null, function ($q) use ($orderby) {
                if (in_array($orderby, ['asc', 'desc'])) {
                    return $q->orderby('orders.created_at', $orderby);
                } else if (in_array($orderby, ['do_no_asc', 'do_no_desc'])) {
                    return $q->orderby('do_no', $orderby === 'do_no_asc' ? 'desc' : 'asc');
                }
            })
            ->orderBy('orders.id', 'desc')
            ->paginate(15);
        $minPrice = Order::min('total_price');
        $maxPrice = Order::max('total_price');
        $role = Auth::guard('web_admin')->user()->role;

        $statuses = [
            // 'pending' => 'Pending',
            'cancelled' => 'Cancelled',
            'processing' => 'Processing',
            // 'delivering' => 'Delivering',
            'completed' => 'Completed',
        ];

        $drivers_arr = [];
        $drivers = DB::table('drivers')->select('id', 'lorry_number')->get()->toArray();
        foreach ($drivers as $driver) {
            $drivers_arr[$driver->id] = $driver->lorry_number;
        }
        
        return view('admin.orders.index', [
                'orders' => $orders,
                'role' => $role,
                'statuses' => $statuses,
                'drivers' => $drivers_arr,
                'input' => $request->all() + ['min_price' => $minPrice, 'max_price' => $maxPrice, 'from_price' => $from_price ?? $minPrice, 'to_price' => $to_price ?? $maxPrice],
                'query_params' => Helper::query_params($request->input()),
                'shipping_state_options' => System::$country_state['MY'],
                'areaList' => Helper::areaList(),
                'customers_list' => DB::table('users')->select('id', 'name')->get()->toArray(),
            ]
        );
    }

    public function create(Request $request)
    {
        // payment_method
        $payment_method_options = User::$payment_method;
        foreach ($payment_method_options as $key => $value) {
            $payment_method_options[$key] = trans('user.payment_method.'.$value);
        }
        
        return view('admin.orders.create', [
                'payment_method_options' => $payment_method_options? : [],
                'shipping_state_options' => System::$country_state['MY'],
                'customers_list' => User::all(),
                'areaList' => Helper::areaList(),
            ]
        );
    }
    
    public function store(Request $request)
    {
        $data = $this->validate_store_order($request);
        if (isset($data['error']) && $data['error']) {
            return redirect()->back()->withInput()->withErrors($data['field_err']);
        }

        $user = User::find($data['customer_id']);
        $total = 0;
        $pricing_date = $request['pricing_date'] ?? date('Y-m-d');
        $delivering_date = $request['delivering_date'] ?? date('Y-m-d', strtotime('+1 day'));

        $order = Order::create(
            [
                "user_id" => $user->id,
                "total_price" => $total,
                "attn_name" => $data['attn_name'],
                "attn_contact" => $data['attn_contact'],
                "payment_method" => $data['payment_method'] ?? null,
                "pricing_date" => $pricing_date,
                "delivering_date" => $delivering_date,
                "area" => $request['area'],
                "billing_address" => $data['billing_address'],
                "billing_city" => $request['billing_city'] ?? null,
                "shipping_city" => $request['shipping_city'],
                "billing_postcode" => $data['billing_postcode'] ?? null,
                "billing_state" => $data['billing_state'] ?? null,
                "shipping_address" => $data['shipping_address'],
                "shipping_postcode" => $data['shipping_postcode'] ?? null,
                "shipping_state" => $data['shipping_state'] ?? null,
                "status" => 'processing',
                "driver_id" => $user->default_driver_id,
            ]
        );

        $image = null;
        if (isset($data['transfer_slip']) && $data['transfer_slip']) {
            do {
                $extension = $data['transfer_slip']->getClientOriginalExtension();
                $filename = time().rand().".".$extension;
                $path = Order::$path.'/'.$order->id;
            } while (Storage::disk('local')->exists($path."/".$filename));
            
            Storage::disk('local')->put($path."/".$filename, file_get_contents($data['transfer_slip']));
            $image = $filename;
        }

        if ($image) {
            $order->fill(
                [
                    'transfer_slip' => $image
                ]
            )->save();
        }

        $order_weight = 0;
        // process order product
        foreach ($data['product_id'] as $key => $product_id) {
            $product = DB::table('products')
                ->select(
                    'products.id',
                    'products.name',
                    'products.price',
                    'products.weight',
                    'products.sell_in',
                    DB::raw("(SELECT product_daily_prices.price 
                        FROM product_daily_prices 
                        WHERE product_daily_prices.product_id = products.id 
                        AND product_daily_prices.date = '" . $pricing_date . "' 
                        LIMIT 1) as daily_price"
                    ),
                    DB::raw("
                        (
                            SELECT pdp.price 
                            FROM product_default_pricings pdp
                            WHERE pdp.product_id = products.id 
                            AND pdp.customer_category_id = (
                                SELECT u.customer_category_id 
                                FROM users u 
                                WHERE u.id = {$user->id}
                            )
                        ) as category_price"
                    )
                )
                ->where('id', $product_id)
                ->first();
            
            $qtyWeight = 0;
            
            if ($product->sell_in == "qty") {
                $quantity = $data['quantity'][$key];
                $qtyWeight = $quantity;
            } else {
                $weight = $data['quantity'][$key];
                $qtyWeight = $weight;
            }

            $unit_price = $product->price;
            if ($product->daily_price) {
                $unit_price = $product->daily_price;
            } elseif ($product->category_price) {
                $unit_price = $product->category_price;
            }
            $price = $unit_price * $qtyWeight;
            
            $order_product = OrderProduct::create(
                [
                    "order_id" => $order->id,
                    "product_id" => $product_id,
                    "product_name" => $product->name,
                    "quantity" => $quantity ?? null,
                    "weight" => $weight ?? null, //$product->weight == '' ? 0 : $product->weight * $quantity,
                    "unit_price" => $unit_price,
                    "price" => $price,
                    "remark" => $data['remark'][$key],
                    'nos' => null,
                    "status" => OrderProduct::$status['active'],
                ]
            );
            $order_weight += ($product->weight == '' ? 0 : $product->weight * $quantity);
            
            if (isset($data['product_options'][$key]) && $data['product_options'][$key]) {
                foreach ($data['product_options'][$key] as $opt => $opt_itm) {
                    if ($opt_itm) {
                        $order_product_option = OrderProductOption::create(
                            [
                            "order_product_id" => $order_product->id,
                                "option" => $opt,
                                "option_item" => $opt_itm,
                                "status" => OrderProductOption::$status['active'],
                            ]
                        );
                    }
                }
            }
            $total += $price;
        }

        $order->fill(
            [
                'total_price' => $total,
                'order_weight' => $order_weight
            ]
        )->save();

        $this->generateDoNumber($order);

        return redirect(route('admin.orders.summary', $order->id))->with('success', "Order has been created!");
    }

    public function edit($id)
    {
        $order = Order::find(decrypt($id));
        // payment_method
        $payment_method_options = User::$payment_method;
        foreach ($payment_method_options as $key => $value) {
            $payment_method_options[$key] = trans('user.payment_method.'.$value);
        }
        
        $order->transfer_slip_url = "";
        if ($order->payment_method == User::$payment_method['bank-transfer']) {
            $order->transfer_slip_url = url('/') . '/'.Order::$path.'/'.$order->id.'/'.$order->transfer_slip;
        }
        
        $order_products = DB::table('order_products')
            ->select(
                'order_products.id as order_product_id', 
                'products.id as product_id', 
                'order_products.product_name', 
                'order_products.quantity', 
                'order_products.weight', 
                'order_products.unit_price as price', 
                'order_products.price as total_price',
                'order_products.remark',
            )
            ->leftJoin('orders', 'orders.id', '=', 'order_products.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
            ->where('order_products.status', OrderProduct::$status['active'])
            ->where('orders.id', $order->id)
            ->get();

        $total = 0;
        foreach ($order_products as $key => $value) {
            $total += $order_products[$key]->price * $value->quantity;
            foreach (OrderProduct::getOption($value->order_product_id) as $itm_key => $itm_value) {
                $order_products[$key]->$itm_key = $itm_value;
            }
            $order_products[$key]->remark = $value->remark? : "";
            unset($order_products[$key]->order_product_id);
        }

        return view('admin.orders.edit', [
                'payment_method_options' => $payment_method_options? : [],
                'shipping_state_options' => System::$country_state['MY'],
                'customer' => $order->customer,
                'order' => $order,
                'products' => $order_products->toArray(),
                'areaList' => Helper::areaList(),
            ]
        );
    }
    
    public function update(Request $request, $id)
    {
        $order = Order::find(decrypt($id));
        $data = $this->validate_update_order($request, $order);
        if (isset($data['error']) && $data['error']) {
            return redirect()->back()->withInput()->withErrors($data['field_err']);
        }

        $user = User::find($order->user_id);
        $total = 0;
        $order->update(
            [
                "total_price" => $total,
                "attn_name" => $data['attn_name'],
                "attn_contact" => $data['attn_contact'],
                "pricing_date" => $request['pricing_date'],
                "delivering_date" => $request['delivering_date'],
                "payment_method" => $data['payment_method'] ?? null,
                "area" => $request['area'],
                "billing_address" => $data['billing_address'],
                "billing_city" => $request['billing_city'],
                "shipping_city" => $request['shipping_city'],
                "billing_postcode" => $data['billing_postcode'] ?? null,
                "billing_state" => $data['billing_state'] ?? null,
                "shipping_address" => $data['shipping_address'],
                "shipping_postcode" => $data['shipping_postcode'] ?? null,
                "shipping_state" => $data['shipping_state'] ?? null,
            ]
        );

        $image = null;
        if (isset($data['transfer_slip']) && $data['transfer_slip']) {
            do {
                $extension = $data['transfer_slip']->getClientOriginalExtension();
                $filename = time().rand().".".$extension;
                $path = Order::$path.'/'.$order->id;
            } while(Storage::disk('local')->exists($path."/".$filename));
            
            Storage::disk('local')->put($path."/".$filename, file_get_contents($data['transfer_slip']));
            $image = $filename;
        }

        if ($image) {
            $order->fill(
                [
                'transfer_slip' => $image
                ]
            )->save();
        }

        // remove all added product first, add back later
        // Order::where('order_id', $order->id)->delete();

        foreach ($data['product_id'] as $key => $product_id) {
            $product = DB::table('products')
                ->select(
                    'products.id',
                    'products.name',
                    'products.price',
                    'products.weight',
                    'products.sell_in',
                    DB::raw("
                        (
                            SELECT pdp.price 
                            FROM product_default_pricings pdp
                            WHERE pdp.product_id = products.id 
                            AND pdp.customer_category_id = (
                                SELECT u.customer_category_id 
                                FROM users u 
                                WHERE u.id = {$user->id}
                            )
                        ) as category_price"
                    )
                )
                ->where('id', $product_id)
                ->first();
            
            $qtyWeight = 0;
            if ($product->sell_in == "qty") {
                 $quantity = $data['quantity'][$key];
                 $qtyWeight = $quantity;
            } else {
                 $weight = $data['weight'][$key];
                 $qtyWeight = $weight;
            }
           
            $unit_price = $product->category_price ?? $product->price;
            $price = $unit_price * $qtyWeight;

            $order_product = OrderProduct::updateOrCreate(
                [
                    "order_id" => $order->id,
                    "product_id" => $product_id
                ],
                [
                    "product_name" => $product->name,
                    "quantity" => $quantity ?? null,
                    "weight" => $weight ?? null,
                    "unit_price" => $unit_price,
                    "price" => $price,
                    "remark" => $data['remark'][$key],
                    "status" => OrderProduct::$status['active'],
                ]
            );
            
            if (isset($data['product_options'][$key]) && $data['product_options'][$key]) {
                foreach ($data['product_options'][$key] as $opt => $opt_itm) {
                    $order_product_option = OrderProductOption::create(
                        [
                            "order_product_id" => $order_product->id,
                            "option" => $opt,
                            "option_item" => $opt_itm,
                            "status" => OrderProductOption::$status['active'],
                        ]
                    );
                }
            }
            $total += $price;
        }

        $order->fill(
            [
            'total_price' => $total
            ]
        )->save();

        return redirect(route('admin.orders.summary', $order->id))->with('success', "Order has been edited!");

    }

    public function getOrderData(Request $request, Order $order)
    {
        $order->payment_method = json_encode([$order->payment_method]);
        return json_encode(
            [
            'success' => true,
            'order' => $order
            ]
        );
    }

    private function validate_update_order(Request $request, Order $order)
    {
        if(in_array($order->status, ['completed'])) {
            return [
                'error' => "Order cannot be edited.",
                'field_err' => [],
            ];
        }

        $rules = [
            "customer_id" => ['required'],
            "attn_name" => array_merge(Order::$attribute_rules['attn_name'], []),
            "attn_contact" => array_merge(Order::$attribute_rules['attn_contact'], []),
            // "payment_method" => array_merge(Order::$attribute_rules['payment_method'], []),
            "billing_address" => array_merge(Order::$attribute_rules['billing_address'], []),
            // "billing_postcode" => array_merge(Order::$attribute_rules['billing_postcode'], []),
            // "billing_state" => array_merge(Order::$attribute_rules['billing_state'], []),
            "shipping_address" => array_merge(Order::$attribute_rules['shipping_address'], []),
            "shipping_postcode" => array_merge(Order::$attribute_rules['shipping_postcode'], []),
            "shipping_state" => array_merge(Order::$attribute_rules['shipping_state'], []),
            "transfer_slip" => ['nullable', 'mimes:jpg,jpeg,png', 'max:4096'],
            "product_id" => ['array'],
            "product_options" => ['array'],
            "remark" => ['array'],
            "quantity" => ['array'],
            "weight" => ['array'],
        ];

        try {
            $data = $request->validate($rules);
        } catch (ValidationException $err) {
            return [
                'error' => $err->getMessage(),
                'field_err' => $err->validator->errors()->getMessages(),
            ];
        }

        return $data;
    }

    public function getProducts(Request $request, $id)
    {
        if ($id != 'products_visibility') {
            $customer = User::find($id);
        }

        $products = Product::select('id', 'name', 'sku', 'price', 'images')
            ->where('status', Product::$status['active'])
            ->get();

        $products_output = [];
        foreach ($products as $key => $value) {
            $image = json_decode($value->images, true);
            $products[$key]->original_price = $value->price;
            if ($id != 'products_visibility') {
                $products[$key]->price = Product::get_today_price($value->id, $customer);
            }
            $products[$key]->image_url = url('/') . '/' . Product::$path."/".$value->id."/".$image[0];
            $products[$key]->product_option = Product::getOption($value->id, true);
            $products_output[$value->id] = $products[$key];
        }

        return json_encode(
            [
                'success' => true,
                'products' => $products_output
            ]
        );
    }

    private function validate_store_order(Request $request)
    {
        $rules = [
            "customer_id" => ['required'],
            "attn_name" => array_merge(Order::$attribute_rules['attn_name'], []),
            "attn_contact" => array_merge(Order::$attribute_rules['attn_contact'], []),
            // "payment_method" => array_merge(Order::$attribute_rules['payment_method'], []),
            'payment_method' => ['nullable'],
            "billing_address" => array_merge(Order::$attribute_rules['billing_address'], []),
            // "billing_postcode" => array_merge(Order::$attribute_rules['billing_postcode'], []),
            'billing_postcode' => ['nullable'],
            // "billing_state" => array_merge(Order::$attribute_rules['billing_state'], []),
            'billing_state' => ['nullable'],
            "shipping_address" => array_merge(Order::$attribute_rules['shipping_address'], []),
            // "shipping_postcode" => array_merge(Order::$attribute_rules['shipping_postcode'], []),
            'shipping_postcode' => ['nullable'],
            // "shipping_state" => array_merge(Order::$attribute_rules['shipping_state'], []),
            'shipping_state' => ['nullable'],
            // "transfer_slip" => array_merge(Order::$attribute_rules['transfer_slip'], []),
            'transfer_slip' => ['nullable'],
            "product_id" => ['array'],
            "product_options" => ['array'],
            "remark" => ['array'],
            'nos' => ['array'],
            "quantity" => ['array'],
            "weight" => ['array']
        ];

        try {
            $data = $request->validate($rules);
        } catch (ValidationException $err) {
            return [
                'error' => $err->getMessage(),
                'field_err' => $err->validator->errors()->getMessages(),
            ];
        }

        return $data;
    }

    public function getCustomerData(Request $request)
    {
        $customer = User::find($request['id']);
        return response()->json(
            [
                'success' => true,
                'customer' => $customer
            ]
        );
    }

    public function change_order_status(Request $request)
    {
        $ids = explode(',', $request->orders_id);
        if ($ids) {
            foreach ($ids as $id) {
                if (!DB::table('order_products')->where('weight', null)->where('order_id', $id)->first()) {
                    DB::table('orders')->where('id', $id)->update(
                        [
                            // 'order_weight' => $request->order_weight,
                            'status' => $request->status,
                        ]
                    );
                }
            }
        }

        return back()->with('success', 'Order status changed successfully.');
    }

    public function order_products_list(Request $request)
    {
        $products = DB::table('order_products')
            ->leftJoin('products', 'products.id', 'order_products.product_id')
            ->select('order_products.id', 'products.name', 'order_products.weight')
            ->where('order_products.order_id', decrypt($request['id']))
            ->where('order_products.status', 'active')
            ->get()
            ->toArray();

        $order = Order::find(decrypt($request['id']));
        if($order->do_date == '0000-00-00')
            {
                $order->do_date = $order->created_at->format("Y-m-d");
            }
        
        $view = view('admin.orders.order_products_list', compact('products','order'))->render();
        return Response::json(
            [
                'success' => true, 
                'view' => $view
            ]
        );
    }

    public function update_order_products_weight(Request $request)
    {
        $order = Order::find(decrypt($request['orders_id']));
        $products = DB::table('order_products')
            ->select('id')
            ->where('order_id', $order->id)
            ->get()
            ->toArray();

        $order_weight = 0;
        foreach ($products as $product) {
            $weight = $request->input('order_product_' . $product->id);
            DB::table('order_products')->where('id', $product->id)->update(
                [
                    'weight' => $weight
                ]
            );
            $order_weight += $weight;
        }

        // update order weight
        $order->update(
            [
            'order_weight' => $order_weight,
            'do_date' => $request['do_date']
            ]
        );

        return back()->with('success', 'Order products weight updated successfully.');
    }

    public function change_order_lorry(Request $request)
    {
        DB::table('orders')->where('id', decrypt($request['orders_id']))->update(
            [
                'driver_id' => $request['driver_id'],
            ]
        );

        return back()->with('success', 'Lorry has been changed successfully.');
    }

    public function assign_order_driver(Request $request)
    {
        $ids = explode(',', $request->orders_id);
        if ($ids) {
            DB::table('orders')->whereIn('id', $ids)->update(
                [
                    'driver_id' => $request['driver_id'],
                ]
            );
        }

        return back()->with('success', 'Lorry has been changed successfully.');
    }

    public function viewSummary($id)
    {
        $order = Order::select(
            "*",
            DB::raw(
                "CONCAT(
                    orders.billing_address, '<br />', 
                    orders.billing_city, '<br />', 
                    orders.billing_postcode, '<br />', 
                    orders.billing_state
                ) AS billing_address"
            ),
            DB::raw(
                "CONCAT(
                orders.shipping_address, '<br />', 
                orders.shipping_city, '<br />', 
                orders.shipping_postcode, '<br />', 
                orders.shipping_state
            ) AS shipping_address"
            ),
        )->where('id', $id)->first();

        $order_products = DB::table('order_products')
            ->select(
                'order_products.id as order_product_id', 
                'products.id as product_id', 
                'products.show_qty',
                'products.show_weight',
                'orders.id as order_id', 
                'orders.transfer_slip as transfer_slip', 
                'order_products.product_name as name', 
                'order_products.quantity', 
                'order_products.weight',
                'order_products.product_weight',
                'order_products.unit_price', 
                'order_products.price',
                'order_products.remark'
            )
            ->leftJoin('orders', 'orders.id', '=', 'order_products.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
            ->where('order_products.status', OrderProduct::$status['active'])
            ->where('orders.id', $order->id)
            ->get();

        $total = 0;
        foreach ($order_products as $key => $value) {
            $order_products[$key]->options = OrderProduct::getOption($value->order_product_id);

            $transfer_slip_url = "";
            if ($order->payment_method == User::$payment_method['bank-transfer']) {
                $transfer_slip_url = url('/') . '/'.Order::$path.'/'.$value->order_id.'/'.$value->transfer_slip;
            }
            $order_products[$key]->transfer_slip_url = $transfer_slip_url;
            
            $total += $order_products[$key]->unit_price * $value->quantity;
        }

        $drivers_arr = [];
        $drivers = DB::table('drivers')->select('id', 'lorry_number')->get()->toArray();
        foreach ($drivers as $driver) {
            $drivers_arr[$driver->id] = $driver->lorry_number;
        }

        return view('admin.orders.order-summary', [
                'order' => $order,
                'invoice_url' => url('/') . '/'.Order::$path.'/'.$order->id.'/invoice-' . $order->id . '.pdf',
                'invoice_download_url' => url('download/').Order::$path.'/'.$order->id.'/invoice-' . $order->id . '.pdf',
                'invoice2_url' => url('/') . '/'.Order::$path.'/'.$order->id.'/invoice2-' . $order->id . '.pdf',
                'invoice2_download_url' => url('download/').Order::$path.'/'.$order->id.'/invoice2-' . $order->id . '.pdf',
                'delivery_order_url' => url('/') . '/'.Order::$path.'/'.$order->id.'/delivery-order-' . $order->id . '.pdf',
                'delivery_order_download_url' => url('download/').Order::$path.'/'.$order->id.'/delivery-order-' . $order->id . '.pdf',
                'products' => $order_products,
                'total' => number_format($total, 2, '.', ''),
                'customer' => $order->customer,
                'drivers' => $drivers_arr,
            ]
        );
    }

    public function export(Request $request)
    {
        return Excel::download(new \App\Exports\OrdersExport(), 'Orders Report - ' . date('Y-m-d') . '.xlsx');

        $orders = Order::select("*");

        if ($filter_id = $request->input('id')) {
            $orders->where('id', $filter_id);
        }

        if ($filter_user_id = $request->input('customer')) {
            $orders->where('user_id', $filter_user_id);
        } else {
            $ids = explode(',', $request->orders_id);
            if ($ids) {
                $orders->whereIn('id', $ids);
            }
        }

        if ($filter_fdate = $request->input('fdate')) {
            $orders->where('created_at', '>=', $filter_fdate);
        }

        if ($filter_tdate = $request->input('tdate')) {
            $orders->where('created_at', '<=', $filter_tdate." 23:59:59");
        }

        if ($filter_price_range = $request->input('price_range')) {
            $filter_price_range = explode(',', $filter_price_range);
            $from_price = $filter_price_range[0];
            $to_price = $filter_price_range[1];
            $orders->where('total_price', '>=', $from_price);
            $orders->where('total_price', '<=', $to_price);
        }

        if ($filter_shipping_state = $request->input('shipping_state')) {
            $orders->where('shipping_state', $filter_shipping_state);
        }

        if ($filter_status = $request->input('status')) {
            $orders->where('status', $filter_status);
        }

        $orders = $orders->get();
        $data = [];

        foreach ($orders as $key => $order) {
            $data[] = [
                'no' => $key + 1,
                'created_at' => $order->created_at,
                'customer' => $order->customer->name,
                'price' => $order->total_price,
                'payment_method' => $order->payment_method ? __('user.payment_method.'.$order->payment_method) : '',
                'billing_address' => $order->billing_address .", ". $order->billing_postcode .", ". $order->billing_state,
                'shipping_address' => $order->shipping_address .", ". $order->shipping_postcode .", ". $order->shipping_state,
                'status' => __('order.status.'.$order->status),
                'updated_at' => $order->updated_at,
            ];
        }

        if ($data) {
            $header = ['No', 'Order At', 'Customer', 'Total Price', 'Payment Method', 'Billing Address', 'Shipping Address', 'Status', 'Last Updated At']; // Adjust the header based on your data model
            return Excel::download(new AdminOrderExport(collect($data), $header), Carbon::now()->format('YmdHis').'-Order-List.xlsx');
        }
    }

    public function downloadInvoiceDoAsZip(Request $request)
    {
        $zip = new ZipArchive();
        $zipFileName = storage_path('app/public/invoices_and_delivery_orders'. Carbon::now()->format('YmdHis') .'.zip');
    
        if ($zip->open($zipFileName, ZipArchive::CREATE) === true) {
            $orders = Order::whereIn('id', $request->order_ids);
    
            foreach ($orders->get() as $order) {
                $orderFile = storage_path('app/orders/' . $order->id . '/delivery-order-' . $order->id . '.pdf');
                if (file_exists($orderFile)) {
                    $zip->addFile($orderFile, 'do/delivery-order-' . $order->id . '.pdf');
                }
                
                $orderFile = storage_path('app/orders/' . $order->id . '/invoice-' . $order->id . '.pdf');
                if (file_exists($orderFile)) {
                    $zip->addFile($orderFile, 'invoice/invoice-' . $order->id . '.pdf');
                }
                
                $orderFile = storage_path('app/orders/' . $order->id . '/invoice2-' . $order->id . '.pdf');
                if (file_exists($orderFile)) {
                    $zip->addFile($orderFile, 'invoice/invoicewoprice-' . $order->id . '.pdf');
                }
            }
    
            $zip->close();

            $orders->update(
                [
                'status' => 'completed'
                ]
            );
    
            return response()->download($zipFileName)->deleteFileAfterSend(true);
        }
    }

    public function download_do_zip(Request $request)
    {
        // format current date or from and to date
        $fdate = $request->fdate;
        $tdate = $request->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;
        $endDate = $tdate ?: $today;
        $startDate = min($startDate, $endDate);

        $zip = new ZipArchive();
        $zipFileName = storage_path('app/public/DO_Report_orders'. Carbon::now()->format('YmdHis') .'.zip');
    
        if ($zip->open($zipFileName, ZipArchive::CREATE) === true) {
            $orders = DB::table('orders')
                ->select('id')
                ->whereBetween('orders.created_at', [$startDate, $endDate . " 23:59:59"])
                ->when(
                    $request->id, function ($q) {
                        return $q->where('orders.id', request()->id);
                    }
                )
            ->when(
                $request->status, function ($q) {
                    return $q->where('orders.status', request()->status);
                }
            )
            ->when(
                $request->driver, function ($q) {
                    return $q->where('orders.driver_id', request()->driver);
                }
            )
            ->when(
                $request->customer, function ($q) {
                    return $q->where('orders.user_id', request()->customer);
                }
            )
            ->when(
                $request->shipping_state, function ($q) {
                    return $q->where('orders.shipping_state', 'LIKE', '%' . request()->shipping_state . '%');
                }
            )
            ->get()
            ->toArray();
    
            foreach ($orders as $order) {
                $orderFile = storage_path('app/orders/' . $order->id . '/delivery-order-' . $order->id . '.pdf');
                if (file_exists($orderFile)) {
                    $zip->addFile($orderFile, 'do/delivery-order-' . $order->id . '.pdf');
                }
            }

            $zip->close();
    
            return response()->download($zipFileName)->deleteFileAfterSend(true);
        }
    }

    public function update_order_status(Request $request, Order $order)
    {
        $success = false;
        $status = $request->status;
        if (!in_array($status, Order::$status)) {
            $success = false;
        }
        
        $prev_status = $order->status;
        $order->update(
            [
            'status' => $status,
            ]
        );
        $success = true;
        
        return json_encode(['success' => $success]);
    }

    public function batchUpdate(Request $request, Order $order)
    {
        $success = false;

        $status = $request->status;
        if (!in_array($status, Order::$status)) {
            $success = false;
        }
        
        foreach ($request->order_ids as $key => $order_id) {
            $order = Order::find($order_id);

            $prev_status = $order->status;
            $order->update(
                [
                'status' => $status,
                ]
            );
            $success = true;
        }
        
        return json_encode(['success' => $success]);
    }

    /**
     * Common method to generate DO number
     */
    private static function generateDoNumber($order)
    {
        $prefix = 'DO' . now()->format('ym');
        $latest = Order::where('do_no', 'like', $prefix . '%')
            ->orderBy('do_no', 'desc')
            ->first();

        $do_no_idx = $latest ? ((int) substr($latest->do_no, strlen($prefix)) + 1) : 1;
        $ending_digit = sprintf('%04d', $do_no_idx);

        $order->do_no = $prefix . $ending_digit;
        $order->save();
    }

    public function sync_delivery_order(Request $request)
    {
        $request->validate([
            'orders_id' => 'required|string',
        ]);

        $orderIds = array_filter(explode(',', $request->input('orders_id')));
        $orders = Order::getOrdersWithUser($orderIds);
        $cartIds = $orders->pluck('cart_id')->filter()->unique()->all();

        $cartItemsMap = Order::getCartItemsForOrders($cartIds);

        $allOrders = $orders
            ->map(function ($order) use ($cartItemsMap) {
                return [
                    'id' => $order->id,
                    'do_no' => $order->do_no,
                    'do_date' => $order->do_date,
                    'attn_name' => $order->attn_name,
                    'attn_contact' => $order->attn_contact,
                    'billing_address' => $order->billing_address,
                    'payment_method' => $order->payment_method,
                    'sql_sync_status' => $order->sql_sync_status,
                    'sql_sync_message' => $order->sql_sync_message,
                    'user_name' => $order->user_name,
                    'user_email' => $order->user_email,
                    'sql_customer_code' => $order->sql_customer_code,
                    'status' => $order->status,
                    'cart_id' => $order->cart_id,
                    'items' => $cartItemsMap[$order->cart_id] ?? [],
                ];
            })->keyBy('id'); // This sets 'id' as the key for $allOrders


        $validOrders = [];
        $invalidOrders = [];
        $notFoundOrders = [];

        foreach ($orderIds as $id) {
            if (!isset($allOrders[$id])) {
                $notFoundOrders[] = "Order ID $id not found.";
                continue;
            }

            $order = $allOrders[$id];

            $errors = [];

           if ($order['status'] !== 'processing') {
                $errors[] = "Status is '{$order['status']}'";
            }

            if (empty($order['do_no'])) {
                $errors[] = "DO number is empty";
            }

            if ($order['sql_sync_status'] === 'success') {
                $errors[] = "Already synced";
            }

            if (!empty($errors)) {
                $invalidOrders[$id] = implode(', ', $errors);
            } else {
                $validOrders[$id] = $order;
            }
        }

        $syncedOrders = [];
        $syncFailures = [];

        if (!empty($validOrders)) {
            $syncResult = app(DeliveryOrderSyncService::class)->sync(collect($validOrders));

            foreach ($syncResult as $id => $result) {
                if ($result['status'] === 'success') {
                    $syncedOrders[$id] = $result['message'];
                } else {
                    $syncFailures[$id] = $result['message'];
                }
            }
        }

        return redirect()->back()->with([
            'success_count' => count($syncedOrders),
            'fail_count' => count($invalidOrders) + count($syncFailures),
            'synced_orders' => $syncedOrders,
            'invalid_orders' => $invalidOrders,
            'sync_failures' => $syncFailures,
            'not_found' => $notFoundOrders,
        ]);
    }

    public function sync_invoice(Request $request)
    {
        $request->validate([
            'orders_id' => 'required|string',
        ]);

        $orderIds = array_filter(explode(',', $request->input('orders_id')));
        $allOrders = Order::prepareSyncOrders($orderIds);
        $validOrders = [];
        $invalidOrders = [];
        $notFoundOrders = [];

        foreach ($orderIds as $id) {
            if (!isset($allOrders[$id])) {
                $notFoundOrders[] = "Order ID $id not found.";
                continue;
            }

            $order = $allOrders[$id];

            $errors = [];

           if ($order['status'] !== 'processing') {
                $errors[] = "Status is '{$order['status']}'";
            }

            if (empty($order['do_no'])) {
                $errors[] = "DO number is empty";
            }

            if ($order['sql_sync_status'] === 'success') {
                $errors[] = "Already synced";
            }

            if (!empty($errors)) {
                $invalidOrders[$id] = implode(', ', $errors);
            } else {
                $validOrders[$id] = $order;
            }
        }

        $syncedOrders = [];
        $syncFailures = [];

        if (!empty($validOrders)) {
            foreach ($validOrders as $id => $order) {
                try {
                    // 🆕 Save sync queue record
                    SqlSyncRecord::queue([
                        'target_id'   => $order['id'],
                        'action'      => 'invoice',
                        'target_name' => $order['do_no'],
                        'details'     => $order,
                    ]);

                    $syncedOrders[$id] = "Sync job queued";

                } catch (\Exception $ex) {
                    $syncFailures[$id] = $ex->getMessage();
                }
            }
        }

        return redirect()->back()->with([
            'success_count' => count($syncedOrders),
            'fail_count' => count($invalidOrders) + count($syncFailures),
            'synced_orders' => $syncedOrders,
            'invalid_orders' => $invalidOrders,
            'sync_failures' => $syncFailures,
            'not_found' => $notFoundOrders,
        ]);
    }
}
