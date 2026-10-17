<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    // "public string $name" dans les paramètres = promotion de propriété (PHP 8) :
    // la propriété est déclarée ET remplie, sans écrire $this->name = $name.
    // Les propriétés publiques sont automatiquement disponibles dans la vue de l'email.
    public function __construct(
        public string $name,
        public string $email,
        public string $messageText, // pas "$message" : ce nom est réservé dans les vues d'email
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouveau message de '.$this->name.' via le site',
            // "Répondre" dans la boîte mail écrira directement au visiteur
            replyTo: [new Address($this->email, $this->name)],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            // resources/views/emails/contact.blade.php (les points remplacent les /)
            view: 'emails.contact',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
