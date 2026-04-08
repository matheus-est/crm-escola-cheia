<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\School;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeSchoolUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
        public School $school,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Bem-vindo — Acesso ao Sistema {$this->school->razao_social}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-school-user',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
