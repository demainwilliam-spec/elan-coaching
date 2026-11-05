<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Espace coach : lien pour choisir un nouveau mot de passe
class CoachResetPasswordEmail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];

    // $token : le jeton en clair (seule son empreinte est en base), il n'existe que dans ce mail
    public function __construct(public User $coach, public string $token)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mot de passe de votre espace coach — JF Coaching',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.coach-reset-password',
            with: [
                'url' => route('coach.password.reset', ['token' => $this->token, 'email' => $this->coach->email]),
                'minutes' => config('auth.passwords.users.expire'),
            ],
        );
    }
}
