<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\ProductReceiveQuantity;
use Carbon\Carbon;
use App\Product;
use App\User;

class ProductReceiveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_admin');
    }

    public function index(Request $request)
    {
        $receive_qtys = ProductReceiveQuantity::selectRaw('date, MAX(created_at) as created_at, MAX(updated_at) as updated_at');

        if ($request->get('fdate')) {
            $receive_qtys->where('date', '>=', $request->get('fdate'));
        }

        if ($request->get('tdate')) {
            $receive_qtys->where('date', '<=', $request->get('tdate'));
        }

        $receive_qtys = $receive_qtys->orderBy('date', 'desc')->groupBy('date')->paginate(12);
        return view('admin.products.product-receive', [
            'receive_qtys' => $receive_qtys,
            'input' => $request->all(),
        ]);
    }

    public function create(Request $request, $date = "", $duplicate_to_date = "")
    {
        $product_daily_price = ProductReceiveQuantity::where('date', $date)->get();

        $formatted_product_daily_price = [];
        foreach ($product_daily_price as $rq) {
            $formatted_product_daily_price[$rq->product_id] = $rq->qty;
        }

        $products = Product::where('status', Product::$status['active'])->where('product_category_id', 30); // import category 30
        
        if ($search_q = $request->input('search_q')) {
            $products->where('name', "LIKE", "%".$search_q."%");
            $products->orWhere('sku', "LIKE", "%".$search_q."%");
        }

        if (isset($request->sort_by)) {
            $sort = explode('-', $request->sort_by);
            $products->orderBy($sort[0], $sort[1]);
        }
                            
        $products = $products->paginate(12);
        $customer_categories = DB::table('customer_categories')
            ->select('id', 'category_name')
            ->get()
            ->toArray();

        return view(
            'admin.products.add-product-receive-qty-table', [
                'duplicating' => $duplicate_to_date? true : false,
                'duplicate_from_date' => $date? : "",
                'setup_date' => $duplicate_to_date? : $date,
                'products' => $products,
                'categories' => $customer_categories,
                'product_daily_price' => $formatted_product_daily_price ?? null,
                'clear_search_url' => config("app.url") . '/admin/product-receive-qty/add/' . $date,
            ]
        );
    }

    public function store_batch(Request $request, $date="")
    {
        $data = $this->validate_daily_prices_batch($request);
        if (isset($data['error']) && $data['error']) {
            return redirect()->back()->withInput()->withErrors($data['field_err']);
        }

        foreach ($data['price'] as $product_id => $qty) {
            $exist = ProductReceiveQuantity::where([
                    'date' => $date,
                    'product_id' => $product_id,
                ])->first();
    
            // if setting already existed
            if (!empty($exist)) {
                $exist->update([
                    'qty' => $qty[0]
                ]);
            } else {
                $product = ProductReceiveQuantity::create([
                    'date' => $date,
                    'product_id' => $product_id,
                    'qty' => $qty[0],
                ]);
            }
        }

        return back()->with('success', "Setting saved successfully.");
    }

    private function validate_daily_prices_batch(Request $request)
    {
        $rules = [
            "price" => ['required', 'array'],
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

    // public function store(Request $request)
    // {
    //     $data = $this->validate_daily_prices($request);
    //     if (isset($data['error']) && $data['error']) {
    //         return redirect()->back()->withInput()->withErrors($data['field_err']);
    //     }


    //     if (ProductDailyPrice::where(
    //         [
    //             'date' => $data['date'],
    //             'product_id' => $data['product_id'],
    //             'user_category' => $data['user_category'],
    //             'status' => ProductDailyPrice::$status['active'],
    //         ]
    //     )->exists()
    //     ) {
    //         return back()->with('error', "The setting has been found duplicated.");
    //     }
        
    //     $product = ProductDailyPrice::create(
    //         [
    //             'date' => $data['date'],
    //             'product_id' => $data['product_id'],
    //             'user_category' => $data['user_category'],
    //             'price' => $data['price'],
    //             'status' => ProductDailyPrice::$status['active'],
    //         ]
    //     );

    //     return redirect(url('/admin/product-daily-prices'))->with('success', "Daily price setup successfully.");
    // }

    private function validate_daily_prices(Request $request)
    {
        $rules = [
            "date" => array_merge(ProductDailyPrice::$attribute_rules['date'], []),
            "product_id" => [
                function ($attribute, $value, $fail) {
                    $product = Product::find($value);
                    if (!$product) {
                        $fail('validation.in');
                    }
                }
            ],
            "user_category" => ['nullable',
                function ($attribute, $value, $fail) {
                    $category_list = User::select('category')
                        ->groupBy('category')   
                        ->pluck('category')
                        ->toArray();
                    if (!in_array($value, $category_list)) {
                        $fail('validation.in');
                    }
                }
            ],
            "price" => array_merge(ProductDailyPrice::$attribute_rules['price'], []),
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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // public function removeProductDailyPrice(Request $request, ProductDailyPrice $product_daily_price)
    // {
    //     if ($product_daily_price->status != ProductDailyPrice::$status['active']) {
    //         return redirect()->to('/product-daily-prices')->with('error', "The setting is not found in the system.");
    //     }
        
    //     $product_daily_price->update(
    //         [
    //         'status' => ProductDailyPrice::$status['removed']
    //         ]
    //     );

    //     return redirect(url('/admin/product-daily-prices'))->with('success', "Daily price setup has been removed.");
    // }
}
