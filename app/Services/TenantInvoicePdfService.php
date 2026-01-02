<?php

namespace App\Services;

use App\Models\TenantInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TenantInvoicePdfService
{
    /**
     * Generate PDF for a tenant invoice
     */
    public function generate(TenantInvoice $invoice): string
    {
        $invoice->load(['school', 'items']);

        $pdf = Pdf::loadView('pdf.tenant-invoice', [
            'invoice' => $invoice,
            'school' => $invoice->school,
            'items' => $invoice->items,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Generate and save PDF to storage
     */
    public function generateAndSave(TenantInvoice $invoice): string
    {
        $pdfContent = $this->generate($invoice);
        
        $filename = "invoices/tenant/{$invoice->invoice_number}.pdf";
        Storage::disk('local')->put($filename, $pdfContent);

        return $filename;
    }

    /**
     * Get the path to a saved invoice PDF
     */
    public function getPath(TenantInvoice $invoice): ?string
    {
        $filename = "invoices/tenant/{$invoice->invoice_number}.pdf";
        
        if (Storage::disk('local')->exists($filename)) {
            return Storage::disk('local')->path($filename);
        }

        return null;
    }

    /**
     * Generate PDF and return as download response
     */
    public function download(TenantInvoice $invoice)
    {
        $invoice->load(['school', 'items']);

        $pdf = Pdf::loadView('pdf.tenant-invoice', [
            'invoice' => $invoice,
            'school' => $invoice->school,
            'items' => $invoice->items,
        ]);

        return $pdf->download("{$invoice->invoice_number}.pdf");
    }

    /**
     * Generate PDF and return as stream response
     */
    public function stream(TenantInvoice $invoice)
    {
        $invoice->load(['school', 'items']);

        $pdf = Pdf::loadView('pdf.tenant-invoice', [
            'invoice' => $invoice,
            'school' => $invoice->school,
            'items' => $invoice->items,
        ]);

        return $pdf->stream("{$invoice->invoice_number}.pdf");
    }
}
