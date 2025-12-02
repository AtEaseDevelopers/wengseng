<?php

namespace App\Exports;

use App\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use DB;

class OrderStockReport implements FromCollection, WithHeadings, WithEvents, WithColumnWidths,ShouldAutoSize
{
    protected $i;
    protected $orders;

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $sno = 1;

                foreach ($this->orders as $customer_code => $order_products) {
                    $no = $this->i;

                    $sheet->setCellValue('A' . $no, $sno++);
                    $sheet->setCellValue('B' . $no, $customer_code);
                    $sheet->setCellValue('C' . $no, '');
                    $sheet->setCellValue('D' . $no, '');
                    $sheet->setCellValue('E' . $no, '');

                    foreach ($order_products as $product) {
                        $this->i++;
                        $no = $this->i;

                        $sheet->setCellValue('A' . $no, '');
                        $sheet->setCellValue('B' . $no, '');
                        $sheet->setCellValue('C' . $no, $product['product_name']);
                        $sheet->setCellValue('D' . $no, $product['uom_name']);
                        $sheet->setCellValue('E' . $no, $product['quantity']);
                    }

                    $this->i++;
                }

                // Make row bold
                $event->sheet->getDelegate()->getStyle('A1:E1')->getFont()->setBold(true);

                // Set BG color
                $event->sheet->getDelegate()->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('dee0bb');

                // Set Font color
                $event->sheet->getDelegate()->getStyle('A1:E1')->getFont()->getColor()->setARGB('000000');
            }
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $this->i = 2;

        $request = request();
        $fdate = $request->fdate;
        $tdate = $request->tdate;

        $today = now()->toDateString();
        $startDate = $fdate ?: $today;
        
        $endDate = $tdate ?: $today;
        $startDate = min($startDate, $endDate);

        $orders = DB::table('order_products')
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
            ->leftJoin('uoms', 'uoms.id', '=', 'products.uom_id')
            ->select(
                'order_products.product_name',
                'users.sql_customer_code',
                'order_products.quantity',
                DB::raw('SUM(order_products.quantity) as quantity'),
                'uoms.uom_name',
            )
            ->when(
                $request['product_category_id'], 
                function ($q) use ($request) {
                    return $q->where('products.product_category_id', $request['product_category_id']);
                }
            )
            ->when(
                $request['area_id'], function ($q) {
                    return $q->where('orders.area_id', request()->area_id);
                }
            )
            ->when(
                ($request->fdate && $request->tdate), function ($q) use ($startDate, $endDate) {
                    return $q->whereBetween('orders.created_at', [$startDate, $endDate . " 23:59:59"]);
                }
            )
            ->when(
                (isset($request['user_id'][0]) && $request['user_id'][0] != null), function ($q) use ($request) {
                    return $q->whereIn('users.id', $request['user_id']);
                }
            )
            ->where('orders.status', '!=', 'completed')
           ->groupBy(['product_id','product_name','users.sql_customer_code','uoms.uom_name'])
            ->orderBy('users.sql_customer_code', 'asc')
            ->orderBy('order_products.product_name', 'asc')
            ->get()
            ->groupBy('sql_customer_code')
            ->map(function ($items) {
                return $items->map(fn ($item) => (array) $item);
            })
            ->toArray();

        return $this->orders = collect($orders);
    }

    public function headings(): array
    {
        return [
            [
                'No.',
                'Customer Code',
                'Product Name',
                'UOM',
                'Qty',
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 30,
            'C' => 40,
            'D' => 15,
            'E' => 15,
        ];
    }
}
