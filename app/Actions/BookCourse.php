<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// Action : une classe = une opération métier. Utilisable depuis un contrôleur, un test, une commande...
class BookCourse
{
    /**
     * Réserve une place (confirmée s'il en reste, sinon en liste d'attente).
     *
     * @throws BookingException si le cours a commencé ou si l'email est déjà inscrit
     */
    public function handle(Course $course, string $name, string $email): Booking
    {
        // Email normalisé : "  Alice@Mail.com " et "alice@mail.com" sont la même personne,
        // quelle que soit la base (la collation _ci de MariaDB n'existe pas en SQLite)
        $email = Str::lower(trim($email));

        try {
            // Transaction : tout ce qui est dans la fonction est enregistré ensemble, ou pas du tout
            $booking = DB::transaction(function () use ($course, $name, $email) {
                // On relit le cours AVEC un verrou (SELECT ... FOR UPDATE) : toute autre réservation
                // sur ce même cours attend ici que notre transaction soit terminée
                $course = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();

                if ($course->starts_at->isPast()) {
                    throw BookingException::courseStarted();
                }

                // Comptage fait sous verrou : personne ne peut ajouter de réservation entre-temps
                $confirmed = $course->bookings()->where('status', BookingStatus::Confirmed)->count();

                $status = $confirmed < $course->capacity
                    ? BookingStatus::Confirmed
                    : BookingStatus::Waiting;

                return $course->bookings()->create([
                    'name' => $name,
                    'email' => $email,
                    'status' => $status,
                ]);
            });
            // Fin de la transaction : COMMIT, le verrou est libéré
        } catch (UniqueConstraintViolationException) {
            // L'index unique (course_id, email) a refusé l'INSERT : la transaction a été annulée (ROLLBACK)
            throw BookingException::alreadyBooked();
        }

        // Mailable "ShouldQueue" : send() le place en queue, le worker (php artisan queue:work) l'enverra
        Mail::to($booking->email)->send(new BookingReceived($booking));

        return $booking;
    }
}
