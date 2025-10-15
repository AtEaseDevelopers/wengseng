<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AdminProductExport;
use Illuminate\Support\Facades\DB;
use App\ProductDefaultPricing;
use Illuminate\Http\Request;
use App\ProductOptionItem;
use App\ProductOption;
use Carbon\Carbon;
use App\Product;
use App\Helper;
use App\Http\Requests\ImportProductRequest;
use App\Imports\ProductsImport;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_admin');
    }

    public function index(Request $request)
    {
        $products = DB::table('products')
            ->leftJoin('uoms', 'uoms.id', '=', 'products.uom_id')
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->select(
                'uom_name',
                'category_name',
                'products.id',
                'name',
                'images',
                'sku',
                'price',
                'description',
                'status',
                'products.updated_at',
                'products.created_at',
            )
            ->where('status', '!=', Product::$status['removed']);

        if (!empty($request['product_category_id'])) {
            $products->where('product_category_id', "{$request['product_category_id']}");
        }

        if (!empty($request['uom_id'])) {
            $products->where('uom_id', "{$request['uom_id']}");
        }

        if (!empty($request['sku'])) {
            $products->where('sku', 'LIKE', "%{$request['sku']}%");
        }

        if (!empty($request['name'])) {
            $products->where('name', 'LIKE', "%{$request['name']}%");
        }

        if (!empty($request['status'])) {
            $products->where('status', $request['status']);
        }

        if (!empty($request['price_range'])) {
            $price_range = $request['price_range'];
            
            $filter_price_range = explode(',', $price_range);
            $from_price = $filter_price_range[0];
            $to_price = $filter_price_range[1];
            
            $products->where('price', '>=', $from_price);
            $products->where('price', '<=', $to_price);
        }

        if (!empty($request['fdate'])) {
            $products->where('products.created_at', '>=', $request['fdate']);
        }

        if (!empty($request['tdate'])) {
            $products->where('products.created_at', '<=', $request['tdate'] . " 23:59:59");
        }

        $products = $products->paginate(15);
        $minPrice = Product::min('price');
        $maxPrice = Product::max('price');

        $product_path = Product::$path;

        $uoms = DB::table('uoms')
            ->select('id', 'uom_name')
            ->get()
            ->toArray();

        $product_categories = DB::table('product_categories')
            ->select('id', 'category_name')
            ->get()
            ->toArray();
        
        return view('admin.products.index', [
                'product_path' => $product_path,
                'products' => $products,
                'uoms' => $uoms,
                'product_categories' => $product_categories,
                'input' => $request->all() + ['min_price' => $minPrice, 'max_price' => $maxPrice, 'from_price' => $from_price ?? $minPrice, 'to_price' => $to_price ?? $maxPrice],
                'query_params' => Helper::query_params($request->input()),
            ]
        );
    }

    public function create()
    {
        $data['uoms'] = DB::table('uoms')->select('id', 'uom_name')->get()->toArray();
        $data['product_categories'] = DB::table('product_categories')->select('id', 'category_name')->get()->toArray();
        $data['categories'] = DB::table('customer_categories')
            ->select('id', 'category_name')
            ->get()
            ->toArray();

        return view('admin.products.create', $data);
    }

    public function store(Request $request)
    {
        $data = $this->validate_store_product($request);
        if (isset($data['error']) && $data['error']) {
            return redirect()->back()->withInput()->withErrors($data['field_err']);
        }
        
        $product = Product::create(
            [
                'uom_id' => $data['uom_id'],
                'product_category_id' => $data['product_category_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'sku' => $data['sku'],
                'price' => $data['price'],
                'weight' => $request['weight'],
                'status' => $data['status'],
                'remark' => $data['remark'],
                'nos' => $data['nos'],
                'show_weight' => isset($data['show_weight']),
                'show_qty' => isset($data['show_qty']),
                'sell_in' => $data['sell_in'],
            ]
        );

        // process images
        $images = [];
        if (isset($data['images']) && $data['images']) {
            do {
                $extension = $data['images']->getClientOriginalExtension();
                $filename = time().rand().".".$extension;
                $path = Product::$path.'/'.$product->id;
            } while (Storage::disk('local')->exists($path."/".$filename));
            
            Storage::disk('local')->put($path."/".$filename, file_get_contents($data['images']));
            $images[] = $filename;
        }

        if ($images) {
            $product->fill(
                [
                    'images' => json_encode($images)
                ]
            )->save();
        }

        // process product option
        if (isset($data['product_option']) && $data['product_option']) {
            // add into product option and product option items
            foreach ($data['product_option'] as $product_option => $option_items) {
                $product_option = ProductOption::create(
                    [
                        'product_id' => $product->id,
                        'name' => $product_option,
                        'mandatory' => $data['product_option_mandatory'][$product_option]? 1 : 0,
                        'status' => ProductOption::$status['active'],
                    ]
                );
                $option_items_arr = explode(',', $option_items);
                foreach ($option_items_arr as $item_value) {
                    $product_option_item = ProductOptionItem::create(
                        [
                            'product_id' => $product->id,
                            'product_option_id' => $product_option->id,
                            'name' => trim($item_value),
                            'status' => ProductOptionItem::$status['active'],
                        ]
                    );
                }
            }
        }

        $categoryPrices = $request->input('category_prices');
        if ($categoryPrices) {
            foreach ($categoryPrices as $categoryId => $price) {
                ProductDefaultPricing::create(
                    [
                        'product_id' => $product->id,
                        'customer_category_id' => $categoryId,
                        'price' => $price,
                    ]
                );
            }
        }

        return redirect(route('admin.products'))->with('success', "$product->name has been added successfully.");
    }

    public function edit($id)
    {
        $product = Product::find(decrypt($id));
        $image = json_decode($product->images, true);
        if ($image != null) {
            $product->image_url = url('/') . '/' . Product::$path."/".$product->id."/".$image[0];
        }
        $product->product_option = Product::getOption($product->id);
        $uoms = DB::table('uoms')->select('id', 'uom_name')->get()->toArray();
        $product_categories = DB::table('product_categories')->select('id', 'category_name')->get()->toArray();

        $categories = DB::table('customer_categories')
            ->select('id', 'category_name')
            ->get()
            ->toArray();

        $product_prices = DB::table('product_default_pricings')
            ->where('product_id', $product->id)
            ->pluck('price', 'customer_category_id')
            ->toArray();

        return view(
            'admin.products.edit', [
                'categories' => $categories,
                'product_prices' => $product_prices,
                'product' => $product,
                'uoms' => $uoms,
                'product_categories' => $product_categories,
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $data = $this->validate_update_product($request);
        $product = Product::find(decrypt($id));

        if (isset($data['error']) && $data['error']) {
            return redirect()->back()->withInput()->withErrors($data['field_err']);
        }

        $product->fill(
            [
                'uom_id' => $data['uom_id'],
                'product_category_id' => $data['product_category_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'sku' => $data['sku'],
                'price' => $data['price'],
                'weight' => $request['weight'],
                'status' => $data['status'],
                'remark' => $data['remark'],
                'nos' => $data['nos'],
                'show_weight' => isset($data['show_weight']),
                'show_qty' => isset($data['show_qty']),
                'sell_in' => $data['sell_in'],
            ]
        )->save();

        // process images
        $images = [];
        if (isset($data['images']) && $data['images']) {
            do {
                $extension = $data['images']->getClientOriginalExtension();
                $filename = time().rand().".".$extension;
                $path = Product::$path.'/'.$product->id;
            } while (Storage::disk('local')->exists($path."/".$filename));
            
            Storage::disk('local')->put($path."/".$filename, file_get_contents($data['images']));
            $images[] = $filename;
        }

        if ($images) {
            $product->fill(
                [
                'images' => json_encode($images)
                ]
            )->save();
        }

        // process product option
        // remove all previous added product option, add new when user update product
        ProductOption::where('product_id', $product->id)->update(['status' => ProductOption::$status['removed']]);
        ProductOptionItem::where('product_id', $product->id)->update(['status' => ProductOptionItem::$status['removed']]);
        if (isset($data['product_option']) && $data['product_option']) {
            // add into product option and product option items
            foreach ($data['product_option'] as $product_option => $option_items) {
                $product_option = ProductOption::create(
                    [
                        'product_id' => $product->id,
                        'name' => $product_option,
                        'mandatory' => $data['product_option_mandatory'][$product_option]? 1 : 0,
                        'status' => ProductOption::$status['active'],
                    ]
                );

                $option_items_arr = explode(',', $option_items);
                foreach ($option_items_arr as $item_value) {
                    $product_option_item = ProductOptionItem::create(
                        [
                            'product_id' => $product->id,
                            'product_option_id' => $product_option->id,
                            'name' => trim($item_value),
                            'status' => ProductOptionItem::$status['active'],
                        ]
                    );
                }
            }
        }

        $categoryPrices = $request->input('category_prices');
        if ($categoryPrices) {
            foreach ($categoryPrices as $categoryId => $price) {
                ProductDefaultPricing::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'customer_category_id' => $categoryId,
                    ],
                    [
                        'price' => $price,
                    ]
                );
            }
        }

        return redirect(route('admin.products.edit', encrypt($product->id)))->with('success', "$product->name has been updated successfully.");
    }

    private function validate_update_product(Request $request)
    {
        $rules = [
            "images" => [
                'nullable', 
                'file',
                'mimes:jpeg,png,jpg',
                'max:4096'
            ],
            "name" => array_merge(Product::$attribute_rules['name'], []),
            "description" => array_merge(Product::$attribute_rules['description'], []),
            "sku" => array_merge(Product::$attribute_rules['sku'], []),
            "price" => array_merge(Product::$attribute_rules['price'], []),
            "status" => array_merge(Product::$attribute_rules['status'], []),
            "product_option" => ['nullable'],
            "product_option_mandatory" => ['nullable'],
            'remark' => ['nullable'],
            'nos' => ['nullable'],
            'show_weight' => ['nullable'],
            'show_qty' => ['nullable'],
            'sell_in' => ['required'],
            'uom_id' => ['required'],
            'product_category_id' => ['required'],
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

    public function removeProduct(Request $request, Product $product)
    {
        $product->update(
            [
            'status' => Product::$status['removed']
            ]
        );

        return redirect()->to('/admin/products')->with('success', "$product->name has been removed successfully.");
    }

    private function validate_store_product(Request $request)
    {
        $rules = [
            "images" => array_merge(Product::$attribute_rules['images'], []),
            "name" => array_merge(Product::$attribute_rules['name'], []),
            "description" => array_merge(Product::$attribute_rules['description'], []),
            "sku" => array_merge(Product::$attribute_rules['sku'], []),
            "price" => array_merge(Product::$attribute_rules['price'], []),
            "status" => array_merge(Product::$attribute_rules['status'], []),
            "product_option" => ['nullable'],
            "product_option_mandatory" => ['nullable'],
            'remark' => ['nullable'],
            'nos' => ['nullable'],
            'show_weight' => ['nullable'],
            'show_qty' => ['nullable'],
            'sell_in' => ['required'],
            'uom_id' => ['required'],
            'product_category_id' => ['required'],
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

    public function export(Request $request)
    {
        $products = Product::select('id', 'name', 'description', 'price', 'status', 'created_at');
        
        if ($filter_sku = $request->input('sku')) {
            $products->where('sku', 'LIKE', "%$filter_sku%");
        }

        if ($filter_name = $request->input('name')) {
            $products->where('name', 'LIKE', "%$filter_name%");
        }

        if ($filter_status = $request->input('status')) {
            $products->where('status', $filter_status);
        }

        if ($filter_price_range = $request->input('price_range')) {
            $filter_price_range = explode(',', $filter_price_range);
            $from_price = $filter_price_range[0];
            $to_price = $filter_price_range[1];
            $products->where('price', '>=', $from_price);
            $products->where('price', '<=', $to_price);
        }

        $header = ['No', 'Name', 'Description', 'Price', 'Status', 'Created At']; // Adjust the header based on your data model
        return Excel::download(new AdminProductExport($products->get(), $header), Carbon::now()->format('YmdHis').'-Product-List.xlsx');
    }

    public function import()
    {
        return view('admin.products.import');
    }

    public function import_store(ImportProductRequest $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $import = new ProductsImport();
            
            Excel::import($import, $request->file('file'));

            return redirect()->back()
                ->with('success', "Successfully imported products!");
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->withInput();
        }
    }
}
