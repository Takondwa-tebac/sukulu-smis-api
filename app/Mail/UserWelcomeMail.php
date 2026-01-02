<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $temporaryPassword = null,
        public string $loginUrl = ''
    ) {
        $this->loginUrl = $loginUrl ?: config('app.frontend_url', 'http://localhost:3000') . '/login';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Sukulu SMIS - Your Account Has Been Created',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-welcome',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
