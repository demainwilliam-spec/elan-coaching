<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dates relatives à aujourd'hui : les cours restent "à venir" quel que soit le jour du seeding
        $crossTraining = Course::create([
            'title' => 'Cross-training',
            'description' => 'Séance intense mêlant cardio, force et gainage. Tous niveaux, intensité adaptée.',
            'location' => 'Salle municipale, 12 rue des Sports',
            'starts_at' => now()->addDays(2)->setTime(18, 30),
            'duration_minutes' => 60,
            'capacity' => 12,
        ]);
        $this->addBookings($crossTraining, 4, BookingStatus::Confirmed);

        // Presque plein : 1 place restante
        $renfo = Course::create([
            'title' => 'Renforcement musculaire',
            'description' => 'Travail ciblé du haut et du bas du corps, au poids du corps et avec élastiques.',
            'location' => 'Salle municipale, 12 rue des Sports',
            'starts_at' => now()->addDays(4)->setTime(12, 15),
            'duration_minutes' => 45,
            'capacity' => 8,
        ]);
        $this->addBookings($renfo, 7, BookingStatus::Confirmed);

        // Complet avec file d'attente
        $circuit = Course::create([
            'title' => 'Circuit training en plein air',
            'description' => 'Ateliers en extérieur, enchaînés en circuit. Prévoir une tenue adaptée à la météo.',
            'location' => 'Parc de la Source, entrée principale',
            'starts_at' => now()->addDays(6)->setTime(10, 0),
            'duration_minutes' => 75,
            'capacity' => 10,
        ]);
        $this->addBookings($circuit, 10, BookingStatus::Confirmed);
        $this->addBookings($circuit, 3, BookingStatus::Waiting);

        // Aucune réservation
        Course::create([
            'title' => 'Mobilité & stretching',
            'description' => null,
            'location' => 'Salle municipale, 12 rue des Sports',
            'starts_at' => now()->addDays(9)->setTime(19, 0),
            'duration_minutes' => 45,
            'capacity' => 15,
        ]);

        // Cours plus lointains, pour avoir plusieurs pages (4 cours par page)
        // Chaque entrée : [titre, jours à partir d'aujourd'hui, heure, minute, durée, places, réservations confirmées]
        $laterCourses = [
            ['Cross-training', 11, 18, 30, 60, 12, 2],
            ['Renforcement musculaire', 13, 12, 15, 45, 8, 3],
            ['Circuit training en plein air', 15, 10, 0, 75, 10, 5],
            ['Mobilité & stretching', 18, 19, 0, 45, 15, 1],
            ['Cross-training', 20, 18, 30, 60, 12, 0],
        ];

        // Déstructuration : chaque élément du sous-tableau est rangé dans sa variable
        foreach ($laterCourses as [$title, $days, $hour, $minute, $duration, $capacity, $booked]) {
            $course = Course::create([
                'title' => $title,
                'description' => null,
                'location' => 'Salle municipale, 12 rue des Sports',
                'starts_at' => now()->addDays($days)->setTime($hour, $minute),
                'duration_minutes' => $duration,
                'capacity' => $capacity,
            ]);
            $this->addBookings($course, $booked, BookingStatus::Confirmed);
        }

        // Cours passé : ne doit pas apparaître sur la page
        $past = Course::create([
            'title' => 'Cross-training',
            'description' => 'Séance intense mêlant cardio, force et gainage. Tous niveaux, intensité adaptée.',
            'location' => 'Salle municipale, 12 rue des Sports',
            'starts_at' => now()->subDays(3)->setTime(18, 30),
            'duration_minutes' => 60,
            'capacity' => 12,
        ]);
        $this->addBookings($past, 12, BookingStatus::Confirmed);
    }

    // Crée $count réservations avec des noms/emails générés par Faker (en français)
    private function addBookings(Course $course, int $count, BookingStatus $status): void
    {
        for ($i = 0; $i < $count; $i++) {
            $course->bookings()->create([
                'name' => fake('fr_FR')->name(),
                'email' => fake('fr_FR')->unique()->safeEmail(), // unique() : jamais deux fois la même adresse
                'status' => $status,
            ]);
        }
    }
}
