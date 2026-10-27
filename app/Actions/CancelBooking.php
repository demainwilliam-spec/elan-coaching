<?php

namespace App\Actions;

use App\Exceptions\BookingException;
use App\Mail\BookingRemoved;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CancelBooking
{
    // Injection de dépendance par le constructeur. "= new PromoteWaiting" (PHP 8.1) : valeur par défaut,
    // pour pouvoir écrire simplement "new CancelBooking" (dans les tests par exemple)
    public function __construct(private PromoteWaiting $promoteWaiting = new PromoteWaiting)
    {
    }

    /**
     * Annule une réservation. Si elle était confirmée, la première personne en attente prend la place.
     *
     * @param  bool  $byCoach  retrait par le coach : pas de limite des 24 h, et la personne est prévenue par mail
     * @return Booking|null la réservation promue, ou null si personne ne l'a été
     *
     * @throws BookingException si le cours commence dans moins de 24 h ou si la réservation est déjà annulée
     */
    public function handle(Booking $booking, bool $byCoach = false): ?Booking
    {
        return DB::transaction(function () use ($booking, $byCoach) {
            // Même verrou que BookCourse : réservations et annulations d'un même cours passent l'une après l'autre
            $course = Course::whereKey($booking->course_id)->lockForUpdate()->firstOrFail();

            if (! $byCoach && ! $course->cancellationIsOpen()) {
                throw BookingException::cancellationClosed();
            }

            // On relit la réservation sous verrou : une autre requête (double clic) a pu la supprimer pendant qu'on attendait
            $booking = $course->bookings()->whereKey($booking->id)->first();

            if ($booking === null) {
                throw BookingException::alreadyCancelled();
            }

            $booking->delete();

            if ($byCoach) {
                // Valeurs simples, comme CourseCancelled : la réservation n'existera plus quand le worker enverra le mail
                Mail::to($booking->email)->send(new BookingRemoved(
                    $booking->name,
                    $course->title,
                    ucfirst($course->starts_at->translatedFormat('l j F \à H\hi')),
                    $course->location,
                ));
            }

            // Place confirmée libérée → le 1er en attente est promu (et prévenu par mail).
            // Place en attente supprimée → aucune place libre, rien ne se passe : les suivants avancent seuls
            return $this->promoteWaiting->handle($course)->first();
        });
    }
}
