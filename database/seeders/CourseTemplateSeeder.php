<?php

namespace Database\Seeders;

use App\Models\CourseTemplate;
use Illuminate\Database\Seeder;

// Planning type réel du coach (vraies données, pas de démonstration) : utilisable aussi en production,
// seul : php artisan db:seed --class=CourseTemplateSeeder
class CourseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // [jour ISO, heure, titre, durée en minutes]
        $slots = [
            [2, '18:30', 'Cardio training', 60],
            [3, '18:00', 'Circuit training', 60],
            [3, '19:10', 'Circuit training', 60],
            [4, '18:00', 'Circuit training', 60],
        ];

        foreach ($slots as [$weekday, $time, $title, $duration]) {
            // updateOrCreate : cherche le créneau avec le 1er tableau, le crée s'il n'existe pas
            // (sinon le met à jour avec le 2e) → relancer le seeder ne crée pas de doublon
            CourseTemplate::updateOrCreate(
                ['weekday' => $weekday, 'start_time' => $time.':00'],
                [
                    'title' => $title,
                    'location' => config('coach.places.group'),
                    'duration_minutes' => $duration,
                    'capacity' => 26,
                ],
            );
        }
    }
}
