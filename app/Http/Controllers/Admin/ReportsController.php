<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CustomerUomsReport;
use App\Http\Controllers\Controller;

use App\Exports\DailySummaryStockReport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DailySummaryReport;
use Illuminate\Support\Facades\DB;
use App\Exports\DailySaleReport;
use App\Exports\OrderStockReport;
use Illuminate\Http\Request;
use App\System;
use App\Helper;
use App\Exports\SqlDoExportReport;
use App\Order;

class ReportsController extends Controller
{
    public function daily_sales_report(Request $request)
    {
        // format current date or from and to date
        $fdate = $request->fdate;
        $tdate = $request->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;
        $endDate = $tdate ?: $today;
        $startDate = min($startDate, $endDate);

        $data = $this->get_order_filters();

        $data['orders'] = DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->select(
                'orders.id',
                'orders.created_at',
                'users.name',
                'order_products.product_name',
                'products.sku',
                'order_products.quantity',
                'order_products.unit_price',
                'order_products.price',
                'orders.payment_method',
                'orders.area',
                'orders.billing_address',
                'orders.billing_city',
                'orders.billing_postcode',
                'orders.billing_state',
                'orders.shipping_address',
                'orders.shipping_city',
                'orders.shipping_postcode',
                'orders.shipping_state',
                'orders.updated_at',
            )
            ->whereBetween('orders.created_at', [$startDate, $endDate . " 23:59:59"])
            ->when($request->id, function ($q) {
                return $q->where('orders.id', request()->id);
            })
            ->when($request->status, function ($q) {
                return $q->where('orders.status', request()->status);
            })
            ->when($request->driver, function ($q) {
                return $q->where('orders.driver_id', request()->driver);
            })
            ->when($request->customer, function ($q) {
                return $q->where('orders.user_id', request()->customer);
            })
            ->when($request->area, function ($q) {
                return $q->where('orders.area', request()->area);
            })
            ->get()
            ->toArray();

        return view('admin.reports.daily_sales_report', $data);
    }

    public function export_daily_sales_report(Request $request)
    {
        $fdate = $request->fdate;
        $tdate = $request->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;
        $endDate = $tdate ?: $today;
        $date = min($startDate, $endDate);

        return Excel::download(new DailySaleReport(), 'Daily Sale Report - ' . $date . '.xlsx');
    }

    public function daily_summary_report(Request $request)
    {
        // format current date or from and to date
        $fdate = $request->fdate;
        $tdate = $request->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;
        $endDate = $tdate ?: $today;
        $startDate = min($startDate, $endDate);

        $data = $this->get_order_filters();
        $data['orders'] = DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->select(
                'orders.id',
                'orders.created_at',
                'users.name',
                'order_products.product_name',
                'products.sku',
                'order_products.quantity',
                'order_products.weight',
                'orders.order_weight',
            )
            ->whereBetween('orders.created_at', [$startDate, $endDate . " 23:59:59"])
            ->when($request->id, function ($q) {
                return $q->where('orders.id', request()->id);
            })
            ->when($request->status, function ($q) {
                return $q->where('orders.status', request()->status);
            })
            ->when($request->driver, function ($q) {
                return $q->where('orders.driver_id', request()->driver);
            })
            ->when($request->customer, function ($q) {
                return $q->where('orders.user_id', request()->customer);
            })
            ->when($request->area, function ($q) {
                return $q->where('orders.area', request()->area);
            })
            ->get()
            ->toArray();

        return view('admin.reports.daily_summary_report', $data);
    }

    public function export_daily_summary_report(Request $request)
    {
        $fdate = $request->fdate;
        $tdate = $request->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;
        $endDate = $tdate ?: $today;
        $date = min($startDate, $endDate);

        return Excel::download(new DailySummaryReport(), 'Daily Summary Report - ' . $date . '.xlsx');
    }

