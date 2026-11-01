<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

// Avertissement avant la suppression d'un compte inactif (cf. commande clients:warn-inactive)
class InactiveAccountWarning extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];

    // $deletionDate : date à partir de laquelle le compte sera supprimé sans nouvelle connexion
    public function __construct(public Client $client, public Carbon $deletionDate)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte JF Coaching sera bientôt supprimé',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.inactive-account-warning');
    }
}
