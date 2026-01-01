<?php

namespace App\Observers;

use App\Models\StudentInvoice;
use App\Notifications\InvoiceCreatedNotification;

class StudentInvoiceObserver
{
    public function created(StudentInvoice $invoice): void
    {
        $this->notifyGuardians($invoice);
    }

    protected function notifyGuardians(StudentInvoice $invoice): void
    {
        $student = $invoice->student;

        if (!$student) {
            return;
        }

        $guardians = $student->guardians()
            ->wherePivot('receives_invoices', true)
            ->get();

        foreach ($guardians as $guardian) {
            if ($guardian->email) {
                $guardian->notify(new InvoiceCreatedNotification($invoice));
            }
        }
    }
}