    public function daily_summary_stock_report(Request $request)
    {
        // format current date or from and to date
        $fdate = $request->fdate;
        $tdate = $request->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;
        $endDate = $tdate ?: $today;
        $startDate = min($startDate, $endDate);

        $data = $this->get_order_filters();

         $data['orders'] = DB::table('order_products')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->join('orders', 'orders.id', '=', 'order_products.order_id') // <-- Add this join
            ->select(
                'order_products.product_name',
                'products.sku',
                DB::raw('SUM(order_products.quantity) AS quantity')
            )
            ->whereBetween('order_products.created_at', [$startDate, $endDate . " 23:59:59"])
            ->when($request->id, function ($q) {
                return $q->where('order_products.order_id', request()->id);
            })
            ->when($request->status, function ($q) {
                return $q->where('orders.status', request()->status);
            })
            ->when($request->driver, function ($q) {
                return $q->where('orders.driver_id', request()->driver);
            })
            ->when($request->customer, function ($q) {
                return $q->where('orders.user_id', request()->customer);
            })
            ->when($request->area, function ($q) {
                return $q->where('orders.area', request()->area);
            })
            ->groupBy('order_products.product_name', 'products.sku') // group properly
            ->get()
            ->toArray();

        return view('admin.reports.daily_summary_stock_report', $data);
    }

    public function export_daily_summary_stock_report(Request $request)
    {
        $fdate = $request->fdate;
        $tdate = $request->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;
        $endDate = $tdate ?: $today;
        $date = min($startDate, $endDate);

        return Excel::download(new DailySummaryStockReport(), 'Daily Summary Stock Report - ' . $date . '.xlsx');
    }

    private function get_order_filters()
    {
        $data['statuses'] = ['cancelled' => 'Cancelled', 'processing' => 'Processing', 'completed' => 'Completed'];
        $data['drivers'] = DB::table('drivers')->select('id', 'lorry_number')->get()->toArray();
        $data['customers'] = DB::table('users')->select('id', 'name', 'email')->get()->toArray();
        $data['areaList'] = Helper::areaList();
        $data['query_params'] = Helper::query_params(request()->input());

        return $data;
    }

