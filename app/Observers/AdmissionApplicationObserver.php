<?php

namespace App\Observers;

use App\Models\AdmissionApplication;
use App\Notifications\AdmissionStatusNotification;

class AdmissionApplicationObserver
{
    public function updated(AdmissionApplication $application): void
    {
        if ($application->wasChanged('status')) {
            $previousStatus = $application->getOriginal('status');
            $this->notifyApplicant($application, $previousStatus);
        }
    }

    protected function notifyApplicant(AdmissionApplication $application, string $previousStatus): void
    {
        if ($application->email) {
            $application->notify(new AdmissionStatusNotification($application, $previousStatus));
        }
    }
}
