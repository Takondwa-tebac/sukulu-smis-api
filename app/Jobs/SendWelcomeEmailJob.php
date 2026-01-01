<?php

namespace App\Jobs;

use App\Mail\SchoolWelcomeMail;
use App\Models\School;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        protected string $schoolId,
        protected string $adminUserId,
        protected ?string $temporaryPassword = null
    ) {}

    public function handle(): void
    {
        $school = School::find($this->schoolId);
        $adminUser = User::find($this->adminUserId);

        if (!$school || !$adminUser) {
            Log::warning('School or admin user not found for welcome email', [
                'school_id' => $this->schoolId,
                'admin_user_id' => $this->adminUserId,
            ]);
            return;
        }

        Mail::to($adminUser->email)->send(new SchoolWelcomeMail(
            $school,
            $adminUser,
            $this->temporaryPassword
        ));

        Log::info('Welcome email sent to school admin', [
            'school_id' => $this->schoolId,
            'admin_email' => $adminUser->email,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send welcome email', [
            'school_id' => $this->schoolId,
            'admin_user_id' => $this->adminUserId,
            'error' => $exception->getMessage(),
        ]);
    }
}
