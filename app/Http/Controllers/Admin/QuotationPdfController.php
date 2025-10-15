<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PdfHelper;
use App\Quotation;
use Illuminate\Http\Request;

class QuotationPdfController extends Controller
{
    public function invoice($id)
    {
        $quotation = Quotation::findOrFail($id);
        return PdfHelper::GenerateQuotationInvoice($quotation, false, 'stream');
    }

    public function invoiceWithoutPrice($id)
    {
        $quotation = Quotation::findOrFail($id);
        return PdfHelper::GenerateQuotationInvoiceWithoutPrice($quotation, false, 'stream');
    }

    public function deliveryOrder($id)
    {
        $quotation = Quotation::findOrFail($id);
        return PdfHelper::GenerateDeliveryQuotation($quotation, false, 'stream');
    }
    
    // Download endpoints
    public function downloadInvoice($id)
    {
        $quotation = Quotation::findOrFail($id);
        return PdfHelper::GenerateQuotationInvoice($quotation, false, 'download');
    }

    public function downloadInvoiceWithoutPrice($id)
    {
        $quotation = Quotation::findOrFail($id);
        return PdfHelper::GenerateQuotationInvoiceWithoutPrice($quotation, false, 'download');
    }

    public function downloadDeliveryOrder($id)
    {
        $quotation = Quotation::findOrFail($id);
        return PdfHelper::GenerateDeliveryQuotation($quotation, false, 'download');
    }
}