<?php

namespace App\Providers;

use App\Models\AdmissionApplication;
use App\Models\Payment;
use App\Models\ReportCard;
use App\Models\StudentInvoice;
use App\Observers\AdmissionApplicationObserver;
use App\Observers\PaymentObserver;
use App\Observers\ReportCardObserver;
use App\Observers\StudentInvoiceObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        StudentInvoice::observe(StudentInvoiceObserver::class);
        ReportCard::observe(ReportCardObserver::class);
        AdmissionApplication::observe(AdmissionApplicationObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
