<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BarangImportSheetExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $request;
    protected $users;

    public function __construct($request)
    {
        $this->request  = $request;

        $query = DB::table('users')
            ->join('orders', 'orders.user_id', '=', 'users.id')
            ->join('order_products', 'order_products.order_id', '=', 'orders.id')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->where('products.product_category_id', 30)
            ->where(function ($q) {
                $q->where('order_products.quantity', '>', 0)
                ->orWhere('order_products.weight', '>', 0);
            })
            ->when($this->request->status, function ($q) {
                $q->where('orders.status', $this->request->status);
            })
            ->when($this->request->fdate, function ($q) {
                $q->whereDate('orders.delivering_date', $this->request->fdate);
            })
            ->where('orders.status', '!=', 'cancelled');

        $this->users = $query->select('users.id', 'users.sql_customer_code', 'users.name')
            ->distinct()
            ->orderBy('users.sql_customer_code', 'asc')
            ->get();
    }

    public function collection()
    {
        $products = DB::table('products')
            ->join('uoms', 'uoms.id', '=', 'products.uom_id')
            ->join('order_products', 'order_products.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->where('products.product_category_id', 30)
            ->where(function ($q) {
                $q->where('order_products.quantity', '>', 0)
                ->orWhere('order_products.weight', '>', 0);
            })
            ->when($this->request->status, function ($q) {
                $q->where('orders.status', $this->request->status);
            })
            ->where('orders.status', '!=', 'cancelled')
            ->when($this->request->fdate, function ($q) {
                $q->whereDate('orders.delivering_date', $this->request->fdate);
            })
            ->select('products.id', 'products.name', 'uoms.uom_name')
            ->distinct()
            ->orderBy('products.name', 'asc')
            ->get();

        // Receive
        $receive_product_ids = DB::table('product_receive_quantities')->where('date', Carbon::parse($this->request->fdate)->subDays(1)->format('Y-m-d'))->where('qty', '>', 0)->pluck('product_id')->toArray();
        $receive_products = DB::table('products')
            ->join('uoms', 'uoms.id', '=', 'products.uom_id')
            ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->whereIn('products.id', $receive_product_ids)
            ->where('product_categories.category_name', 'like', '%import%')
            ->select('products.id', 'products.name', 'uoms.uom_name')
            ->distinct()
            ->orderBy('products.name', 'asc')
            ->get();

        $new_products = collect();
        $new_products = $new_products->merge($products);
        for ($i = 0; $i < count($receive_products); $i++) {
            $found_product = false;
            for ($j = 0; $j < count($products); $j++) {
                if ($receive_products[$i]->id == $products[$j]->id) {
                    $found_product = true;
                    break;
                }
            }
            if (!$found_product) {
                $new_products->push($receive_products[$i]);
            }
        }
        $products = $new_products;
        // Balance
        $balance_product_ids = DB::table('product_balance_quantities')
            ->where('date', Carbon::parse($this->request->fdate)->subDays(2)->format('Y-m-d'))
            ->where('qty', '>', 0)
            ->pluck('product_id')->toArray();
        $balance_products = DB::table('products')
            ->join('uoms', 'uoms.id', '=', 'products.uom_id')
            ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->whereIn('products.id', $balance_product_ids)
            ->where('product_categories.category_name', 'like', '%import%')
            ->select('products.id', 'products.name', 'uoms.uom_name')
            ->distinct()
            ->orderBy('products.name', 'asc')
            ->get();
            
        $new_products = collect();
        $new_products = $new_products->merge($products);
        for ($i = 0; $i < count($balance_products); $i++) {
            $found_product = false;
            for ($j = 0; $j < count($products); $j++) {
                if ($balance_products[$i]->id == $products[$j]->id) {
                    $found_product = true;
                    break;
                }
            }
            if (!$found_product) {
                $new_products->push($balance_products[$i]);
            }
        }
        $products = $new_products;


        $rows = [];
        $customerTotals = [];
        $grandTotal = 0;

        foreach ($this->users as $user) {
            $userKey = $user->sql_customer_code ?? $user->name;
            $customerTotals[$userKey] = 0;
        }
        foreach ($products as $product) {
            $receive_qty = DB::table('product_receive_quantities')
                ->select('qty', 'remark')
                ->where('product_id', $product->id)
                ->where('date', Carbon::parse($this->request->fdate)->subDays(1)->format('Y-m-d'))
                ->first();
            $balance_qty = DB::table('product_balance_quantities')
                ->select('qty', 'remark')
                ->where('product_id', $product->id)
                ->where('date', Carbon::parse($this->request->fdate)->subDays(2)->format('Y-m-d'))
                ->first();
            $row = ['Product' => $product->name, 'Receive' => $receive_qty->qty ?? 0, 'Receive Remark' => $receive_qty->remark ?? null, 'Balance' => $balance_qty->qty ?? 0, 'Balance Remark' => $balance_qty->remark ?? null, 'UOM' => $product->uom_name];
            $productTotal = 0;
            $hasData = false;

            foreach ($this->users as $user) {
                $userKey = $user->sql_customer_code ?? $user->name;

                $qty = DB::table('order_products')
                    ->join('orders', 'orders.id', '=', 'order_products.order_id')
                    ->where('orders.user_id', $user->id)
                    ->where('order_products.product_id', $product->id)
                    ->when($this->request->status, function ($q) {
                        $q->where('orders.status', $this->request->status);
                    })
                    ->where('orders.status', '!=', 'cancelled')
                    ->when($this->request->fdate, function ($q) {
                        $q->whereDate('orders.delivering_date', $this->request->fdate);
                    })
                    ->sum(DB::raw('COALESCE(order_products.quantity, 0)'));

                $qty = $qty ?: 0;
                $row[$userKey] = $qty;

                if ($qty > 0) {
                    $hasData = true;
                    $productTotal += $qty;
                    $customerTotals[$userKey] += $qty;
                    $grandTotal += $qty;
                }
            }
            if (count($this->users) <= 0 && count($products) > 0) {
                $hasData = true;
            }

            // if ($hasData) {
                $row['Total'] = $productTotal;
                $rows[] = $row;
            // }
        }

        if (count($rows) > 0) {
            $totalsRow = ['Product' => 'Total', 'Receice' => '', 'Receive Remark' => '', 'Balance' => '', 'Balance Remark' => '', 'UOM' => ''];
            foreach ($this->users as $user) {
                $userKey = $user->sql_customer_code ?? $user->name;
                $totalsRow[$userKey] = $customerTotals[$userKey];
            }
            $totalsRow['Total'] = $grandTotal;
            $rows[] = $totalsRow;
        }

        return collect($rows);
    }

    public function headings(): array
    {
        $userCodes = array_map(fn($u) => $u->sql_customer_code ?? $u->name, $this->users->toArray());
        
        return [
            ['Date', '', $this->request->fdate, ''],
            ['', '', '', ''],
            array_merge(['Product', 'Receive', 'Receive Remark', 'Balance', 'Balance Remark', 'UOM'], $userCodes, ['Total']),
        ];
    }

    public function title(): string
    {
        return 'Barang Import';
    }

    public function styles(Worksheet $sheet)
    {
        $data = $this->collection();
        $rowCount = $data->count();

        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            3 => ['font' => ['bold' => true]],
        ];

        // if ($rowCount > 0) {
        //     $styles[$rowCount + 5] = ['font' => ['bold' => true]];
        // }

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 15,
        ];
    }
}
