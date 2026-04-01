<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionInvalidatedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public readonly string $ip;

    public readonly string $browser;

    public readonly string $loginAt;

    public function __construct(
        public readonly User $user,
        Request $request,
    ) {
        $this->ip = $request->ip() ?? 'desconhecido';
        $this->browser = $request->userAgent() ?? 'desconhecido';
        $this->loginAt = now()->format('d/m/Y H:i:s');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sua sessão foi encerrada — novo acesso detectado',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.session-invalidated',
        );
    }
}
