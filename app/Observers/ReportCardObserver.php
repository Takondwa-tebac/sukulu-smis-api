<?php

namespace App\Observers;

use App\Models\ReportCard;
use App\Notifications\ReportCardPublishedNotification;

class ReportCardObserver
{
    public function updated(ReportCard $reportCard): void
    {
        if ($reportCard->wasChanged('status') && $reportCard->status === ReportCard::STATUS_PUBLISHED) {
            $this->notifyGuardians($reportCard);
        }
    }

    protected function notifyGuardians(ReportCard $reportCard): void
    {
        $student = $reportCard->student;

        if (!$student) {
            return;
        }

        $guardians = $student->guardians()
            ->wherePivot('receives_reports', true)
            ->get();

        foreach ($guardians as $guardian) {
            if ($guardian->email) {
                $guardian->notify(new ReportCardPublishedNotification($reportCard));
            }
        }
    }
}
