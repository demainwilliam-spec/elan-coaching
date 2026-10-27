<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Mail\CourseUpdated;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class UpdateCourse
{
    public function __construct(private PromoteWaiting $promoteWaiting = new PromoteWaiting)
    {
    }

    /**
     * Modifie un cours ; prévient les inscrits si la date, la durée ou le lieu change,
     * et promeut la liste d'attente si la capacité augmente.
     *
     * @param  array<string, mixed>  $data  les champs validés par CourseRequest
     * @return Course le cours modifié (wasChanged() indique ce qui a changé)
     *
     * @throws ValidationException si la nouvelle capacité est inférieure au nombre de confirmés
     */
    public function handle(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {
            $course = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();

            // Comptage sous verrou : personne ne peut réserver entre cette vérification et l'enregistrement
            $confirmed = $course->bookings()->where('status', BookingStatus::Confirmed)->count();

            if ($data['capacity'] < $confirmed) {
                // ValidationException : Laravel la traite comme une erreur de formulaire
                // → retour au formulaire, message affiché sous le champ "capacity", saisie conservée
                throw ValidationException::withMessages([
                    'capacity' => "Impossible de descendre sous {$confirmed} : {$confirmed} personnes sont déjà confirmées.",
                ]);
            }

            $course->update($data);

            // wasChanged() : vrai seulement si la valeur enregistrée est réellement différente de l'ancienne
            if ($course->wasChanged(Course::NOTIFIABLE_FIELDS)) {
                foreach ($course->bookings as $booking) {
                    Mail::to($booking->email)->send(new CourseUpdated($booking));
                }
            }

            // Capacité augmentée → des places se libèrent pour la liste d'attente (sinon ne fait rien)
            $this->promoteWaiting->handle($course);

            return $course;
        });
    }
}
