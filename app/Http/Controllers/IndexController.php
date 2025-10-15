<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Product;
use App\User;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    public function get_products_list(Request $request)
    {
        $id = $request['id'];
        if ($id != 'products_visibility') {
            $customer = User::find($id);
        }
        $pricing_date = $request['pricing_date'] ?? date('Y-m-d');

        $categoryPriceSelect = $id == 'products_visibility' 
            ? DB::raw('0 as category_price')
            : DB::raw("(
                SELECT pdp.price 
                FROM product_default_pricings pdp
                WHERE pdp.product_id = products.id 
                AND pdp.customer_category_id = (
                    SELECT u.customer_category_id 
                    FROM users u 
                    WHERE u.id = {$id}
                )
            ) as category_price");

        $products = DB::table('products')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.images',
                'products.price',
                'products.sell_in',
                'uoms.uom_name',
                DB::raw("(SELECT product_daily_prices.price 
                    FROM product_daily_prices 
                    WHERE product_daily_prices.product_id = products.id 
                    AND product_daily_prices.date = '" . $pricing_date . "' 
                    LIMIT 1) as daily_price"
                ),
                $categoryPriceSelect,
                DB::raw('(
                    SELECT JSON_OBJECTAGG(
                        po.name, 
                        JSON_OBJECT(
                            "mandatory", po.mandatory,
                            "items", (
                                SELECT JSON_ARRAYAGG(poi.name)
                                FROM product_option_items poi
                                WHERE poi.product_option_id = po.id
                                AND poi.product_id = products.id
                                AND poi.status = "active"
                            )
                        )
                    )
                        FROM product_options po
                        WHERE po.product_id = products.id
                        AND po.status = "active"
                    ) as product_options'
                )
            )
            ->leftJoin('uoms', 'uoms.id', 'products.uom_id')
            ->where('status', Product::$status['active'])
            ->get()
            ->toArray();

        $image_path = url('/') . '/' . Product::$path . "/";

        $view = view('partials.products_list', compact('products', 'id', 'image_path'))->render();
        return Response::json(['success' => true, 'view' => $view]);
    }
}
