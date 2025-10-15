<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDF;
use Carbon\Carbon;

class PdfHelper extends Model
{
    /**
     * Common method to handle PDF return options
     */
    private static function handlePdfReturn($pdf, $filename, $path, $id, $returnPdf)
    {
        // Always save to storage
        Storage::disk('local')->put($path . '/' . $id . '/' . $filename, $pdf->output());

        // Handle return behavior
        if ($returnPdf === 'stream') {
            return $pdf->stream($filename);
        }

        if ($returnPdf === 'download') {
            return $pdf->download($filename);
        }

        // Default return path
        return $path . '/' . $id . '/' . $filename;
    }

    /**
     * Common method to get products for orders or quotations
     */
    private static function getProductsData($type, $id, $productModel)
    {
        return DB::table("{$type}_products")
            ->select(
                "{$type}_products.id as {$type}_product_id", 
                'products.id as product_id', 
                'products.show_qty as show_qty',
                'products.show_weight as show_weight',
                "{$type}s.id as {$type}_id", 
                "{$type}s.transfer_slip as transfer_slip", 
                "{$type}_products.product_name as name", 
                "{$type}_products.quantity", 
                "{$type}_products.unit_price", 
                "{$type}_products.price",
                "{$type}_products.remark",
                "{$type}_products.nos",
                "{$type}_products.weight",
                "{$type}_products.product_weight",
                DB::raw("(SELECT GROUP_CONCAT(
                        CONCAT(`option`, ': ', `option_item`) 
                        SEPARATOR ', '
                    ) 
                    FROM {$type}_product_options 
                    WHERE {$type}_product_options.{$type}_product_id = {$type}_products.id 
                    AND {$type}_product_options.status = 'active') as product_options"
                )
            )
            ->leftJoin("{$type}s", "{$type}s.id", '=', "{$type}_products.{$type}_id")
            ->leftJoin('products', 'products.id', '=', "{$type}_products.product_id")
            ->where("{$type}_products.status", $productModel::$status['active'])
            ->where("{$type}s.id", $id)
            ->get();
    }

    // Order specific methods (keep original structure but use common helpers)
    public static function GenerateOrderInvoice(Order $order, $void = false, $returnPdf = false)
    {
        $order_products = self::getProductsData('order', $order->id, OrderProduct::class);
        $data = [
            'invoice_number' => 'INV-' . $order->id,
            'date' => now()->format('d/m/Y'),
            'order' => $order,
            'order_items' => $order_products,
            'void' => $void,
            'user' => $order->customer,
            'type' => 'order',
        ];

        $pdf = PDF::loadView('pdf.invoice', $data);
        $pdf->setPaper('a4', 'portrait');

        $invoiceFilename = 'invoice-' . $order->id . '.pdf';

        return self::handlePdfReturn($pdf, $invoiceFilename, Order::$path, $order->id, $returnPdf);
    }

    public static function GenerateOrderInvoiceWithoutPrice(Order $order, $void = false, $returnPdf = false)
    {
        $order_products = self::getProductsData('order', $order->id, OrderProduct::class);
        $data = [
            'invoice_number' => 'INV-' . $order->id,
            'date' => now()->format('d/m/Y'),
            'order' => $order,
            'order_items' => $order_products,
            'total' => $total,
            'void' => $void,
            'user' => $order->customer,
            'type' => 'order',
        ];

        $pdf = PDF::loadView('pdf.invoicewithoutprice', $data);
        $pdf->setPaper('a4', 'portrait');

        $invoiceFilename = 'invoice-' . $order->id . '.pdf';

        return self::handlePdfReturn($pdf, $invoiceFilename, Order::$path, $order->id, $returnPdf);
    }

    public static function GenerateDeliveryOrder(Order $order, $void = false, $returnPdf = false)
    {
        $order_products = self::getProductsData('order', $order->id, OrderProduct::class);
        $data = [
            'invoice_number' => 'INV-' . $order->id,
            'date' => $order->created_at,
            'order' => $order,
            'order_items' => $order_products,
            // 'total' => $total,
            'void' => $void,
            'do_no' => $order->do_no,
        ];

        $pdf = PDF::loadView('pdf.delivery-order', $data);
        $pdf->setPaper('a4', 'portrait');

        $invoiceFilename = 'delivery-order-' . $order->id . '.pdf';

        return self::handlePdfReturn($pdf, $invoiceFilename, Order::$path, $order->id, $returnPdf);
    }

    // Quotation specific methods (similar structure)
    public static function GenerateQuotationInvoice(Quotation $quotation, $void = false, $returnPdf = false)
    {
        $quotation_products = self::getProductsData('quotation', $quotation->id, QuotationProduct::class);

        $data = [
            'invoice_number' => 'QUO-' . $quotation->id,
            'date' => now()->format('d/m/Y'),
            'order' => $quotation,
            'order_items' => $quotation_products,
            'void' => $void,
            'user' => $quotation->customer,
            'type' => 'quotation',
        ];

        $pdf = PDF::loadView('pdf.invoice', $data);
        $pdf->setPaper('a4', 'portrait');

        $invoiceFilename = 'invoice-' . $quotation->id . '.pdf';

        return self::handlePdfReturn($pdf, $invoiceFilename, Quotation::$path, $quotation->id, $returnPdf);
    }

    public static function GenerateQuotationInvoiceWithoutPrice(Quotation $quotation, $void = false, $returnPdf = false)
    {
        $quotation_products = self::getProductsData('quotation', $quotation->id, QuotationProduct::class);
        $data = [
            'invoice_number' => 'QUO-' . $quotation->id,
            'date' => now()->format('d/m/Y'),
            'order' => $quotation,
            'order_items' => $quotation_products,
            'void' => $void,
            'user' => $quotation->customer,
            'type' => 'quotation',
        ];

        $pdf = PDF::loadView('pdf.invoicewithoutprice', $data);
        $pdf->setPaper('a4', 'portrait');

        $invoiceFilename = 'invoice-' . $quotation->id . '.pdf';

        return self::handlePdfReturn($pdf, $invoiceFilename, Quotation::$path, $quotation->id, $returnPdf);
    }

    public static function GenerateDeliveryQuotation(Quotation $quotation, $void = false, $returnPdf = false)
    {
        $quotation_products = self::getProductsData('quotation', $quotation->id, QuotationProduct::class);
        $data = [
            'invoice_number' => 'QUO-' . $quotation->id,
            'date' => $quotation->created_at,
            'order' => $quotation,
            'order_items' => $quotation_products,
            'void' => $void,
            'do_no' => $quotation->do_no,
        ];

        $pdf = PDF::loadView('pdf.delivery-order', $data);
        $pdf->setPaper('a4', 'portrait');

        $invoiceFilename = 'delivery-order-' . $quotation->id . '.pdf';

        return self::handlePdfReturn($pdf, $invoiceFilename, Quotation::$path, $quotation->id, $returnPdf);
    }
}
