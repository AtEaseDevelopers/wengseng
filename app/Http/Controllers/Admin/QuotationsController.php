<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class QuotationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_admin');
    }

    public function index(Request $request)
    {
        $date = $request->input('date') ?? date('Y-m-d');
        $data['date'] = $date;
        $data['categories'] = DB::table('product_daily_prices')
            ->leftJoin('customer_categories', 'product_daily_prices.user_category', '=', 'customer_categories.id')
            ->select(
                'product_daily_prices.user_category',
                'customer_categories.category_name',
                DB::raw('COUNT(product_daily_prices.product_id) as total_products')
            )
            ->where('product_daily_prices.date', $date)
            ->groupBy('product_daily_prices.user_category', 'customer_categories.category_name')
            ->get();

        return view('admin.quotations.index', $data);
    }

    public function show(Request $request, $id)
    {
        $date = $request['date'];
        $data['date'] = $date;
        $data['category'] = DB::table('customer_categories')
            ->select('category_name')
            ->where('id', decrypt($id))
            ->first();

        $data['products'] = DB::table('product_daily_prices')
            ->join('products', 'products.id', '=', 'product_daily_prices.product_id')
            ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->select(
                'products.name',
                'product_daily_prices.price',
                'product_categories.category_name'
            )
            ->where('product_daily_prices.user_category', decrypt($id))
            ->where('product_daily_prices.date', $date)
            ->where('product_daily_prices.price', '>', 0)
            ->orderBy('product_categories.quotation_summary_sequence', 'asc')
            ->orderBy('product_categories.category_name', 'asc')
            ->orderBy('products.name', 'asc')
            ->get()
            ->groupBy('category_name');

        return view('admin.quotations.show', $data);
    }

    public function copy(Request $request)
    {
        $userCategory = decrypt($request->get('category'));
        $date = $request->get('date');

        $products = DB::table('product_daily_prices')
            ->join('products', 'products.id', '=', 'product_daily_prices.product_id')
            ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->select(
                'products.name',
                'product_daily_prices.price',
                'product_categories.category_name'
            )
            ->where('product_daily_prices.user_category', $userCategory)
            ->where('product_daily_prices.date', $date)
            ->where('product_daily_prices.price', '>', 0)
            ->orderBy('product_categories.category_name', 'asc')
            ->orderBy('products.name', 'asc')
            ->get()
            ->groupBy('category_name');

        return response()->json([
            'category_name' => DB::table('customer_categories')->where('id', $userCategory)->value('category_name') ?? 'No Category',
            'date' => $date,
            'products' => $products,
        ]);
    }
}
