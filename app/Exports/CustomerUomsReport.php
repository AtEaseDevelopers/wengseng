<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerUomsReport implements WithMultipleSheets
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        $sheets = [];

        // categories that have orders, grouped by excel_sheet_batch
        $categories = DB::table('product_categories')
            ->join('products', 'products.product_category_id', '=', 'product_categories.id')
            ->join('order_products', 'order_products.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->when($this->request->status, function ($q) {
                $q->where('orders.status', $this->request->status);
            })
            ->when($this->request->fdate, function ($q) {
                $q->whereDate('orders.delivering_date', $this->request->fdate);
            })
            ->where('orders.status', '!=', 'cancelled')
            ->where('product_categories.category_name', '!=', 'IMPORT')
            ->select('product_categories.id', 'product_categories.category_name', 'product_categories.excel_sheet_batch')
            ->distinct()
            ->get();

        if ($categories->count() > 0) {
            // Group categories by excel_sheet_batch
            $groupedCategories = $categories->groupBy('excel_sheet_batch');

            foreach ($groupedCategories as $batchKey => $batchCategories) {
                if ($batchKey === '' || $batchKey === null) {
                    // NULL batch - individual sheets per category
                    foreach ($batchCategories as $category) {
                        $sheets[] = new ProductCategoriesSheetExport([$category], $this->request);
                    }
                } else {
                    // Same batch - combine into one sheet
                    $sheets[] = new ProductCategoriesSheetExport($batchCategories->values()->all(), $this->request);
                }
            }
        }
        
        // Barang Import / Lebih
        $sheets[] = new BarangImportSheetExport($this->request);
        $sheets[] = new BarangLebihSheetExport($this->request);

        // users that have orders
        $users = DB::table('users')
            ->join('orders', 'orders.user_id', '=', 'users.id')
            ->join('order_products', 'order_products.order_id', '=', 'orders.id')
            ->when($this->request->status, function ($q) {
                $q->where('orders.status', $this->request->status);
            })
            ->when($this->request->fdate, function ($q) {
                $q->whereDate('orders.delivering_date', $this->request->fdate);
            })
            ->select('users.id', 'users.sql_customer_code', 'users.name', 'users.customer_category_id')
            ->where('orders.status', '!=', 'cancelled')
            ->distinct()
            ->orderBy('users.sql_customer_code', 'asc')
            ->get();

        if ($users->count() > 0) {
            foreach ($users as $user) {
                $sheets[] = new CustomerSheetExport($user, $this->request);
            }
        }

        if (count($sheets) === 0) {
            $sheets[] = new EmptyReportSheet($this->request);
        }

        return $sheets;
    }
}

class EmptyReportSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function array(): array
    {
        $filterInfo = [];
        
        if (!empty($this->request->status)) {
            $filterInfo[] = ['Status Filter:', $this->request->status];
        }
        
        if (!empty($this->request->fdate)) {
            $filterInfo[] = ['Date:', $this->request->fdate];
        }

        return [
            ['CUSTOMER UOMS REPORT'],
            [''],
            ['No data found for the selected criteria.'],
            [''],
            ...$filterInfo,
            [''],
            ['Possible reasons:'],
            ['- No orders match your current filters'],
            ['- Orders exist but were cancelled or have zero quantity'],
            ['- Date range filters exclude all existing orders'],
            [''],
            ['Please adjust your filters and try again.'],
        ];
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'No Data';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            3 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']]],
        ];
    }
}