<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Mail\BookingPromoted;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

// Remplit les places libres d'un cours avec la liste d'attente (premier arrivé, premier servi).
// Utilisée par CancelBooking (une place se libère) et UpdateCourse (capacité augmentée).
// À appeler DANS une transaction, avec le cours déjà verrouillé (lockForUpdate) par l'appelant.
class PromoteWaiting
{
    /**
     * @return Collection<int, Booking> les réservations promues (vide si aucune)
     */
    public function handle(Course $course): Collection
    {
        $confirmed = $course->bookings()->where('status', BookingStatus::Confirmed)->count();
        $freePlaces = $course->capacity - $confirmed;

        if ($freePlaces <= 0) {
            return collect(); // collection vide
        }

        // Les N premiers en attente, N = nombre de places libres
        $promoted = $course->bookings()
            ->where('status', BookingStatus::Waiting)
            ->orderBy('id')
            ->limit($freePlaces)
            ->get();

        foreach ($promoted as $booking) {
            $booking->update(['status' => BookingStatus::Confirmed]);

            // ShouldQueueAfterCommit : mis en queue seulement au COMMIT de la transaction de l'appelant
            Mail::to($booking->email)->send(new BookingPromoted($booking));
        }

        return $promoted;
    }
}
