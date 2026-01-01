<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Received - Receipt #' . $this->payment->receipt_number)
            ->greeting('Dear Parent/Guardian,')
            ->line('We have received your payment. Thank you!')
            ->line('Receipt Number: ' . $this->payment->receipt_number)
            ->line('Amount: ' . number_format($this->payment->amount, 2))
            ->line('Payment Method: ' . ucfirst($this->payment->payment_method))
            ->line('Date: ' . $this->payment->payment_date->format('F j, Y'))
            ->action('View Receipt', url('/payments/' . $this->payment->id . '/receipt'))
            ->line('Thank you for your prompt payment.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'payment_id' => $this->payment->id,
            'receipt_number' => $this->payment->receipt_number,
            'amount' => $this->payment->amount,
            'student_id' => $this->payment->student_id,
        ];
    }
}
