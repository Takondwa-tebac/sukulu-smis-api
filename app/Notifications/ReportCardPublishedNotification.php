<?php

namespace App\Notifications;

use App\Models\ReportCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportCardPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ReportCard $reportCard
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $student = $this->reportCard->student;
        $term = $this->reportCard->term;
        $academicYear = $this->reportCard->academicYear;

        return (new MailMessage)
            ->subject('Report Card Published - ' . $student->full_name)
            ->greeting('Dear Parent/Guardian,')
            ->line("The report card for {$student->full_name} has been published.")
            ->line("Term: {$term->name}")
            ->line("Academic Year: {$academicYear->name}")
            ->line("Average Score: {$this->reportCard->average_score}%")
            ->line("Grade: {$this->reportCard->grade}")
            ->action('View Report Card', url('/report-cards/' . $this->reportCard->id))
            ->line('Thank you for your continued support of your child\'s education.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report_card_published',
            'report_card_id' => $this->reportCard->id,
            'student_id' => $this->reportCard->student_id,
            'student_name' => $this->reportCard->student->full_name,
            'term' => $this->reportCard->term->name,
            'average_score' => $this->reportCard->average_score,
            'grade' => $this->reportCard->grade,
        ];
    }
}
