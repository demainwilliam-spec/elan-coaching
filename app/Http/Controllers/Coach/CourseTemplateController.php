<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseTemplateRequest;
use App\Models\CourseTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// Planning type : les créneaux habituels du coach (modèles des cours, pas de vrais cours)
class CourseTemplateController extends Controller
{
    public function index(): View
    {
        return view('coach.templates.index', ['templates' => CourseTemplate::ordered()->get()]);
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
