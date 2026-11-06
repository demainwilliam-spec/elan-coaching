<?php

namespace App\Http\Controllers\Coach;

use App\Actions\BookCourse;
use App\Actions\CancelBooking;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Les inscrits d'un cours, côté coach (à ne pas confondre avec App\Http\Controllers\BookingController, côté public)
class BookingController extends Controller
{
    public function index(Course $course): View
    {
        return view('coach.bookings.index', [
            'course' => $course,
            // Par nom de famille : la collation de MariaDB ignore les majuscules et les accents (É rangé avec E)
            'confirmed' => $course->bookings()
                ->where('status', BookingStatus::Confirmed)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            // Par ordre d'arrivée : c'est lui qui décide qui reçoit la prochaine place libre
            'waiting' => $course->bookings()
                ->where('status', BookingStatus::Waiting)
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request, Course $course, BookCourse $bookCourse): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email:rfc,filter|max:254',
        ], [
            'first_name.required' => 'Indiquez le prénom.',
            'last_name.required' => 'Indiquez le nom de famille.',
            'email.required' => 'Indiquez l\'email : la personne y recevra son lien d\'annulation.',
            'email.email' => 'Cette adresse email n\'est pas valide.',
        ]);

        try {
            // Mêmes règles qu'une réservation en ligne (places, doublon, mail avec lien d'annulation)
            $booking = $bookCourse->handle($course, $validated['first_name'], $validated['last_name'], $validated['email']);
        } catch (BookingException $e) {
            return back()->withErrors(['email' => $e->getMessage()])->withInput();
        }

        $status = $booking->status === BookingStatus::Confirmed
            ? 'place confirmée'
            : "liste d'attente, n°{$booking->waitingPosition()}";

        return back()->with('status', "{$booking->name} inscrit(e) : {$status}. Un mail de confirmation est envoyé.");
    }

    public function destroy(Booking $booking, CancelBooking $cancelBooking): RedirectResponse
    {
        try {
            // byCoach: true → argument nommé : pas de limite de midi, et la personne est prévenue par mail
            $promoted = $cancelBooking->handle($booking, byCoach: true);
        } catch (BookingException $e) {
            return back()->with('status', $e->getMessage()); // déjà retiré (double clic)
        }

        // Absent retiré après le cours : aucun mail n'est parti (cf. CancelBooking)
        if ($booking->course->hasEnded()) {
            return back()->with('status', "{$booking->name} est retiré(e) de ce cours terminé, sans mail. Son compteur de cours est à jour.");
        }

        $message = "{$booking->name} est retiré(e) du cours et prévenu(e) par mail.";
        if ($promoted !== null) {
            $message .= " {$promoted->name} passe de la liste d'attente à une place confirmée.";
        }

        return back()->with('status', $message);
    }
}
