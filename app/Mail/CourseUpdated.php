<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Envoyé à chaque inscrit (confirmé ou en attente) quand le coach change la date, la durée ou le lieu d'un cours
class CourseUpdated extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];

    public function __construct(public Booking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Changement pour le cours {$this->booking->course->title}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.course-updated');
    }
}
