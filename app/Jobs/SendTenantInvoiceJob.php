<?php

namespace App\Jobs;

use App\Mail\TenantInvoiceMail;
use App\Models\TenantInvoice;
use App\Services\TenantInvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendTenantInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 60;

    public function __construct(
        protected string $invoiceId
    ) {}

    public function handle(TenantInvoicePdfService $pdfService): void
    {
        $invoice = TenantInvoice::with(['school', 'items'])->find($this->invoiceId);

        if (!$invoice) {
            Log::warning('Tenant invoice not found for email', [
                'invoice_id' => $this->invoiceId,
            ]);
            return;
        }

        if (!$invoice->school || !$invoice->school->email) {
            Log::warning('School or school email not found for invoice', [
                'invoice_id' => $this->invoiceId,
                'school_id' => $invoice->school_id,
            ]);
            return;
        }

        // Generate PDF
        $pdfContent = $pdfService->generate($invoice);
        
        // Save PDF temporarily
        $tempPath = "temp/invoices/{$invoice->invoice_number}.pdf";
        Storage::disk('local')->put($tempPath, $pdfContent);
        $fullPath = Storage::disk('local')->path($tempPath);

        try {
            // Send email with PDF attachment
            Mail::to($invoice->school->email)->send(new TenantInvoiceMail(
                $invoice,
                $fullPath
            ));

            Log::info('Tenant invoice email sent', [
                'invoice_id' => $this->invoiceId,
                'invoice_number' => $invoice->invoice_number,
                'school_email' => $invoice->school->email,
            ]);
        } finally {
            // Clean up temp file
            Storage::disk('local')->delete($tempPath);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send tenant invoice email', [
            'invoice_id' => $this->invoiceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
