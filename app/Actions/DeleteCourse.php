<?php

namespace App\Actions;

use App\Mail\CourseCancelled;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DeleteCourse
{
    /**
     * Supprime un cours (et ses réservations, par cascade) et prévient chaque inscrit par mail.
     *
     * @return int le nombre de personnes prévenues
     */
    public function handle(Course $course, ?string $coachMessage): int
    {
        return DB::transaction(function () use ($course, $coachMessage) {
            // Verrou : aucune réservation ne peut arriver pendant la suppression (elle ne serait pas prévenue)
            $course = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $bookings = $course->bookings()->get(); // lus AVANT la suppression

            $when = ucfirst($course->starts_at->translatedFormat('l j F \à H\hi'));

            foreach ($bookings as $booking) {
                // Valeurs simples, pas de modèle : elles survivront à la suppression
                Mail::to($booking->email)->send(new CourseCancelled(
                    $booking->name, $course->title, $when, $course->location, $coachMessage,
                ));
            }

            // cascadeOnDelete (migration bookings) : la base supprime aussi les réservations du cours
            $course->delete();

            return $bookings->count();
        });
    }
}