    public function do_report(Request $request)
    {
        // format current date or from and to date
        $fdate = $request->fdate;
        $tdate = $request->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;
        $endDate = $tdate ?: $today;
        $startDate = min($startDate, $endDate);

        $data = $this->get_order_filters();

        $data['orders'] = DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->select(
                'users.name',
                'orders.id',
                'orders.user_id',
                'orders.order_weight',
                'orders.total_price',
                'orders.payment_method',
                'orders.area',
                'orders.billing_address',
                'orders.billing_city',
                'orders.billing_postcode',
                'orders.billing_state',
                'orders.shipping_address',
                'orders.shipping_city',
                'orders.shipping_postcode',
                'orders.shipping_state',
                'orders.driver_id',
                'orders.status',
                'orders.created_at',
                'orders.updated_at',
                DB::raw("
                    (
                        SELECT GROUP_CONCAT(CONCAT(p.name, ': ', IFNULL(CONCAT(op.weight, 'KG'), '')) SEPARATOR '<br />')
                        FROM products p
                        JOIN order_products op ON p.id = op.product_id
                        WHERE op.order_id = orders.id
                    ) as product_info
                "),
            )
            ->whereBetween('orders.created_at', [$startDate, $endDate . " 23:59:59"])
            ->when($request->id, function ($q) {
                return $q->where('orders.id', request()->id);
            })
            ->when($request->status, function ($q) {
                return $q->where('orders.status', request()->status);
            })
            ->when($request->driver, function ($q) {
                return $q->where('orders.driver_id', request()->driver);
            })
            ->when($request->customer, function ($q) {
                return $q->where('orders.user_id', request()->customer);
            })
            ->when($request->area, function ($q) {
                return $q->where('orders.area', request()->area);
            })
            ->get()
            ->toArray();

        $drivers_arr = [];
        $drivers = DB::table('drivers')->select('id', 'lorry_number')->get()->toArray();
        foreach ($drivers as $driver) {
            $drivers_arr[$driver->id] = $driver->lorry_number;
        }
        $data['order_drivers'] = $drivers_arr;

        return view('admin.reports.do_report', $data);
    }
    
    public function sql_do_export_report(Request $request)
    {
        // format current date or from and to date
        // $fdate = $request->fdate;
        // $tdate = $request->tdate;

        // $today = now()->toDateString();
        // $startDate = $fdate ?: $today;
        // $endDate = $tdate ?: $today;
        // $startDate = min($startDate, $endDate);

        $data = $this->get_order_filters();

        // $data['orders'] = DB::table('orders')
        //     ->join('users', 'users.id', '=', 'orders.user_id')
        //     ->select(
        //         'users.name',
        //         'orders.id',
        //         'orders.user_id',
        //         'orders.order_weight',
        //         'orders.total_price',
        //         'orders.payment_method',
        //         'orders.area',
        //         'orders.billing_address',
        //         'orders.billing_city',
        //         'orders.billing_postcode',
        //         'orders.billing_state',
        //         'orders.shipping_address',
        //         'orders.shipping_city',
        //         'orders.shipping_postcode',
        //         'orders.shipping_state',
        //         'orders.driver_id',
        //         'orders.status',
        //         'orders.created_at',
        //         'orders.updated_at',
        //         DB::raw("
        //             (
        //                 SELECT GROUP_CONCAT(CONCAT(p.name, ': ', op.weight, 'KG') SEPARATOR '<br />')
        //                 FROM products p
        //                 JOIN order_products op ON p.id = op.product_id
        //                 WHERE op.order_id = orders.id
        //             ) as product_info
        //         "),
        //     )
        //     ->whereBetween('orders.created_at', [$startDate, $endDate . " 23:59:59"])
        //     ->when($request->id, function ($q) {
        //         return $q->where('orders.id', request()->id);
        //     })
        //     ->when($request->status, function ($q) {
        //         return $q->where('orders.status', request()->status);
        //     })
        //     ->when($request->driver, function ($q) {
        //         return $q->where('orders.driver_id', request()->driver);
        //     })
        //     ->when($request->customer, function ($q) {
        //         return $q->where('orders.user_id', request()->customer);
        //     })
        //     ->when($request->area, function ($q) {
        //         return $q->where('orders.area', request()->area);
        //     })
        //     ->get()
        //     ->toArray();

        $drivers_arr = [];
        $drivers = DB::table('drivers')->select('id', 'lorry_number')->get()->toArray();
        foreach ($drivers as $driver) {
            $drivers_arr[$driver->id] = $driver->lorry_number;
        }
        $data['order_drivers'] = $drivers_arr;

        return view('admin.reports.sql_do_export_report', $data);
    }

    public function sql_do_export_report_excel(Request $req) {
        $fdate = $req->fdate;
        $tdate = $req->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;
        $endDate = $tdate ?: $today;
        $date = min($startDate, $endDate);

        return Excel::download(new SqlDoExportReport($req), 'SQL DO Export Report - ' . $date . '.xlsx');
    }

    public function order_report(Request $request)
    {
        // format current date or from and to date
        $fdate = $request->fdate;
        $tdate = $request->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;

        $endDate = $tdate ?: $today;
        $startDate = min($startDate, $endDate);

        $data['statuses'] = ['processing' => 'Processing', 'completed' => 'Completed'];
        $data['product_categories'] = DB::table('product_categories')->select('id', 'category_name')->get()->toArray();
        $request['status'] = $request['status'] ?? "processing";

        $data['orders'] = DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
            ->leftJoin('uoms', 'uoms.id', '=', 'products.uom_id')
            ->select(
                'orders.status',
                'order_products.id as order_product_id',
                'order_products.product_id',
                'order_products.product_name',
                'users.name as user_name',
                'users.sql_customer_code',
                // DB::raw('SUM(order_products.quantity) as quantity'),
                'order_products.quantity as quantity',
                'uoms.uom_name',
            )
            ->when(
                $request['product_category_id'],
                function ($q) use ($request) {
                    return $q->where('products.product_category_id', $request['product_category_id']);
                }
            )
            ->when(
                $request['area_id'], function ($q) {
                    return $q->where('orders.area_id', request()->area_id);
                }
            )
            ->when(
                ($request->fdate && $request->tdate), function ($q) use ($startDate, $endDate) {
                    return $q->whereBetween('orders.created_at', [$startDate, $endDate . " 23:59:59"]);
                }
            )
            ->when(
                $request['user_id'], function ($q) use ($request) {
                    return $q->whereIn('users.id', $request['user_id']);
                }
            )
            ->when(
                $request['status'], function ($q) use ($request) {
                    return $q->where('orders.status', '=', $request['status']);
                }
            )
            ->where('orders.status', 'processing')
            ->orderBy('order_products.product_name', 'asc')
            ->get()
            ->groupBy('product_name')
            ->toArray();

        return view('admin.reports.order_report', $data);
    }

    public function update_stock_quantity(Request $request)
    {
        try {
            $order_product_id = $request['id'];
            $qty = $request['qty'];

            // reterive the order product to get the orderId and the unit price for calculating the total order
            $order_product = DB::table('order_products')
                ->select('id', 'order_id', 'unit_price')
                ->where('id', $order_product_id)
                ->first();

            if ($order_product) {
                // update the order product qty and its total price
                DB::table('order_products')
                    ->where('id', $order_product_id)
                    ->update([
                        'quantity'  => $qty,
                        'price'     => $qty * $order_product->unit_price,
                    ]);

                // update the order total amount
                $total_price = DB::table('order_products')
                    ->where('order_id', $order_product->order_id)
                    ->where('status', 'active')
                    ->sum('price');
                    
                Order::where('id', $order_product->order_id)
                ->update([
                    'total_price' => $total_price,
                ]);
            }

            return response()->json([
                'status'    => true,
                'message'   => '',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => false,
                'message'   => 'Error occured: ' . $e->getMessage(),
            ]);
        }
    }

    public function print_order_stock_report(Request $request)
    {
        ini_set('memory_limit', '-1'); // unlimited memory
        set_time_limit(0); // unlimited execution time

        try {
            $fdate = $request->fdate;
            $tdate = $request->tdate;

            $today = now()->toDateString();
            $startDate = $fdate ?: $today;
            
            $endDate = $tdate ?: $today;
            $startDate = min($startDate, $endDate);

            $data['orders'] = DB::table('order_products')
                ->join('orders', 'orders.id', '=', 'order_products.order_id')
                ->leftJoin('users', 'users.id', '=', 'orders.user_id')
                ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
                ->leftJoin('uoms', 'uoms.id', '=', 'products.uom_id')
                ->select(
                    'orders.status',
                    'order_products.id as order_product_id',
                    'order_products.product_id',
                    'order_products.product_name',
                    'users.name as user_name',
                    // DB::raw('SUM(order_products.quantity) as quantity'),
                    'order_products.quantity as quantity',
                    'uoms.uom_name',
                )
                ->when(
                    $request['product_category_id'],
                    function ($q) use ($request) {
                        return $q->where('products.product_category_id', $request['product_category_id']);
                    }
                )
                ->when(
                    $request['area_id'], function ($q) {
                        return $q->where('orders.area_id', request()->area_id);
                    }
                )
                ->when(
                    ($request->fdate && $request->tdate), function ($q) use ($startDate, $endDate) {
                        return $q->whereBetween('orders.created_at', [$startDate, $endDate . " 23:59:59"]);
                    }
                )
                ->when(
                    $request['user_id'], function ($q) use ($request) {
                        return $q->whereIn('users.id', $request['user_id']);
                    }
                )
                ->when(
                    $request['status'], function ($q) use ($request) {
                        return $q->where('orders.status', '=', $request['status']);
                    }
                )
                ->orderBy('order_products.product_name', 'asc')
                ->get()
                ->groupBy('product_name')
                ->toArray();

            // return view('pdf.order_stock_report', $data); // working
            $pdf = \PDF::loadView('pdf.order_stock_report', $data)
                ->set_option('isHtml5ParserEnabled', true)
                ->set_option('isRemoteEnabled', true)
                ->setPaper('a4', 'portrait'); // not working
            
            return $pdf->stream('Order Stock Report - ' . date('Y-m-d H:i') . '.pdf');
        } catch (\Exception $e) {
            return redirect()->back()->with('warning', 'Error occured: ' . $e->getMessage());
        }
    }

    public function export_order_stock_report(Request $request)
    {
        return Excel::download(new OrderStockReport(), 'Order Report - ' . date('Y-m-d') . '.xlsx');
    }

    public function customer_uoms_report(Request $request)
    {
        $data['statuses'] = [
            'processing' => 'Processing',
            'completed' => 'Completed',
        ];

        return view('admin.reports.customer_uoms_report', $data);
    }

    public function export_customer_uoms_report(Request $request)
    {
        try {
            return Excel::download(new CustomerUomsReport($request), 'Customer Uoms Report - ' . date('Y-m-d') . '.xlsx');
        } catch (\Exception $e) {
            return redirect()->back()->with('warning', 'Error occured: ' . $e->getMessage());
        }
    }
}
