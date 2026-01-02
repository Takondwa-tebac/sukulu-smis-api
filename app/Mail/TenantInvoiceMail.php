<?php

namespace App\Mail;

use App\Models\TenantInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TenantInvoice $invoice,
        public ?string $pdfPath = null
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->invoice->status === 'sent' 
            ? "Invoice {$this->invoice->invoice_number} from Sukulu"
            : "Invoice {$this->invoice->invoice_number} - Payment Reminder";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-invoice',
        );
    }

    public function attachments(): array
    {
        if ($this->pdfPath && file_exists($this->pdfPath)) {
            return [
                Attachment::fromPath($this->pdfPath)
                    ->as("{$this->invoice->invoice_number}.pdf")
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
