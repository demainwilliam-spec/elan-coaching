<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Réservation depuis l'agenda : réservée aux comptes à l'adresse confirmée
class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(int $capacity): Course
    {
        return Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle de test',
            'starts_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'capacity' => $capacity,
        ]);
    }

    // Réserve avec un compte confirmé ; route() construit l'URL /cours/{id}/reservations
    private function book(Course $course, string $email)
    {
        $client = Client::firstWhere('email', $email) ?? Client::factory()->create(['email' => $email]);

        return $this->actingAs($client, 'client')
            ->post(route('bookings.store', $course), ['booking' => ['course_id' => $course->id]]);
    }

    public function test_une_reservation_valide_est_enregistree_et_confirmee(): void
    {
        $course = $this->makeCourse(capacity: 5);

        $response = $this->book($course, 'alice@test.be');

        $response->assertRedirect('/#cours');
        $response->assertSessionHas('booking_success', fn (string $message) => str_contains($message, 'confirmée'));
        // assertDatabaseHas : vérifie qu'une ligne avec ces valeurs existe dans la table
        $this->assertDatabaseHas('bookings', ['course_id' => $course->id, 'email' => 'alice@test.be', 'status' => 'confirmed']);
    }

    public function test_le_message_indique_la_position_quand_le_cours_est_complet(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $this->book($course, 'alice@test.be');

        $response = $this->book($course, 'bob@test.be');

        $response->assertSessionHas('booking_success', fn (string $message) => str_contains($message, 'n°1'));
    }

    public function test_un_visiteur_sans_compte_ne_peut_pas_reserver(): void
    {
        $course = $this->makeCourse(capacity: 5);

        // Même en envoyant les anciens champs à la main
        $this->post(route('bookings.store', $course), [
            'booking' => ['course_id' => $course->id, 'first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'alice@test.be'],
        ])
            ->assertRedirect('/#cours')
            ->assertSessionHas('booking_error', 'Connectez-vous ou créez un compte pour réserver un cours.')
            ->assertSessionHasInput('booking.course_id', $course->id); // pour rouvrir la modale sur ce cours

        $this->assertSame(0, Booking::count());
    }

    public function test_un_compte_non_confirme_ne_peut_pas_reserver(): void
    {
        $course = $this->makeCourse(capacity: 5);

        $this->actingAs(Client::factory()->unverified()->create(), 'client')
            ->post(route('bookings.store', $course), ['booking' => ['course_id' => $course->id]])
            ->assertRedirect('/#cours')
            ->assertSessionHas('booking_error', fn (string $message) => str_contains($message, 'Confirmez d\'abord votre adresse'));

        $this->assertSame(0, Booking::count());
    }

    public function test_un_doublon_renvoie_un_message_d_erreur_et_l_id_du_cours(): void
    {
        $course = $this->makeCourse(capacity: 5);
        $this->book($course, 'alice@test.be');

        $response = $this->book($course, 'alice@test.be');

        $response->assertSessionHas('booking_error');
        $response->assertSessionHasInput('booking.course_id', $course->id); // withInput() : pour old()
    }

    public function test_un_cours_inexistant_renvoie_une_404(): void
    {
        $this->actingAs(Client::factory()->create(), 'client')
            ->post('/cours/999/reservations')
            ->assertNotFound();
    }
}
