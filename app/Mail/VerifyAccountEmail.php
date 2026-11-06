<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

// Envoyé après l'inscription (et sur demande depuis « Mon compte ») : lien pour confirmer l'adresse email
class VerifyAccountEmail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];

    public function __construct(public Client $client)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmez votre adresse email — JF Coaching',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-account',
            with: [
                // Lien signé : Laravel ajoute ?expires=...&signature=... ; la signature est calculée avec APP_KEY,
                // donc modifier un seul caractère du lien (id, date d'expiration...) le rend invalide.
                // hash : empreinte de l'adresse, pour qu'un lien ne valide que l'adresse à laquelle il a été envoyé
                'url' => URL::temporarySignedRoute(
                    'account.verification.verify',
                    now()->addMinutes(Client::VERIFICATION_LINK_MINUTES),
                    ['client' => $this->client->id, 'hash' => sha1($this->client->email)],
                ),
            ],
        );
    }
}
