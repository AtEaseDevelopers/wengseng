<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductCategoriesSheetExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $category;
    protected $request;
    protected $users;

    public function __construct($category, $request)
    {
        $this->category = $category;
        $this->request  = $request;

        $query = DB::table('users')
            ->join('orders', 'orders.user_id', '=', 'users.id')
            ->join('order_products', 'order_products.order_id', '=', 'orders.id')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->where('products.product_category_id', $this->category->id)
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
            ->whereNotNull('users.sql_customer_code')
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
            ->where('products.product_category_id', $this->category->id)
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

        $rows = [];
        $customerTotals = [];
        $grandTotal = 0;

        foreach ($this->users as $user) {
            $userKey = $user->sql_customer_code ?? $user->name;
            $customerTotals[$userKey] = 0;
        }

        foreach ($products as $product) {
            $row = ['Product' => $product->name, 'UOM' => $product->uom_name];
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
                    ->sum(DB::raw('COALESCE(order_products.quantity, 0) + COALESCE(order_products.weight, 0)'));

                $qty = $qty ?: 0;
                $row[$userKey] = $qty;

                if ($qty > 0) {
                    $hasData = true;
                    $productTotal += $qty;
                    $customerTotals[$userKey] += $qty;
                    $grandTotal += $qty;
                }
            }

            if ($hasData) {
                $row['Total'] = $productTotal;
                $rows[] = $row;
            }
        }

        if (count($rows) > 0) {
            $totalsRow = ['Product' => 'Total', 'UOM' => ''];
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
            array_merge(['Product', 'UOM'], $userCodes, ['Total']),
        ];
    }

    public function title(): string
    {
        $title = $this->category->category_name;
        return strlen($title) > 31 ? substr($title, 0, 28) . '...' : $title;
    }

    public function styles(Worksheet $sheet)
    {
        $data = $this->collection();
        $rowCount = $data->count();

        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            3 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],
        ];

        if ($rowCount > 0) {
            $styles[$rowCount + 5] = ['font' => ['bold' => true]];
        }

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
