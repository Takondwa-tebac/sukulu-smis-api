<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationTemplate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function send(array $params): Notification
    {
        $template = null;
        $subject = $params['subject'] ?? null;
        $body = $params['body'] ?? '';
        $type = $params['type'] ?? NotificationTemplate::TYPE_EMAIL;

        // If template is provided, render it
        if (!empty($params['template_id'])) {
            $template = NotificationTemplate::findOrFail($params['template_id']);
            $rendered = $template->render($params['data'] ?? []);
            $subject = $rendered['subject'];
            $body = $rendered['body'];
            $type = $template->type;
        }

        // Create notification record
        $notification = Notification::create([
            'school_id' => $params['school_id'],
            'template_id' => $template?->id,
            'type' => $type,
            'subject' => $subject,
            'body' => $body,
            'metadata' => $params['metadata'] ?? null,
            'created_by' => $params['created_by'] ?? null,
        ]);

        // Add recipients
        foreach ($params['recipients'] as $recipient) {
            $recipientModel = $this->resolveRecipient($recipient['type'], $recipient['id']);
            
            if ($recipientModel) {
                NotificationRecipient::create([
                    'notification_id' => $notification->id,
                    'recipient_type' => get_class($recipientModel),
                    'recipient_id' => $recipientModel->id,
                    'recipient_email' => $this->getRecipientEmail($recipientModel),
                    'recipient_phone' => $this->getRecipientPhone($recipientModel),
                ]);
            }
        }

        // Dispatch notification based on type
        $this->dispatch($notification);

        return $notification;
    }

    public function sendFromTemplate(string $templateCode, array $recipients, array $data = [], ?string $schoolId = null): ?Notification
    {
        $template = NotificationTemplate::where('code', $templateCode)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->active()
            ->first();

        if (!$template) {
            return null;
        }

        return $this->send([
            'school_id' => $template->school_id,
            'template_id' => $template->id,
            'data' => $data,
            'recipients' => $recipients,
        ]);
    }

    protected function dispatch(Notification $notification): void
    {
        $notification->load('recipients');

        switch ($notification->type) {
            case NotificationTemplate::TYPE_EMAIL:
                $this->sendEmail($notification);
                break;
            case NotificationTemplate::TYPE_SMS:
                $this->sendSms($notification);
                break;
            case NotificationTemplate::TYPE_IN_APP:
                // In-app notifications are already stored, just mark as sent
                $notification->markAsSent();
                break;
            default:
                break;
        }
    }

    protected function sendEmail(Notification $notification): void
    {
        try {
            foreach ($notification->recipients as $recipient) {
                if ($recipient->recipient_email) {
                    Mail::raw($notification->body, function ($message) use ($notification, $recipient) {
                        $message->to($recipient->recipient_email)
                            ->subject($notification->subject ?? 'Notification');
                    });

                    $recipient->update([
                        'status' => NotificationRecipient::STATUS_SENT,
                        'sent_at' => now(),
                    ]);
                }
            }

            $notification->markAsSent();
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
        }
    }

    protected function sendSms(Notification $notification): void
    {
        // SMS implementation would go here
        // This would integrate with SMS providers like Twilio, Africa's Talking, etc.
        try {
            foreach ($notification->recipients as $recipient) {
                if ($recipient->recipient_phone) {
                    // Placeholder for SMS sending logic
                    // $this->smsProvider->send($recipient->recipient_phone, $notification->body);
                    
                    $recipient->update([
                        'status' => NotificationRecipient::STATUS_SENT,
                        'sent_at' => now(),
                    ]);
                }
            }

            $notification->markAsSent();
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
        }
    }

    protected function resolveRecipient(string $type, string $id): ?object
    {
        return match ($type) {
            'user' => User::find($id),
            'student' => Student::find($id),
            'guardian' => Guardian::find($id),
            default => null,
        };
    }

    protected function getRecipientEmail(object $recipient): ?string
    {
        return $recipient->email ?? null;
    }

    protected function getRecipientPhone(object $recipient): ?string
    {
        return $recipient->phone ?? $recipient->phone_primary ?? null;
    }
}
