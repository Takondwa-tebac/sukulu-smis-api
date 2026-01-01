<?php

namespace App\Notifications;

use App\Models\StudentInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public StudentInvoice $invoice
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Invoice Generated - ' . $this->invoice->invoice_number)
            ->greeting('Dear Parent/Guardian,')
            ->line('A new invoice has been generated for your child.')
            ->line('Invoice Number: ' . $this->invoice->invoice_number)
            ->line('Amount: ' . number_format($this->invoice->total_amount, 2))
            ->line('Due Date: ' . $this->invoice->due_date?->format('F j, Y'))
            ->line('Please ensure payment is made before the due date.')
            ->action('View Invoice', url('/invoices/' . $this->invoice->id))
            ->line('Thank you for your continued support.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_created',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date?->toISOString(),
            'student_id' => $this->invoice->student_id,
        ];
    }
}
