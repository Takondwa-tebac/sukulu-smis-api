<?php

namespace App\Jobs;

use App\Models\PasswordResetToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendPasswordResetEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $email,
        private string $token,
        private string $userName
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $resetUrl = $this->generateResetUrl($this->token);
            
            // TODO: Create and send actual email template
            // For now, we'll log the reset URL
            \Log::info("Password reset link for {$this->email}: {$resetUrl}");
            
            // In production, you would send an actual email:
            // Mail::to($this->email)->send(new PasswordResetMail($this->userName, $resetUrl));
            
        } catch (\Exception $e) {
            \Log::error("Failed to send password reset email to {$this->email}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate the password reset URL
     */
    private function generateResetUrl(string $token): string
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        return "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($this->email);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error("Password reset email job failed for {$this->email}: " . $exception->getMessage());
    }
}
