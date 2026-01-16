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

class ProductCategoriesSheetExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $categories;
    protected $request;
    protected $users;

    public function __construct($categories, $request)
    {
        // Accept array of categories (for batch grouping) or single category (for backward compatibility)
        $this->categories = is_array($categories) ? $categories : [$categories];
        $this->request  = $request;

        // Get category IDs
        $categoryIds = array_map(fn($cat) => $cat->id, $this->categories);

        $query = DB::table('users')
            ->join('orders', 'orders.user_id', '=', 'users.id')
            ->join('order_products', 'order_products.order_id', '=', 'orders.id')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->whereIn('products.product_category_id', $categoryIds)
            ->where('order_products.status', 'active')
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
        // Get category IDs
        $categoryIds = array_map(fn($cat) => $cat->id, $this->categories);

        $products = DB::table('products')
            ->join('uoms', 'uoms.id', '=', 'products.uom_id')
            ->join('order_products', 'order_products.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->whereIn('products.product_category_id', $categoryIds)
            ->where('order_products.status', 'active')
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

        // Also include products from receive quantities
        $receive_product_ids = DB::table('product_receive_quantities')
            ->where('date', Carbon::parse($this->request->fdate)->subDays(1)->format('Y-m-d'))
            ->where('qty', '>', 0)
            ->pluck('product_id')->toArray();
        $receive_products = DB::table('products')
            ->join('uoms', 'uoms.id', '=', 'products.uom_id')
            ->whereIn('products.id', $receive_product_ids)
            ->whereIn('products.product_category_id', $categoryIds)
            ->select('products.id', 'products.name', 'uoms.uom_name')
            ->distinct()
            ->orderBy('products.name', 'asc')
            ->get();

        $new_products = collect();
        $new_products = $new_products->merge($products);
        foreach ($receive_products as $receive_product) {
            $found = false;
            foreach ($products as $product) {
                if ($receive_product->id == $product->id) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $new_products->push($receive_product);
            }
        }
        $products = $new_products;

        // Also include products from balance quantities
        $balance_product_ids = DB::table('product_balance_quantities')
            ->where('date', Carbon::parse($this->request->fdate)->subDays(2)->format('Y-m-d'))
            ->where('qty', '>', 0)
            ->pluck('product_id')->toArray();
        $balance_products = DB::table('products')
            ->join('uoms', 'uoms.id', '=', 'products.uom_id')
            ->whereIn('products.id', $balance_product_ids)
            ->whereIn('products.product_category_id', $categoryIds)
            ->select('products.id', 'products.name', 'uoms.uom_name')
            ->distinct()
            ->orderBy('products.name', 'asc')
            ->get();

        $new_products = collect();
        $new_products = $new_products->merge($products);
        foreach ($balance_products as $balance_product) {
            $found = false;
            foreach ($products as $product) {
                if ($balance_product->id == $product->id) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $new_products->push($balance_product);
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
            // Get all receive records and sum qty, use first remark
            $receive_records = DB::table('product_receive_quantities')
                ->select('qty', 'remark')
                ->where('product_id', $product->id)
                ->where('date', Carbon::parse($this->request->fdate)->subDays(1)->format('Y-m-d'))
                ->get();
            $receive_qty = (object) [
                'qty' => $receive_records->sum(fn($r) => floatval($r->qty)),
                'remark' => $receive_records->first()?->remark
            ];

            // Get all balance records and sum qty, use first remark
            $balance_records = DB::table('product_balance_quantities')
                ->select('qty', 'remark')
                ->where('product_id', $product->id)
                ->where('date', Carbon::parse($this->request->fdate)->subDays(2)->format('Y-m-d'))
                ->get();
            $balance_qty = (object) [
                'qty' => $balance_records->sum(fn($r) => floatval($r->qty)),
                'remark' => $balance_records->first()?->remark
            ];
            $row = [
                'Product' => $product->name,
                'Receive' => $receive_qty->qty ?? 0,
                'Receive Remark' => $receive_qty->remark ?? null,
                'Balance' => $balance_qty->qty ?? 0,
                'Balance Remark' => $balance_qty->remark ?? null,
                'UOM' => $product->uom_name
            ];
            $productTotal = 0;
            $hasData = false;

            foreach ($this->users as $user) {
                $userKey = $user->sql_customer_code ?? $user->name;

                $qty = DB::table('order_products')
                    ->join('orders', 'orders.id', '=', 'order_products.order_id')
                    ->where('orders.user_id', $user->id)
                    ->where('order_products.product_id', $product->id)
                    ->where('order_products.status', 'active')
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

            // Include row if has order data OR has receive qty OR has balance qty
            if ($hasData || count($this->users) <= 0 || ($receive_qty && $receive_qty->qty > 0) || ($balance_qty && $balance_qty->qty > 0)) {
                $row['Total'] = $productTotal;
                $rows[] = $row;
            }
        }

        if (count($rows) > 0) {
            $totalsRow = ['Product' => 'Total', 'Receive' => '', 'Receive Remark' => '', 'Balance' => '', 'Balance Remark' => '', 'UOM' => ''];
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
        // Combine category names with slash for grouped categories
        $names = array_map(fn($cat) => $cat->category_name, $this->categories);
        $title = implode(', ', $names);

        // Excel sheet names have 31 character limit
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
