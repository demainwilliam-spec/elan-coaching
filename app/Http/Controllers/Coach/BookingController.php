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
        // partition() : coupe la collection en deux selon la condition → [ceux qui la respectent, les autres]
        // Destructuration [$a, $b] = ... : chaque morceau dans sa variable
        [$confirmed, $waiting] = $course->bookings()
            ->orderBy('id')
            ->get()
            ->partition(fn (Booking $booking) => $booking->status === BookingStatus::Confirmed);

        return view('coach.bookings.index', [
            'course' => $course,
            'confirmed' => $confirmed,
            'waiting' => $waiting->values(), // values() : renumérote 0, 1, 2... → position = index + 1
        ]);
    }

    public function store(Request $request, Course $course, BookCourse $bookCourse): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email:rfc,filter|max:254',
        ], [
            'name.required' => 'Indiquez le nom.',
            'email.required' => 'Indiquez l\'email : la personne y recevra son lien d\'annulation.',
            'email.email' => 'Cette adresse email n\'est pas valide.',
        ]);

        try {
            // Mêmes règles qu'une réservation en ligne (places, doublon, mail avec lien d'annulation)
            $booking = $bookCourse->handle($course, $validated['name'], $validated['email']);
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
            // byCoach: true → argument nommé : pas de limite des 24 h, et la personne est prévenue par mail
            $promoted = $cancelBooking->handle($booking, byCoach: true);
        } catch (BookingException $e) {
            return back()->with('status', $e->getMessage()); // déjà retiré (double clic)
        }

        $message = "{$booking->name} est retiré(e) du cours et prévenu(e) par mail.";
        if ($promoted !== null) {
            $message .= " {$promoted->name} passe de la liste d'attente à une place confirmée.";
        }

        return back()->with('status', $message);
    }
}
