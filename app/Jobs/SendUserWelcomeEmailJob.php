<?php

namespace App\Jobs;

use App\Mail\UserWelcomeMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendUserWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        protected string $userId,
        protected ?string $temporaryPassword = null
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning('User not found for welcome email', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        if (!$user->email) {
            Log::warning('User has no email address for welcome email', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        Mail::to($user->email)->send(new UserWelcomeMail(
            $user,
            $this->temporaryPassword
        ));

        Log::info('Welcome email sent to user', [
            'user_id' => $this->userId,
            'email' => $user->email,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send user welcome email', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
