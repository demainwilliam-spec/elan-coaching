<?php

namespace App\Mail;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Envoyé après une réservation : place confirmée, ou position sur la liste d'attente.
// ShouldQueueAfterCommit : Mail::send() ne l'envoie pas tout de suite, il le met en queue (table jobs),
// et seulement une fois la transaction en cours validée (COMMIT)
class BookingReceived extends Mailable implements ShouldQueueAfterCommit
{
    // SerializesModels : la tâche ne stocke que l'id de la réservation, le worker la relit en base avant l'envoi
    use Queueable, SerializesModels;

    // Si Gmail ne répond pas : 3 essais au total, le 2e après 60 s, le 3e après 300 s
    public $tries = 3;
    public $backoff = [60, 300];

    public function __construct(public Booking $booking)
    {
    }

    public function envelope(): Envelope
    {
        $course = $this->booking->course;
        $date = $course->starts_at->translatedFormat('j F');

        return new Envelope(
            subject: $this->booking->status === BookingStatus::Confirmed
                ? "Réservation confirmée : {$course->title} du {$date}"
                : "Liste d'attente : {$course->title} du {$date}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-received');
    }
}
