<?php

namespace App\Http\Controllers\Coach;

use App\Actions\DeleteCourse;
use App\Actions\UpdateCourse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function create(): View
    {
        // Cours vide (non enregistré), avec des valeurs par défaut pour préremplir le formulaire
        return view('coach.courses.form', ['course' => new Course(['duration_minutes' => 60, 'capacity' => 10])]);
    }

    public function duplicate(Course $course): View
    {
        // replicate() : copie du cours SANS id → le formulaire la traite comme un nouveau cours
        $copy = $course->replicate();
        // copy() : addWeek() modifierait sinon la date du cours d'origine (objet Carbon partagé)
        $copy->starts_at = $course->starts_at->copy()->addWeek();

        return view('coach.courses.form', ['course' => $copy]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = Course::create($request->validated());

        return to_route('coach.dashboard')->with('status', "Cours « {$course->title} » créé.");
    }

    public function edit(Course $course): View
    {
        return view('coach.courses.form', ['course' => $course]);
    }

    public function update(CourseRequest $request, Course $course, UpdateCourse $updateCourse): RedirectResponse
    {
        $course = $updateCourse->handle($course, $request->validated());

        $message = "Cours « {$course->title} » modifié.";
        if ($course->wasChanged(Course::NOTIFIABLE_FIELDS) && $course->bookings()->exists()) {
            $message .= ' Les inscrits sont prévenus par mail.';
        }

        return to_route('coach.dashboard')->with('status', $message);
    }

    // GET : page de confirmation (avec le champ "message pour les inscrits"), ne supprime rien
    public function delete(Course $course): View
    {
        return view('coach.courses.delete', [
            'course' => $course,
            'registered' => $course->bookings()->count(),
        ]);
    }

    public function destroy(Request $request, Course $course, DeleteCourse $deleteCourse): RedirectResponse
    {
        // validate() directement dans le contrôleur : un seul champ, pas besoin d'un FormRequest
        $validated = $request->validate(
            ['coach_message' => 'nullable|string|max:1000'],
            ['coach_message.max' => 'Le message ne doit pas dépasser 1000 caractères.'],
        );

        $notified = $deleteCourse->handle($course, $validated['coach_message'] ?? null);

        $message = "Cours « {$course->title} » supprimé.";
        if ($notified > 0) {
            $message .= " {$notified} ".($notified > 1 ? 'inscrits prévenus' : 'inscrit prévenu').' par mail.';
        }

        return to_route('coach.dashboard')->with('status', $message);
    }
}
