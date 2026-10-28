<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function bookingForCourseStarting(string $startsIn): Booking
    {
        $course = Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle A',
            'starts_at' => now()->modify($startsIn),
            'duration_minutes' => 60,
            'capacity' => 5,
        ]);

        // Création directe : BookCourse refuse (à raison) les cours déjà passés
        return $course->bookings()->create([
            'name' => 'Alice',
            'email' => 'alice@test.be',
            'status' => BookingStatus::Confirmed,
        ]);
    }

    public function test_les_reservations_de_plus_de_6_mois_sont_supprimees(): void
    {
        $old = $this->bookingForCourseStarting('-7 months');
        $recent = $this->bookingForCourseStarting('-5 months');
        $upcoming = $this->bookingForCourseStarting('+3 days');

        // --model : limite la commande à ce modèle
        $this->artisan('model:prune', ['--model' => Booking::class])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
        $this->assertModelExists($upcoming);
        $this->assertDatabaseCount('courses', 3); // les cours sont gardés (pas de données personnelles)
    }

    public function test_le_nettoyage_est_planifie_chaque_jour(): void
    {
        // schedule:list : affiche les tâches planifiées et leur fréquence
        $this->artisan('schedule:list')
            ->expectsOutputToContain('model:prune')
            ->expectsOutputToContain('queue:prune-failed --hours=168')
            ->assertSuccessful();
    }
}
