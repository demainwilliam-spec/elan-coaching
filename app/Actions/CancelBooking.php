<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Mail\BookingPromoted;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CancelBooking
{
    /**
     * Annule une réservation. Si elle était confirmée, la première personne en attente prend la place.
     *
     * @return Booking|null la réservation promue, ou null si personne ne l'a été
     *
     * @throws BookingException si le cours commence dans moins de 24 h ou si la réservation est déjà annulée
     */
    public function handle(Booking $booking): ?Booking
    {
        return DB::transaction(function () use ($booking) {
            // Même verrou que BookCourse : réservations et annulations d'un même cours passent l'une après l'autre
            $course = Course::whereKey($booking->course_id)->lockForUpdate()->firstOrFail();

            if (! $course->cancellationIsOpen()) {
                throw BookingException::cancellationClosed();
            }

            // On relit la réservation sous verrou : une autre requête (double clic) a pu la supprimer pendant qu'on attendait
            $booking = $course->bookings()->whereKey($booking->id)->first();

            if ($booking === null) {
                throw BookingException::alreadyCancelled();
            }

            $wasConfirmed = $booking->status === BookingStatus::Confirmed;
            $booking->delete();

            // Une place en attente ne libère rien : les suivants avancent seuls (waitingPosition() est calculée)
            if (! $wasConfirmed) {
                return null;
            }

            // Premier arrivé = plus petit id
            $next = $course->bookings()
                ->where('status', BookingStatus::Waiting)
                ->orderBy('id')
                ->first();

            if ($next !== null) {
                $next->update(['status' => BookingStatus::Confirmed]);

                // Demandé ici, en pleine transaction : ShouldQueueAfterCommit attend le COMMIT pour mettre le mail en queue
                Mail::to($next->email)->send(new BookingPromoted($next));
            }

            return $next;
        });
    }
}
