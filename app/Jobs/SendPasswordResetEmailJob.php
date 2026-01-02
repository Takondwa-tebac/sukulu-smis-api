<?php

namespace App\Jobs;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        protected string $userId,
        protected string $newPassword
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning('User not found for password reset email', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        if (!$user->email) {
            Log::warning('User has no email address for password reset email', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        Mail::to($user->email)->send(new PasswordResetMail(
            $user,
            $this->newPassword
        ));

        Log::info('Password reset email sent to user', [
            'user_id' => $this->userId,
            'email' => $user->email,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send password reset email', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
