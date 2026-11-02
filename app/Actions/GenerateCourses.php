<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\CourseTemplate;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Crée les vrais cours d'une période à partir du planning type (bouton « Créer les cours » du coach).
// Les cours créés sont des cours ordinaires : modifiables et supprimables un par un, sans lien avec le modèle.
class GenerateCourses
{
    /**
     * @return array{created: int, skipped: int} cours créés, et cours sautés car déjà dans l'agenda
     */
    public function handle(Carbon $from, Carbon $until): array
    {
        $templates = CourseTemplate::all();
        $created = 0;
        $skipped = 0;

        // Transaction : tout ou rien (pas d'agenda à moitié rempli en cas d'erreur)
        DB::transaction(function () use ($templates, $from, $until, &$created, &$skipped) {
            // CarbonPeriod : parcourt chaque jour entre les deux dates (incluses)
            foreach (CarbonPeriod::create($from->copy()->startOfDay(), $until->copy()->startOfDay()) as $day) {
                // dayOfWeekIso : 1 = lundi … 7 = dimanche, comme la colonne weekday
                foreach ($templates->where('weekday', $day->dayOfWeekIso) as $template) {
                    $startsAt = $day->copy()->setTimeFromTimeString($template->start_time);

                    // Créneau déjà passé (ex. aujourd'hui 18h30 alors qu'il est 20h) : ignoré, sans le compter
                    if ($startsAt->isPast()) {
                        continue;
                    }

                    // Anti-doublon : un cours existe déjà à cette date et cette heure (généré avant, ou créé à la main)
                    if (Course::where('starts_at', $startsAt)->exists()) {
                        $skipped++;

                        continue;
                    }

                    Course::create([
                        'title' => $template->title,
                        'description' => $template->description,
                        'location' => $template->location,
                        'starts_at' => $startsAt,
                        'duration_minutes' => $template->duration_minutes,
                        'capacity' => $template->capacity,
                    ]);
                    $created++;
                }
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }
}
