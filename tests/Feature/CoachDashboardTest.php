<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoachDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(string $title, string $startsIn, int $capacity = 5): Course
    {
        return Course::create([
            'title' => $title,
            'location' => 'Salle de test',
            'starts_at' => now()->modify($startsIn),
            'duration_minutes' => 60,
            'capacity' => $capacity,
        ]);
    }

    public function test_le_tableau_de_bord_liste_les_cours_a_venir_avec_leurs_chiffres(): void
    {
        $course = $this->makeCourse('Cross-training', '+2 days', capacity: 1);
        (new BookCourse)->handle($course, 'Alice', 'alice@test.be');
        (new BookCourse)->handle($course, 'Bob', 'bob@test.be');
        $this->makeCourse('Cours passé', '-2 days');

        $coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->actingAs($coach)
            ->get(route('coach.dashboard'))
            ->assertOk()
            ->assertSee('Cross-training')
            ->assertSee('1 / 1 confirmés')
            ->assertSee('1 en attente')
            ->assertDontSee('Cours passé');
    }
}
