<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Models\Client;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_client_connecte_n_a_pas_acces_a_l_espace_coach(): void
    {
        $client = Client::factory()->create();

        // actingAs($client, 'client') : simule une connexion sur le guard "client"
        $this->actingAs($client, 'client')
            ->get(route('coach.dashboard'))
            ->assertRedirect(route('coach.login'));
    }

    public function test_le_mot_de_passe_est_enregistre_hache(): void
    {
        $client = Client::create(['name' => 'Alice', 'email' => 'alice@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->assertNotSame('un-long-mot-de-passe', $client->getRawOriginal('password'));
    }

    public function test_supprimer_un_compte_garde_ses_reservations(): void
    {
        $course = Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle de test',
            'starts_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'capacity' => 5,
        ]);
        $client = Client::factory()->create();
        $booking = (new BookCourse)->handle($course, 'Alice', 'alice@test.be');
        $booking->client()->associate($client)->save(); // associate : remplit client_id

        $client->delete();

        // La réservation existe toujours, simplement détachée du compte (nullOnDelete)
        $this->assertNull($booking->fresh()->client_id);
    }
}
