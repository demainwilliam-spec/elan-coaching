<?php

namespace App\Http\Controllers;

use App\Actions\BookCourse;
use App\Exceptions\BookingException;
use App\Http\Requests\BookingRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;

class BookingController extends Controller
{
    // Course $course : "route model binding", Laravel charge le cours dont l'id est dans l'URL (404 s'il n'existe pas)
    // BookCourse $bookCourse : injection de dépendance, Laravel crée l'objet tout seul
    public function store(BookingRequest $request, Course $course, BookCourse $bookCourse): RedirectResponse
    {
        try {
            $booking = $bookCourse->handle(
                $course,
                $request->validated('booking.name'),
                $request->validated('booking.email'),
            );
        } catch (BookingException $e) {
            // Refus métier : on rouvre le formulaire avec la saisie et le message
            return redirect('/#cours')
                ->withInput()
                ->with('booking_error', $e->getMessage());
        }

        $when = $course->starts_at->translatedFormat('l j F \à H\hi'); // ex. "mercredi 28 octobre à 18h30"

        $message = $booking->waitingPosition()
            ? "Le cours {$course->title} du {$when} est complet : vous êtes n°{$booking->waitingPosition()} sur la liste d'attente. Vous serez prévenu(e) par mail si une place se libère."
            : "C'est réservé ! Votre place pour le cours {$course->title} du {$when} est confirmée.";

        // Clé "booking_success" (et pas "success") : celle-ci est déjà lue par le formulaire de contact
        return redirect('/#cours')->with('booking_success', $message);
    }
}
