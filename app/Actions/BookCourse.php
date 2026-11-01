<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Models\Client;
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
     * $client : le compte qui réserve (null pour un visiteur). Sans compte, la réservation est quand même
     * rattachée au compte confirmé qui a la même adresse, s'il existe (même règle que VerifyClientEmail).
     *
     * @throws BookingException si le cours a commencé ou si l'email (ou le compte) est déjà inscrit
     */
    public function handle(Course $course, string $name, string $email, ?Client $client = null): Booking
    {
        // Email normalisé : "  Alice@Mail.com " et "alice@mail.com" sont la même personne,
        // quelle que soit la base (la collation _ci de MariaDB n'existe pas en SQLite)
        $email = Str::lower(trim($email));

        // ?-> : vaut null si $client est null ; ?? : sinon, on cherche un compte confirmé avec cette adresse
        // value('id') : récupère seulement la colonne id de la première ligne (ou null)
        $clientId = $client?->id ?? Client::where('email', $email)->whereNotNull('email_verified_at')->value('id');

        try {
            // Transaction : tout ce qui est dans la fonction est enregistré ensemble, ou pas du tout
            $booking = DB::transaction(function () use ($course, $name, $email, $clientId) {
                // On relit le cours AVEC un verrou (SELECT ... FOR UPDATE) : toute autre réservation
                // sur ce même cours attend ici que notre transaction soit terminée
                $course = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();

                if ($course->starts_at->isPast()) {
                    throw BookingException::courseStarted();
                }

                // Un compte = une place par cours, même si l'une de ses réservations porte une autre adresse
                // (ancienne adresse du compte) : l'index unique (course_id, email) ne le verrait pas
                if ($clientId !== null && $course->bookings()->where('client_id', $clientId)->exists()) {
                    throw BookingException::alreadyBookedWithAccount();
                }

                // Comptage fait sous verrou : personne ne peut ajouter de réservation entre-temps
                $confirmed = $course->bookings()->where('status', BookingStatus::Confirmed)->count();

                $status = $confirmed < $course->capacity
                    ? BookingStatus::Confirmed
                    : BookingStatus::Waiting;

                return $course->bookings()->create([
                    'client_id' => $clientId,
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

        // Réserver avec son compte = activité (repousse la suppression pour inactivité)
        $client?->markActive();

        // Mailable "ShouldQueue" : send() le place en queue, le worker (php artisan queue:work) l'enverra
        Mail::to($booking->email)->send(new BookingReceived($booking));

        return $booking;
    }
}
