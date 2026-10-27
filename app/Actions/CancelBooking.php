<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class CancelBooking
{
    // Constante de classe : la règle métier est écrite une seule fois, réutilisable ailleurs (CancelBooking::CUTOFF_HOURS)
    public const CUTOFF_HOURS = 24;

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

            if ($course->starts_at->lessThan(now()->addHours(self::CUTOFF_HOURS))) {
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

            // ?-> (nullsafe) : n'appelle update() que si $next existe, sinon renvoie null
            $next?->update(['status' => BookingStatus::Confirmed]);

            return $next;
        });
    }
}
