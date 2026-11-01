<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Envoyé depuis « Mot de passe oublié » : lien pour choisir un nouveau mot de passe
class ResetPasswordEmail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];

    // $token : le jeton en clair (seule son empreinte est en base), il n'existe que dans ce mail
    public function __construct(public Client $client, public string $token)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réinitialiser votre mot de passe — JF Coaching',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'url' => route('account.password.reset', ['token' => $this->token, 'email' => $this->client->email]),
                'minutes' => config('auth.passwords.clients.expire'),
            ],
        );
    }
}
