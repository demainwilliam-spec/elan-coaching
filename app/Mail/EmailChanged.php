<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Alerte envoyée à l'ANCIENNE adresse quand l'email du compte est changé : si ce n'est pas le titulaire
// qui l'a fait (compte volé), il est prévenu. Valeurs simples : le client aura déjà sa nouvelle adresse
class EmailChanged extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];

    public function __construct(public string $name, public string $newEmail)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'L\'adresse email de votre compte a été modifiée — JF Coaching',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.email-changed');
    }
}
