<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 60;

    public function __construct(
        protected string $notificationId
    ) {}

    public function handle(): void
    {
        $notification = Notification::with('recipients')->find($this->notificationId);

        if (!$notification) {
            Log::warning('Notification not found', ['id' => $this->notificationId]);
            return;
        }

        if ($notification->status !== Notification::STATUS_PENDING) {
            return;
        }

        try {
            switch ($notification->type) {
                case NotificationTemplate::TYPE_EMAIL:
                    $this->sendEmail($notification);
                    break;
                case NotificationTemplate::TYPE_SMS:
                    $this->sendSms($notification);
                    break;
                case NotificationTemplate::TYPE_IN_APP:
                    $notification->markAsSent();
                    break;
            }
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function sendEmail(Notification $notification): void
    {
        foreach ($notification->recipients as $recipient) {
            if (!$recipient->recipient_email) {
                continue;
            }

            try {
                Mail::raw($notification->body, function ($message) use ($notification, $recipient) {
                    $message->to($recipient->recipient_email)
                        ->subject($notification->subject ?? 'Notification from Sukulu');
                });

                $recipient->update([
                    'status' => NotificationRecipient::STATUS_SENT,
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                $recipient->update([
                    'status' => NotificationRecipient::STATUS_FAILED,
                    'failure_reason' => $e->getMessage(),
                ]);
            }
        }

        $notification->markAsSent();
    }

    protected function sendSms(Notification $notification): void
    {
        foreach ($notification->recipients as $recipient) {
            if (!$recipient->recipient_phone) {
                continue;
            }

            // SMS provider integration would go here
            // For now, mark as sent (placeholder)
            $recipient->update([
                'status' => NotificationRecipient::STATUS_SENT,
                'sent_at' => now(),
            ]);
        }

        $notification->markAsSent();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Send notification job failed', [
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
