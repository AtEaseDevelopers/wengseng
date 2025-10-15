<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\CustomerCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerCategoriesController extends Controller
{
    public function index()
    {
        return view('admin.customers.categories.index');
    }

    public function create()
    {
        return view('admin.customers.categories.create');
    }

    public function store(Request $request)
    {
        $this->validate(
            $request, [
                'category_name' => 'required',
            ]
        );

        CustomerCategory::create(
            [
                'category_name' => $request['category_name']
            ]
        );

        return redirect(route('admin.customer-categories.index'))->with('success', "Product category has been added successfully.");
    }

    public function edit($id)
    {
        $data['category'] = CustomerCategory::findorFail(decrypt($id));
        return view('admin.customers.categories.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $this->validate(
            $request, [
                'category_name' => 'required',
            ]
        );

        CustomerCategory::where('id', decrypt($id))->update(
            [
                'category_name' => $request['category_name']
            ]
        );

        return redirect(route('admin.customer-categories.index'))->with('success', "Customer category has been updated successfully.");
    }

    public function destroy($id)
    {
        return redirect(route('admin.customer-categories.index'))->with('success', "Customer category has been deleted successfully.");
    }

    public function fetch_categories(Request $request)
    {
        $columns = array('id', 'options', 'category_name', 'total_products', 'created_at');
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
            $records = DB::table('customer_categories')
                ->select(
                    'id', 'category_name', 'created_at',
                    DB::raw('(SELECT COUNT(`id`) FROM users WHERE users.customer_category_id = customer_categories.id) as total_customers')
                )
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
        } else {
            $search = $request->input('search.value');
            $records = DB::table('customer_categories')
                ->select(
                    'id', 'category_name', 'created_at',
                    DB::raw('(SELECT COUNT(`id`) FROM users WHERE users.customer_category_id = customer_categories.id) as total_customers')
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
                $nestedData['total_customers'] = $record->total_customers;
                $nestedData['created_at'] = date('d-m-Y', strtotime($record->created_at));
                $nestedData['options'] = '
                    <div class="d-flex gap-1">
                        <a href="' . route('admin.customer-categories.edit', encrypt($record->id)) . '" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#delete" data-action="' . route('admin.customer-categories.destroy', encrypt($record->id)) . '"><i class="fa fa-trash"></i></button>
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
