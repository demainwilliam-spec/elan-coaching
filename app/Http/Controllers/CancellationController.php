<?php

namespace App\Http\Controllers;

use App\Actions\CancelBooking;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CancellationController extends Controller
{
    // GET : affiche seulement la page de confirmation, ne modifie rien
    // (les messageries et antivirus ouvrent les liens des mails tout seuls)
    public function show(Booking $booking): View
    {
        return view('bookings.cancel', ['booking' => $booking]);
    }

    // POST : l'annulation réelle, déclenchée par le bouton de la page
    public function destroy(Booking $booking, CancelBooking $cancelBooking): RedirectResponse
    {
        try {
            $cancelBooking->handle($booking);
        } catch (BookingException $e) {
            // Retour sur la page de confirmation (ou sur "lien plus valide" si la réservation a disparu entre-temps)
            return to_route('bookings.cancel', $booking->cancel_token)->with('cancel_error', $e->getMessage());
        }

        $course = $booking->course;
        $what = $booking->status === BookingStatus::Confirmed ? 'Votre réservation' : 'Votre inscription sur la liste d\'attente';
        $message = "{$what} au cours {$course->title} du {$course->starts_at->translatedFormat('l j F \à H\hi')} est annulée.";

        // Annulation depuis « Mon compte » (client connecté, propriétaire de la réservation) : retour à son compte
        if ($booking->client_id !== null && Auth::guard('client')->id() === $booking->client_id) {
            return to_route('account.show')->with('account_success', $message);
        }

        return redirect('/#cours')->with('booking_success', $message);
    }
}
