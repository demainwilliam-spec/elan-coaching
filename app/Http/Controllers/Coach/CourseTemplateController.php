<?php

namespace App\Http\Controllers\Coach;

use App\Actions\GenerateCourses;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseTemplateRequest;
use App\Models\Course;
use App\Models\CourseTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

// Planning type : les créneaux habituels du coach (modèles des cours, pas de vrais cours)
class CourseTemplateController extends Controller
{
    // Période maximale d'une génération : évite de remplir l'agenda pour des années par une faute de frappe
    public const GENERATION_MAX_MONTHS = 6;

    public function index(): View
    {
        // Dernier cours de l'agenda (généré ou créé à la main) : la génération repart du lendemain par défaut,
        // pour prolonger l'agenda sans recréer les cours supprimés exprès (vacances, jours fériés)
        $lastCourse = Course::max('starts_at'); // max() : la plus grande valeur de la colonne (null si aucun cours)
        $from = $lastCourse ? Carbon::parse($lastCourse)->addDay()->startOfDay() : today();
        $from = $from->max(today()); // jamais dans le passé (si le dernier cours est déjà passé)

        return view('coach.templates.index', [
            'templates' => CourseTemplate::ordered()->get(),
            'lastCourse' => $lastCourse ? Carbon::parse($lastCourse) : null,
            'from' => $from,
            'until' => $from->copy()->addWeeks(4)->subDay(), // 4 semaines complètes
        ]);
    }

    public function generate(Request $request, GenerateCourses $generateCourses): RedirectResponse
    {
        $validated = $request->validate([
            'from' => 'required|date|after_or_equal:today',
            // after_or_equal:from : compare avec l'autre champ du formulaire
            'until' => 'required|date|after_or_equal:from|before_or_equal:'.today()->addMonths(self::GENERATION_MAX_MONTHS)->toDateString(),
        ], [
            'from.required' => 'Indiquez la date de début.',
            'from.date' => 'La date de début n\'est pas valide.',
            'from.after_or_equal' => 'La date de début ne peut pas être dans le passé.',
            'until.required' => 'Indiquez la date de fin.',
            'until.date' => 'La date de fin n\'est pas valide.',
            'until.after_or_equal' => 'La date de fin doit être après la date de début.',
            'until.before_or_equal' => 'Maximum '.self::GENERATION_MAX_MONTHS.' mois à l\'avance.',
        ]);

        $result = $generateCourses->handle(Carbon::parse($validated['from']), Carbon::parse($validated['until']));

        $message = match ($result['created']) {
            0 => 'Aucun cours créé.',
            1 => '1 cours créé.',
            default => "{$result['created']} cours créés.",
        };
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} ".($result['skipped'] > 1 ? 'existaient' : 'existait').' déjà.';
        }

        return to_route('coach.templates.index')->with('status', $message);
    }

    public function create(): View
    {
        // Mêmes valeurs par défaut qu'un nouveau cours
        return view('coach.templates.form', ['template' => new CourseTemplate([
            'duration_minutes' => 60,
            'capacity' => 26,
            'location' => config('coach.places.group'),
        ])]);
    }

    public function store(CourseTemplateRequest $request): RedirectResponse
    {
        $template = CourseTemplate::create($request->validated());

        return to_route('coach.templates.index')
            ->with('status', "Créneau du {$template->weekdayName()} à {$template->startTimeLabel()} ajouté.");
    }

    public function edit(CourseTemplate $template): View
    {
        return view('coach.templates.form', ['template' => $template]);
    }

    public function update(CourseTemplateRequest $request, CourseTemplate $template): RedirectResponse
    {
        $template->update($request->validated());

        // Les cours déjà créés ne changent pas : le modèle ne sert qu'aux prochaines générations
        return to_route('coach.templates.index')
            ->with('status', "Créneau du {$template->weekdayName()} à {$template->startTimeLabel()} modifié. Les cours déjà créés ne changent pas.");
    }

    public function destroy(CourseTemplate $template): RedirectResponse
    {
        $template->delete();

        return to_route('coach.templates.index')
            ->with('status', "Créneau du {$template->weekdayName()} à {$template->startTimeLabel()} supprimé. Les cours déjà créés restent dans l'agenda.");
    }
}
