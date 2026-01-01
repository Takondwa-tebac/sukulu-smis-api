<?php

namespace App\Notifications;

use App\Models\AdmissionApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdmissionStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AdmissionApplication $application,
        public string $previousStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->application->status;
        $studentName = $this->application->first_name . ' ' . $this->application->last_name;

        $message = (new MailMessage)
            ->subject('Admission Application Update - ' . $studentName);

        switch ($status) {
            case 'approved':
                $message->greeting('Congratulations!')
                    ->line("We are pleased to inform you that the admission application for {$studentName} has been approved.")
                    ->line('Please proceed with the enrollment process.')
                    ->action('Complete Enrollment', url('/admissions/' . $this->application->id));
                break;

            case 'rejected':
                $message->greeting('Dear Applicant,')
                    ->line("We regret to inform you that the admission application for {$studentName} has not been successful.")
                    ->line('Reason: ' . ($this->application->rejection_reason ?? 'Not specified'))
                    ->line('Thank you for your interest in our school.');
                break;

            case 'under_review':
                $message->greeting('Dear Applicant,')
                    ->line("The admission application for {$studentName} is now under review.")
                    ->line('We will notify you once a decision has been made.');
                break;

            case 'interview_scheduled':
                $message->greeting('Dear Applicant,')
                    ->line("An interview has been scheduled for {$studentName}.")
                    ->line('Interview Date: ' . ($this->application->interview_date?->format('F j, Y \a\t g:i A') ?? 'To be confirmed'))
                    ->line('Please ensure you arrive on time.');
                break;

            default:
                $message->greeting('Dear Applicant,')
                    ->line("The status of the admission application for {$studentName} has been updated to: {$status}.");
        }

        return $message->line('Thank you for choosing our school.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admission_status_change',
            'application_id' => $this->application->id,
            'student_name' => $this->application->first_name . ' ' . $this->application->last_name,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->application->status,
        ];
    }
}
