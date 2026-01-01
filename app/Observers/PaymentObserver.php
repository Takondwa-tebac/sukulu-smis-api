<?php

namespace App\Observers;

use App\Models\Payment;
use App\Notifications\PaymentReceivedNotification;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $this->notifyGuardians($payment);
    }

    protected function notifyGuardians(Payment $payment): void
    {
        $student = $payment->student;

        if (!$student) {
            return;
        }

        $guardians = $student->guardians()
            ->wherePivot('receives_invoices', true)
            ->get();

        foreach ($guardians as $guardian) {
            if ($guardian->email) {
                $guardian->notify(new PaymentReceivedNotification($payment));
            }
        }
    }
}
