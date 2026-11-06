<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountBookingTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(int $capacity = 5, string $title = 'Cross-training'): Course
    {
        return Course::create([
            'title' => $title,
            'location' => 'Salle de test',
            'starts_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'capacity' => $capacity,
        ]);
    }

    public function test_un_client_confirme_reserve_sans_remplir_de_champ(): void
    {
        $course = $this->makeCourse();
        $client = Client::factory()->create(['first_name' => 'Alice', 'email' => 'alice@test.be']);

        $this->actingAs($client, 'client')
            ->post(route('bookings.store', $course), ['booking' => ['course_id' => $course->id]])
            ->assertRedirect('/#cours')
            ->assertSessionHas('booking_success');

        $booking = Booking::sole();
        $this->assertSame($client->id, $booking->client_id);
        $this->assertSame('Alice', $booking->first_name);
        $this->assertSame('alice@test.be', $booking->email);
    }

    public function test_un_client_confirme_ne_peut_pas_reserver_au_nom_d_un_autre(): void
    {
        $course = $this->makeCourse();
        $client = Client::factory()->create(['email' => 'alice@test.be']);

        // Champs ajoutés à la main dans la requête : ignorés, le compte fait foi
        $this->actingAs($client, 'client')
            ->post(route('bookings.store', $course), ['booking' => ['first_name' => 'Bob', 'last_name' => 'Test', 'email' => 'bob@test.be']]);

        $this->assertSame('alice@test.be', Booking::sole()->email);
    }

    public function test_un_client_non_confirme_remplit_le_formulaire_et_n_est_pas_rattache(): void
    {
        $course = $this->makeCourse();
        $client = Client::factory()->unverified()->create(['email' => 'alice@test.be']);

        // Sans champs : refusé, comme pour un visiteur
        $this->actingAs($client, 'client')
            ->post(route('bookings.store', $course), ['booking' => ['course_id' => $course->id]])
            ->assertSessionHasErrors(['booking.first_name', 'booking.last_name', 'booking.email']);

        $this->post(route('bookings.store', $course), ['booking' => ['first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'alice@test.be']])
            ->assertSessionHas('booking_success');

        // Pas encore rattachée : elle le sera à la confirmation de l'adresse (VerifyClientEmail)
        $this->assertNull(Booking::sole()->client_id);
    }

    public function test_une_reservation_sans_compte_est_rattachee_au_compte_confirme_de_meme_adresse(): void
    {
        $course = $this->makeCourse();
        $verified = Client::factory()->create(['email' => 'alice@test.be']);
        Client::factory()->unverified()->create(['email' => 'bob@test.be']);

        $alice = (new BookCourse)->handle($course, 'Alice', 'Test', 'Alice@Test.be');
        $bob = (new BookCourse)->handle($course, 'Bob', 'Test', 'bob@test.be');

        $this->assertSame($verified->id, $alice->client_id);
        $this->assertNull($bob->client_id); // compte non confirmé : pas de rattachement
    }

    public function test_un_compte_ne_reserve_qu_une_fois_meme_avec_une_autre_adresse(): void
    {
        $course = $this->makeCourse();
        $client = Client::factory()->create(['email' => 'nouvelle@test.be']);
        // Réservation faite avec l'ancienne adresse du compte
        $course->bookings()->create([
            'client_id' => $client->id, 'first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'ancienne@test.be', 'status' => BookingStatus::Confirmed,
        ]);

        $this->actingAs($client, 'client')
            ->post(route('bookings.store', $course))
            ->assertSessionHas('booking_error', 'Vous êtes déjà inscrit(e) à ce cours.');

        $this->assertSame(1, Booking::count());
    }

    public function test_l_agenda_montre_les_cours_ou_le_client_est_inscrit(): void
    {
        $confirmed = $this->makeCourse(capacity: 1, title: 'Cours confirmé');
        $waiting = $this->makeCourse(capacity: 1, title: 'Cours complet');
        $this->makeCourse(title: 'Autre cours');
        $client = Client::factory()->create(['email' => 'alice@test.be']);

        (new BookCourse)->handle($confirmed, 'Alice', 'Test', 'alice@test.be', $client);
        (new BookCourse)->handle($waiting, 'Bob', 'Test', 'bob@test.be');
        (new BookCourse)->handle($waiting, 'Alice', 'Test', 'alice@test.be', $client);

        $response = $this->actingAs($client, 'client')->get('/');

        $response->assertSee('Inscrit ✓')
            ->assertSee('En liste d&#039;attente', false) // l'apostrophe est échappée par Blade
            // Seul « Autre cours » garde son bouton
            ->assertSee('data-course-id="'.Course::where('title', 'Autre cours')->value('id').'"', false)
            ->assertDontSee('data-course-id="'.$confirmed->id.'"', false);
    }

    public function test_la_modale_s_adapte_au_visiteur_et_au_client(): void
    {
        $this->makeCourse();

        // Visiteur : champs vides + invitation à se connecter
        $this->get('/')
            ->assertSee('id="booking-first-name"', false)
            ->assertSee('Vous avez un compte ?');

        // Client confirmé : plus de champs, « Au nom de »
        $this->actingAs(Client::factory()->create(['first_name' => 'Alice']), 'client')
            ->get('/')
            ->assertSee('Au nom de')
            ->assertDontSee('id="booking-first-name"', false)
            ->assertDontSee('Vous avez un compte ?');
    }

    public function test_un_client_non_confirme_voit_le_formulaire_prerempli(): void
    {
        $this->makeCourse();

        $this->actingAs(Client::factory()->unverified()->create(['first_name' => 'Alice', 'last_name' => 'Dupont', 'email' => 'alice@test.be']), 'client')
            ->get('/')
            ->assertSee('Confirmez votre adresse email (lien reçu par mail) pour réserver en un clic.')
            ->assertSee('value="Alice"', false)
            ->assertSee('value="Dupont"', false)
            ->assertSee('value="alice@test.be"', false);
    }

    public function test_la_connexion_depuis_l_agenda_ramene_aux_cours(): void
    {
        Client::factory()->create(['email' => 'alice@test.be']);

        $this->get(route('account.login', ['retour' => 'cours']));
        $this->post(route('account.login'), ['email' => 'alice@test.be', 'password' => 'password'])
            ->assertRedirect(url('/').'#cours');
    }

    public function test_le_parametre_retour_n_accepte_pas_d_adresse_libre(): void
    {
        Client::factory()->create(['email' => 'alice@test.be']);

        $this->get(route('account.login', ['retour' => 'https://site-pirate.example']));
        $this->post(route('account.login'), ['email' => 'alice@test.be', 'password' => 'password'])
            ->assertRedirect(route('account.show'));
    }
}
