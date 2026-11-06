<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Enums\BookingStatus;
use App\Models\Client;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountReservationsTest extends TestCase
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

    public function test_mon_compte_liste_les_reservations_a_venir_par_date(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']);
        $later = $this->makeCourse('Cours de la semaine prochaine', '+7 days');
        $soon = $this->makeCourse('Cours de demain', '+2 days');
        $past = $this->makeCourse('Cours passé', '-2 days');
        $notMine = $this->makeCourse('Cours d\'un autre', '+3 days');

        (new BookCourse)->handle($later, 'Alice', 'Test', 'alice@test.be', $client);
        (new BookCourse)->handle($soon, 'Alice', 'Test', 'alice@test.be', $client);
        $past->bookings()->create(['client_id' => $client->id, 'first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'alice@test.be', 'status' => BookingStatus::Confirmed]);
        (new BookCourse)->handle($notMine, 'Bob', 'Test', 'bob@test.be');

        $this->actingAs($client, 'client')
            ->get(route('account.show'))
            ->assertOk()
            // assertSeeInOrder : les textes apparaissent dans cet ordre (tri par date)
            ->assertSeeInOrder(['Cours de demain', 'Cours de la semaine prochaine'])
            ->assertDontSee('Cours passé')
            ->assertDontSee('Cours d&#039;un autre', false);
    }

    public function test_le_statut_et_la_position_en_attente_sont_affiches(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']);
        $full = $this->makeCourse('Cours complet', '+2 days', capacity: 1);
        (new BookCourse)->handle($full, 'Bob', 'Test', 'bob@test.be');
        (new BookCourse)->handle($full, 'Chloé', 'Test', 'chloe@test.be');
        (new BookCourse)->handle($full, 'Alice', 'Test', 'alice@test.be', $client);

        $this->actingAs($client, 'client')
            ->get(route('account.show'))
            ->assertSee("Liste d'attente (n°2)", false); // texte écrit dans la vue, hors {{ }} : pas échappé
    }

    public function test_aucune_reservation_affiche_un_message(): void
    {
        $this->actingAs(Client::factory()->create(), 'client')
            ->get(route('account.show'))
            ->assertSee('Aucune réservation à venir.');
    }

    public function test_le_bouton_annuler_disparait_apres_midi_le_jour_du_cours(): void
    {
        $this->travelTo(now()->setTime(13, 0));
        $client = Client::factory()->create(['email' => 'alice@test.be']);
        $open = (new BookCourse)->handle($this->makeCourse('Dans 3 jours', '+3 days'), 'Alice', 'Test', 'alice@test.be', $client);
        $closed = (new BookCourse)->handle($this->makeCourse('Ce soir', '+5 hours'), 'Alice', 'Test', 'alice@test.be', $client);

        $this->actingAs($client, 'client')
            ->get(route('account.show'))
            ->assertSee(route('bookings.cancel', $open->cancel_token))
            ->assertDontSee(route('bookings.cancel', $closed->cancel_token))
            ->assertSee('Délai dépassé : contactez le coach');
    }

    public function test_annuler_depuis_mon_compte_ramene_au_compte(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']);
        $booking = (new BookCourse)->handle($this->makeCourse('Cross-training', '+3 days'), 'Alice', 'Test', 'alice@test.be', $client);

        $this->actingAs($client, 'client')
            ->get(route('bookings.cancel', $booking->cancel_token))
            ->assertSee(route('account.show')); // « Garder ma réservation » ramène au compte

        $this->post(route('bookings.destroy', $booking->cancel_token))
            ->assertRedirect(route('account.show'))
            ->assertSessionHas('account_success');

        $this->assertModelMissing($booking);
    }

    public function test_un_autre_client_connecte_qui_annule_par_le_lien_revient_a_l_agenda(): void
    {
        $booking = (new BookCourse)->handle($this->makeCourse('Cross-training', '+3 days'), 'Bob', 'Test', 'bob@test.be');

        $this->actingAs(Client::factory()->create(), 'client')
            ->post(route('bookings.destroy', $booking->cancel_token))
            ->assertRedirect('/#cours');
    }
}
