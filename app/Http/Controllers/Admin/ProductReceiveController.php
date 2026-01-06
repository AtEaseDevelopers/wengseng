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
        // Get existing receive quantities for this date
        $existing_quantities = ProductReceiveQuantity::where('date', $date)
            ->with('product')
            ->get();

        // Get active products for Select2 dropdown
        $products = Product::where('status', Product::$status['active'])
            ->orderBy('name', 'asc')
            ->select('id', 'name', 'sku')
            ->get();

        return view(
            'admin.products.add-product-receive-qty-table', [
                'duplicating' => $duplicate_to_date ? true : false,
                'duplicate_from_date' => $date ?: "",
                'setup_date' => $duplicate_to_date ?: $date,
                'products' => $products,
                'existing_quantities' => $existing_quantities,
            ]
        );
    }

    public function store_batch(Request $request, $date="")
    {
        $items = $request->input('items', []);

        // Delete existing quantities for this date first
        ProductReceiveQuantity::where('date', $date)->delete();

        // Insert new quantities
        foreach ($items as $item) {
            if (!empty($item['product_id']) && isset($item['qty']) && $item['qty'] > 0) {
                ProductReceiveQuantity::create([
                    'date' => $date,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'remark' => $item['remark'],
                ]);
            }
        }

        return back()->with('success', "Setting saved successfully.");
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
