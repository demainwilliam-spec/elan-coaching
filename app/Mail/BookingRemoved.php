<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

// Envoyé à la personne que le coach retire d'un cours. Valeurs simples (pas de modèle) : cf. CourseCancelled
class BookingRemoved extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable;

    public $tries = 3;
    public $backoff = [60, 300];

    public function __construct(
        public string $name,
        public string $courseTitle,
        public string $courseWhen,
        public string $courseLocation,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Inscription annulée : {$this->courseTitle}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-removed');
    }
}
