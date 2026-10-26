<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    // Simule l'envoi du formulaire ; route() construit l'URL /cours/{id}/reservations
    private function book(Course $course, string $name, string $email)
    {
        return $this->post(route('bookings.store', $course), [
            'booking' => ['name' => $name, 'email' => $email],
        ]);
    }

    public function test_une_reservation_valide_est_enregistree_et_confirmee(): void
    {
        $course = $this->makeCourse(capacity: 5);

        $response = $this->book($course, 'Alice', 'alice@test.be');

        $response->assertRedirect('/#cours');
        $response->assertSessionHas('booking_success', fn (string $message) => str_contains($message, 'confirmée'));
        // assertDatabaseHas : vérifie qu'une ligne avec ces valeurs existe dans la table
        $this->assertDatabaseHas('bookings', ['course_id' => $course->id, 'email' => 'alice@test.be', 'status' => 'confirmed']);
    }

    public function test_le_message_indique_la_position_quand_le_cours_est_complet(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $this->book($course, 'Alice', 'alice@test.be');

        $response = $this->book($course, 'Bob', 'bob@test.be');

        $response->assertSessionHas('booking_success', fn (string $message) => str_contains($message, 'n°1'));
    }

    public function test_les_erreurs_de_validation_ne_touchent_pas_le_formulaire_de_contact(): void
    {
        $response = $this->book($this->makeCourse(capacity: 5), '', 'pas-un-email');

        $response->assertRedirect('/#cours');
        $response->assertSessionHasErrors(['booking.name', 'booking.email']);
        $response->assertSessionDoesntHaveErrors(['name', 'email']); // les champs du contact restent propres
    }

    public function test_un_doublon_renvoie_un_message_d_erreur_et_garde_la_saisie(): void
    {
        $course = $this->makeCourse(capacity: 5);
        $this->book($course, 'Alice', 'alice@test.be');

        $response = $this->book($course, 'Alice', 'alice@test.be');

        $response->assertSessionHas('booking_error');
        $response->assertSessionHasInput('booking.email', 'alice@test.be'); // withInput() : pour old()
    }

    public function test_un_cours_inexistant_renvoie_une_404(): void
    {
        $this->post('/cours/999/reservations', ['booking' => ['name' => 'Alice', 'email' => 'alice@test.be']])
            ->assertNotFound();
    }
}
