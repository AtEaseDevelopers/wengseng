<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductCategoriesController extends Controller
{
    public function index()
    {
        return view('admin.products.categories.index');
    }

    public function create()
    {
        return view('admin.products.categories.create');
    }

    public function store(Request $request)
    {
        $this->validate(
            $request, [
                'category_name' => 'required',
                'excel_sheet_batch' => 'nullable|integer',
            ]
        );

        ProductCategory::create(
            [
                'category_name' => $request['category_name'],
                'excel_sheet_batch' => $request['excel_sheet_batch'],
            ]
        );

        return redirect(route('admin.product-categories.index'))->with('success', "Product category has been added successfully.");
    }

    public function edit($id)
    {
        $data['category'] = ProductCategory::findorFail(decrypt($id));
        return view('admin.products.categories.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate(
            $request, [
                'category_name' => 'required',
                'excel_sheet_batch' => 'nullable|integer',
            ]
        );

        ProductCategory::where('id', decrypt($id))->update(
            [
                'category_name' => $request['category_name'],
                'excel_sheet_batch' => $request['excel_sheet_batch'],
            ]
        );

        return redirect(route('admin.product-categories.index'))->with('success', "Product category has been updated successfully.");
    }

    public function destroy($id)
    {
        $cat_id = decrypt($id);
        $in_use = DB::table('products')->where('product_category_id', $cat_id)->exists();
        if ($in_use) {
            return redirect(route('admin.product-categories.index'))->with('warning', "Product category cannot be deleted as it is being used.");
        }
        DB::table('product_categories')->where('id', $cat_id)->delete();
        
        return redirect(route('admin.product-categories.index'))->with('success', "Product category has been deleted successfully.");
    }

    public function fetch_categories(Request $request)
    {
        $columns = array('id', 'options', 'category_name', 'excel_sheet_batch', 'total_products', 'created_at');
        $totalRecords = DB::table('product_categories')->count();
        $totalFiltered = $totalRecords;
        if ($request->input('length') == -1) {
            $limit =  $totalRecords;
        } else {
            $limit = $request->input('length');
        }
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
            $records = DB::table('product_categories')
                ->select(
                    'id', 'category_name', 'excel_sheet_batch', 'created_at',
                    DB::raw('(SELECT COUNT(`id`) FROM products WHERE products.product_category_id = product_categories.id) as total_products')
                )
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
        } else {
            $search = $request->input('search.value');
            $records = DB::table('product_categories')
                ->select(
                    'id', 'category_name', 'excel_sheet_batch', 'created_at',
                    DB::raw('(SELECT COUNT(`id`) FROM products WHERE products.product_category_id = product_categories.id) as total_products')
                )
                ->where('category_name', 'LIKE', "%{$search}%")
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $totalFiltered = $records->count();
        }

        $data = array();
        if (!empty($records)) {
            foreach ($records as $record) {
                $nestedData['id'] = $record->id;
                $nestedData['category_name'] = $record->category_name;
                $nestedData['excel_sheet_batch'] = $record->excel_sheet_batch ?? '-';
                $nestedData['total_products'] = $record->total_products;
                $nestedData['created_at'] = date('d-m-Y', strtotime($record->created_at));
                $nestedData['options'] = '
                    <div class="d-flex gap-1">
                        <a href="' . route('admin.product-categories.edit', encrypt($record->id)) . '" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#delete" data-action="' . route('admin.product-categories.destroy', encrypt($record->id)) . '"><i class="fa fa-trash"></i></button>
                    </div>
                ';

                $data[] = $nestedData;
            }
        }

        $json_data = array(
            "draw"              => intval($request->input('draw')),
            "recordsTotal"      => intval($totalRecords),
            "recordsFiltered"   => intval($totalFiltered),
            "data"              => $data,
        );

        echo json_encode($json_data);
    }
}
