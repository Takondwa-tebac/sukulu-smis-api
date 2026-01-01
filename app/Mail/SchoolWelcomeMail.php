<?php

namespace App\Mail;

use App\Models\School;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SchoolWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public School $school,
        public User $adminUser,
        public ?string $temporaryPassword = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Sukulu - Your School Has Been Onboarded!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.school-welcome',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
