<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

// Envoyé à chaque inscrit quand le coach supprime un cours.
// Pas de modèle ici (ni SerializesModels) : quand le worker enverra le mail, le cours et la réservation
// auront été supprimés de la base. On transmet donc des valeurs simples, copiées avant la suppression.
class CourseCancelled extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable;

    public $tries = 3;
    public $backoff = [60, 300];

    public function __construct(
        public string $name,
        public string $courseTitle,
        public string $courseWhen,       // date déjà mise en forme ("Mardi 3 novembre à 18h30")
        public string $courseLocation,
        public ?string $coachMessage,    // pas "$message" : ce nom est réservé dans les vues d'email
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Cours annulé : {$this->courseTitle}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.course-cancelled');
    }
}
