<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Exports\AdminCustomerExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use App\CustomerOptionItem;
use App\ProductVisibility;
use App\CustomerOption;
use Carbon\Carbon;
use App\System;
use App\Helper;
use App\User;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_admin');
    }

    public function index(Request $request)
    {
        // Filtered data
        $name = $request['name'];
        $email = $request['email'];
        $category = $request['category'];
        $area = $request['area'];
        $shipping_state = $request['shipping_state'];
        $status = $request['status'];

        $users = DB::table('users')
            ->leftJoin('customer_categories', 'customer_categories.id', '=', 'users.customer_category_id')
            ->leftJoin('areas', 'areas.id', '=', 'users.area')
            ->select(
                'customer_categories.category_name',
                'users.id',
                'users.name',
                'users.sql_customer_code',
                'users.email',
                'areas.area_name as area',
                'users.billing_address',
                'users.shipping_address',
                'users.login_code',
                'users.status',
                'users.created_at',
                'users.updated_at',
            )
            ->when(($name != null), function ($q) use ($name) {
                return $q->where('users.name', 'LIKE', "%$name%");
            })
            ->when(($email != null), function ($q) use ($email) {
                return $q->where('users.email', 'LIKE', "%$email%");
            })
            ->when(($category != null), function ($q) use ($category) {
                return $q->where('users.customer_category_id', $category);
            })
            ->when(($area != null), function ($q) use ($area) {
                return $q->where('users.area', $area);
            })
            ->when(($shipping_state != null), function ($q) use ($shipping_state) {
                return $q->where('users.shipping_state', $shipping_state);
            })
            ->when(($status != null), function ($q) use ($status) {
                return $q->where('users.status', $status);
            })
            ->paginate(15);

        $areas = DB::table('areas')->select('id', 'area_name')->get()->toArray();
        $customer_categories = DB::table('customer_categories')
            ->select('id', 'category_name')
            ->get()
            ->toArray();

        return view('admin.customers.index', [
                'categories' => $customer_categories,
                'users' => $users,
                'areas' => $areas,
                'input' => $request->all(),
                'query_params' => Helper::query_params($request->input()),
                'shipping_state_options' => System::$country_state['MY'],
            ]
        );
    }

    public function create()
    {
        $customer_categories = DB::table('customer_categories')
            ->select('id', 'category_name')
            ->get()
            ->toArray();

        $areas = DB::table('areas')->select('id', 'area_name')->get()->toArray();
        $products = DB::table('products')->select('id', 'name', 'sku')->get()->toArray();
        $drivers = DB::table('drivers')->select('id', 'lorry_number')->get()->toArray();

        return view('admin.customers.create', [
                'products' => $products,
                'drivers' => $drivers,
                'areas' => $areas,
                'categories' => $customer_categories,
                'payment_method_options' => User::$payment_method,
                'shipping_state_options' => System::$country_state['MY'],
            ]
        );
    }

    public function store(Request $request)
    {
        $data = $this->validate_store_Customer($request);

        if (isset($data['error']) && $data['error']) {
            return back()->withInput()->withErrors($data['field_err']);
        }

        // generate login code for specific user, unique for every user
        do {
            $login_code = Helper::generateRandomString(100);
            $exist = User::where('login_code', $login_code)->exists();
        } while ($exist);
        
        $default_password = "ecommerce123";
        $customer = User::create(
            [
                "name" => $data['name'],
                "email" => $data['email'] ?? null,
                "attn_name" => $data['attn_name'],
                "attn_contact" => $data['attn_contact'],
                "payment_method" => isset($data['payment_method']) ? json_encode($data['payment_method']) : null,
                "login_code" => $login_code,
                "area" => $request['area_id'],
                "customer_category_id" => $request['customer_category_id'] ?? 0,
                "billing_address" => $data['billing_address'],
                "billing_city" => $request['billing_city'] ?? null,
                "shipping_city" => $request['shipping_city'] ?? null,
                "billing_postcode" => $data['billing_postcode'] ?? null,
                "billing_state" => $data['billing_state'] ?? null,
                "shipping_address" => $data['shipping_address'] ?? '',
                "shipping_postcode" => $data['shipping_postcode'] ?? '',
                "shipping_state" => $data['shipping_state'] ?? '',
                "fax_no" => $data['fax_no'] ?? '',
                "password" => Hash::make($default_password),
                "status" => User::$user_status['active'],
                "remark" => $data['remark'],
                "price_permission" => $request['price_permission'] ?? 0,
                "invoice_visibility" => $request['invoice_visibility'] ?? 0,
                "invoice_price_permission" => $request['invoice_price_permission'] ?? 0,
                "default_driver_id" => $request['default_driver_id'],
                'sql_customer_code' => $request['sql_customer_code'] ?? null,
            ]
        );

        if ($request['product_id']) {
            foreach ($request['product_id'] as $pid) {
                ProductVisibility::create([
                    'user_id' => $customer->id, 
                    'product_id' => $pid,
                ]);
            }
        }

        return redirect(route('admin.customers'))->with('success', "$customer->name has been added successfully. Default login password is $default_password.");

    }

    private function validate_store_Customer(Request $request)
    {
        $rules = [
            "name" => array_merge(User::$attribute_rules['name'], []),
            "email" => array_merge(User::$attribute_rules['email'], ['unique:users,email']),
            "attn_name" => array_merge(User::$attribute_rules['attn_name'], []),
            "attn_contact" => array_merge(User::$attribute_rules['attn_contact'], []),
            // "payment_method" => array_merge(User::$attribute_rules['payment_method'], []),
            "payment_method" => ['nullable'],
            "billing_address" => array_merge(User::$attribute_rules['billing_address'], []),
            // "billing_postcode" => array_merge(User::$attribute_rules['billing_postcode'], []),
            'billing_postcode' => ['nullable'],
            // "billing_state" => array_merge(User::$attribute_rules['billing_state'], []),
            'billing_state' => ['nullable'],
            "shipping_address" => array_merge(User::$attribute_rules['shipping_address'], []),
            // "shipping_postcode" => array_merge(User::$attribute_rules['shipping_postcode'], []),
            'shipping_postcode' => ['nullable'],
            // "shipping_state" => array_merge(User::$attribute_rules['shipping_state'], []),
            'shipping_state' => ['nullable'],
            "remark" => array_merge(User::$attribute_rules['remark'], []),
            "fax_no" => array_merge(User::$attribute_rules['fax_no'], []),

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

    public function edit(Request $request, $id)
    {
        $customer = User::find(decrypt($id));
        $customer_categories = DB::table('customer_categories')
            ->select('id', 'category_name')
            ->get()
            ->toArray();

        $customer->payment_method = json_decode($customer->payment_method??"[]", true);
        $products = DB::table('products')->select('id', 'name', 'sku')->get()->toArray();
        $drivers = DB::table('drivers')->select('id', 'lorry_number')->get()->toArray();
        $areas = DB::table('areas')->select('id', 'area_name')->get()->toArray();

        $product_visibilities = DB::table('product_visibilities')
            ->join('products', 'products.id', '=', 'product_visibilities.product_id')
            ->select('product_id', 'products.name as product_name', 'product_visibilities.id')
            ->where('product_visibilities.user_id', $customer->id)
            ->where('products.status', '=', 'active')
            ->get()
            ->toArray();


        return view(
            'admin.customers.edit', [
                'customer' => $customer,
                'drivers' => $drivers,
                'areas' => $areas,
                'products' => $products,
                'product_visibilities' => $product_visibilities,
                'categories' => $customer_categories,
                'payment_method_options' => User::$payment_method,
                'shipping_state_options' => System::$country_state['MY'],
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $customer = User::find(decrypt($id));

        $data = $this->validate_update_customer($request, $customer);
        if (isset($data['error']) && $data['error']) {
            return redirect()->back()->withInput()->withErrors($data['field_err']);
        }
        
        $customer->fill(
            [
                "name" => $data['name'],
                "email" => $data['email'] ?? null,
                "attn_name" => $data['attn_name'],
                "attn_contact" => $data['attn_contact'],
                "payment_method" => isset($data['payment_method']) ? json_encode($data['payment_method']) : null,
                "area" => $request['area_id'],
                "billing_address" => $data['billing_address'],
                "billing_city" => $request['billing_city'] ?? null,
                "shipping_city" => $request['shipping_city'] ?? null,
                "billing_postcode" => $data['billing_postcode'] ?? null,
                "billing_state" => $data['billing_state'] ?? null,
                "shipping_address" => $data['shipping_address'] ?? "",
                "shipping_postcode" => $data['shipping_postcode'] ?? "",
                "shipping_state" => $data['shipping_state'] ?? "",
                "status" => User::$user_status['active'],
                "remark" => $data['remark'],
                "price_permission" => $request['price_permission'] ?? 0,
                "invoice_visibility" => $request['invoice_visibility'] ?? 0,
                "invoice_price_permission" => $request['invoice_price_permission'] ?? 0,
                "default_driver_id" => $request['default_driver_id'] ?? null,
                'sql_customer_code' => $request['sql_customer_code'] ?? null,
                'customer_category_id' => $request['customer_category_id'] ?? 0,
                "fax_no" => $data['fax_no'] ?? null,

            ]
        )->save();

        if ($request['product_id']) {
            foreach ($request['product_id'] as $pid) {
                ProductVisibility::updateOrCreate([
                    'user_id' => $customer->id, 
                    'product_id' => $pid,
                ]);
            }
        }

        return redirect(route('admin.customers.edit', encrypt($customer->id)))->with('success', "$customer->name has been updated successfully.");
    }

    public function updatePassword(Request $request)
    {
        $customer = User::findorfail(decrypt($request['id']));
        $data = $this->validate_update_password($request, $customer);
        if (isset($data['error']) && $data['error']) {
            return back()->withInput()->withErrors($data['field_err']);
        }

        // generate login code for specific user, unique for every user
        do {
            $login_code = Helper::generateRandomString(100);
            $exist = User::where('login_code', $login_code)->exists();
        } while ($exist);

        $customer->fill(
            [
                "password" => Hash::make($data['new_password']),
                "login_code" => $login_code,
            ]
        )->save();

        return redirect(route('admin.customers.edit', encrypt($customer->id)))->with('success', "$customer->name login password & fast-login link has been updated successfully.");

    }

    private function validate_update_password(Request $request, User $customer)
    {
        $rules = [
            "new_password" => ['required', 'string', 'max:100'],
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

    private function validate_update_customer(Request $request, User $customer)
    {
        $rules = [
            "name" => ['required', 'unique:users,name,'.$customer->id, 'string', 'max:100'],
            "email" => array_merge(User::$attribute_rules['email'], ['unique:users,email,'.$customer->id]),
            "attn_name" => array_merge(User::$attribute_rules['attn_name'], []),
            "attn_contact" => array_merge(User::$attribute_rules['attn_contact'], []),
            // "payment_method" => array_merge(User::$attribute_rules['payment_method'], []),
            "payment_method" => ['nullable'],
            "billing_address" => array_merge(User::$attribute_rules['billing_address'], []),
            // "billing_postcode" => array_merge(User::$attribute_rules['billing_postcode'], []),
            'billing_postcode' => ['nullable'],
            // "billing_state" => array_merge(User::$attribute_rules['billing_state'], []),
            'billing_state' => ['nullable'],
            'shipping_address' => array_merge(User::$attribute_rules['shipping_address'], []),
            // "shipping_postcode" => array_merge(User::$attribute_rules['shipping_postcode'], []),
            'shipping_postcode' => ['nullable'],
            // "shipping_state" => array_merge(User::$attribute_rules['shipping_state'], []),
            'shipping_state' => ['nullable'],
            "remark" => array_merge(User::$attribute_rules['remark'], []),
            "fax_no" => array_merge(User::$attribute_rules['fax_no'], []),
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

    public function generateNewLoginLink(Request $request, User $customer)
    {
        // generate login code for specific user, unique for every user
        do {
            $login_code = Helper::generateRandomString(100);
            $exist = User::where('login_code', $login_code)->exists();
        } while ($exist);

        $customer->fill(
            [
            "login_code" => $login_code,
            ]
        )->save();

        return redirect(route('admin.customers'))->with('success', "New login link has been generated for $customer->name.");
    }

    public function export(Request $request)
    {
        // Filtered data
        $name = $request['name'];
        $email = $request['email'];
        $category = $request['category'];
        $shipping_state = $request['shipping_state'];
        $status = $request['status'];

        $users = DB::table('users')
            ->leftJoin('customer_categories', 'customer_categories.id', '=', 'users.customer_category_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'customer_categories.category_name',
                'users.shipping_address',
                'users.shipping_postcode',
                'users.shipping_state',
                'users.remark',
                'users.status',
                'users.created_at',
            )
            ->when(($name != null), function ($q) use ($name) {
                return $q->where('users.name', 'LIKE', "%$name%");
            })
            ->when(($email != null), function ($q) use ($email) {
                return $q->where('users.email', 'LIKE', "%$email%");
            })
            ->when(($category != null), function ($q) use ($category) {
                return $q->where('users.customer_category_id', $category);
            })
            ->when(($shipping_state != null), function ($q) use ($shipping_state) {
                return $q->where('users.shipping_state', $shipping_state);
            })
            ->when(($status != null), function ($q) use ($status) {
                return $q->where('users.status', $status);
            })
            ->get();


        $header = ['No', 'Name', 'Email', 'Customer Category', 'Shipping Address', 'Shipping Postcode', 'Shipping State', 'remark', 'Status', 'Created At'];

        return Excel::download(new AdminCustomerExport($users, $header), Carbon::now()->format('YmdHis').'-Customer-List.xlsx');
    }

    public function deleteCustomerProduct(Request $request)
    {
        ProductVisibility::where('id', $request['id'])->delete();
        return response()->json([]);
    }

    public function import_customers()
    {
        return view('admin.customers.import_customers');
    }

    public function import_customers_submit(Request $request)
    {
        $request->validate(
            [
                'file' => 'required|file|mimes:xlsx,csv',
            ]
        );

        Excel::import(new CustomersImport, $request->file('file'));
        try {
            // Import the file with transaction handling inside the import
            return back();
        } catch (\Exception $e) {
            // Catch the exception thrown during the import process and display it
            return back();
        }
    }
}
