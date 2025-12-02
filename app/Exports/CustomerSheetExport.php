<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerSheetExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected $user;
    protected $request;

    public function __construct($user, $request)
    {
        $this->user = $user;
        $this->request = $request;
    }

    public function collection()
    {
        $productsByCategory = DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->join('uoms', 'uoms.id', '=', 'products.uom_id')
            ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->where('orders.user_id', $this->user->id)
            ->where(function ($q) {
                $q->where('order_products.quantity', '>', 0)
                ->orWhere('order_products.weight', '>', 0);
            })
            ->when($this->request->status, fn($q) => $q->where('orders.status', $this->request->status))
            ->where('orders.status', '!=', 'cancelled')
            ->when($this->request->fdate, fn($q) => $q->whereDate('orders.delivering_date', $this->request->fdate))
            ->select(
                'products.id',
                'products.name',
                'uoms.uom_name',
                'order_products.unit_price',
                'order_products.price',
                'order_products.quantity',
                'order_products.weight',
                'product_categories.category_name'
            )
            ->orderBy('product_categories.category_name')
            ->orderBy('products.name')
            ->get()
            ->groupBy('category_name');

        $rows = [];
        $total_qty = 0;
        $total_price = 0;

        foreach ($productsByCategory as $category => $products) {
            $rows[] = [$category, '', '', '', '', ''];
            foreach ($products as $product) {
                $qty = DB::table('order_products')
                    ->join('orders', 'orders.id', '=', 'order_products.order_id')
                    ->where('orders.user_id', $this->user->id)
                    ->where('order_products.product_id', $product->id)
                    ->when($this->request->status, fn($q) => $q->where('orders.status', $this->request->status))
                    ->where('orders.status', '!=', 'cancelled')
                    ->when(
                        $this->request->fdate,
                        fn($q) => $q->whereDate('orders.delivering_date', $this->request->fdate)
                    )
                    ->sum(DB::raw('COALESCE(order_products.quantity, order_products.weight, 0)'));

                if ($qty <= 0) continue;

                // Get daily price for this product
                $dailyPrice = DB::table('product_daily_prices')
                    ->where('product_id', $product->id)
                    ->where('date', $this->request->fdate)
                    ->where('user_category', $this->user->customer_category_id)
                    ->where('status', 'active')
                    ->value('price') ?? $product->unit_price;

                $displayQty = $product->quantity ?? $product->weight ?? 0;
                $total_qty += $displayQty;
                $total_price += $dailyPrice * $displayQty;

                $rows[] = [
                    $product->name,
                    $product->uom_name,
                    $displayQty,
                    '',
                    $dailyPrice,
                    '',
                ];
            }

            $rows[] = ['', '', '', '', '', '', ''];
        }

        if (count($rows) > 0) {
            $rows[] = ['', '', '', '', '', '', ''];
            $rows[] = ['', '', '', '', '', '', ''];
            $rows[] = ['Total', '', $total_qty, '', $total_price, ''];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        $title = $this->user->sql_customer_code ?: $this->user->name;

        return [
            ['OUTLET', '', $title, 'DATE OF DELIVERY'],
            ['', '', '', $this->request->fdate, ''],
            ['Product', 'UOM', 'Quantity', '', 'Price', 'BK'],
        ];
    }

    public function title(): string
    {
        $title = $this->user->sql_customer_code ?: $this->user->name;
        return strlen($title) > 31 ? substr($title, 0, 28) . '...' : $title;
    }

    public function styles(Worksheet $sheet)
    {
        $data = $this->collection();
        $rowCount = $data->count();

        if ($rowCount === 0) return [];

        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true]],
        ];

        $styles[$rowCount + 3] = ['font' => ['bold' => true]];

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 15,
            'C' => 15,
            'D' => 20,
            'E' => 20,
            'F' => 20,
        ];
    }
}
