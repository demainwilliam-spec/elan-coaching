<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Client;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Liste des comptes clients, côté coach
class CoachClientsTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);
    }

    private function bookFor(Client $client, string $startsIn): void
    {
        $course = Course::create([
            'title' => 'Cours', 'location' => 'Salle', 'starts_at' => now()->modify($startsIn),
            'duration_minutes' => 60, 'capacity' => 5,
        ]);

        $course->bookings()->create([
            'client_id' => $client->id, 'first_name' => $client->first_name, 'last_name' => $client->last_name,
            'email' => $client->email, 'status' => BookingStatus::Confirmed,
        ]);
    }

    public function test_la_page_est_reservee_au_coach(): void
    {
        $this->get(route('coach.clients'))->assertRedirect(route('coach.login'));

        $this->actingAs(Client::factory()->create(), 'client')
            ->get(route('coach.clients'))
            ->assertRedirect(route('coach.login'));
    }

    public function test_l_onglet_clients_est_dans_le_header_du_coach(): void
    {
        $this->actingAs($this->coach, 'web')
            ->get(route('coach.dashboard'))
            ->assertSee('href="'.route('coach.clients').'"', false);
    }

    public function test_les_clients_sont_tries_par_nom_puis_prenom(): void
    {
        foreach ([['Zoé', 'Martin'], ['Alice', 'Peeters'], ['Bruno', 'Martin']] as [$first, $last]) {
            Client::factory()->create(['first_name' => $first, 'last_name' => $last]);
        }

        $this->actingAs($this->coach, 'web')
            ->get(route('coach.clients'))
            ->assertOk()
            ->assertSee('3 comptes')
            ->assertSeeInOrder(['Martin Bruno', 'Martin Zoé', 'Peeters Alice']);
    }

    public function test_la_page_indique_si_l_adresse_est_confirmee(): void
    {
        Client::factory()->unverified()->create();

        $this->actingAs($this->coach, 'web')
            ->get(route('coach.clients'))
            ->assertSee('1 compte')
            ->assertSee('À confirmer');
    }

    public function test_seules_les_reservations_a_venir_sont_comptees(): void
    {
        $client = Client::factory()->create();
        $this->bookFor($client, '+3 days');
        $this->bookFor($client, '+4 days');
        $this->bookFor($client, '-3 days');

        $this->actingAs($this->coach, 'web')
            ->get(route('coach.clients'))
            ->assertSee('<td data-label="À venir">2</td>', false);
    }
}
