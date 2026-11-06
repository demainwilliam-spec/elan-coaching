<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Envoyé à la personne qui sort de la liste d'attente quand une place se libère (cf. CancelBooking)
class BookingPromoted extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];

    public function __construct(public Booking $booking)
    {
    }

    public function envelope(): Envelope
    {
        $course = $this->booking->course;

        return new Envelope(
            subject: "Une place s'est libérée : vous êtes inscrit(e) au cours {$course->title} du {$course->starts_at->translatedFormat('j F')}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-promoted');
    }
}
