<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $newPassword,
        public string $loginUrl = ''
    ) {
        $this->loginUrl = $loginUrl ?: config('app.frontend_url', 'http://localhost:3000') . '/login';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Sukulu SMIS Password Has Been Reset',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
