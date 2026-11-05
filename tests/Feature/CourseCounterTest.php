<?php

namespace Tests\Feature;

use App\Actions\VerifyClientEmail;
use App\Enums\BookingStatus;
use App\Mail\BookingPromoted;
use App\Mail\BookingRemoved;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

// Compteur de cours faits par client et retrait d'un absent après le cours
class CourseCounterTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->client = Client::factory()->create(['email' => 'alice@test.be']);
    }

    private function makeCourse(string $startsAt): Course
    {
        return Course::create([
            'title' => 'Cours', 'location' => 'Salle', 'starts_at' => now()->modify($startsAt),
            'duration_minutes' => 60, 'capacity' => 5,
        ]);
    }

    private function book(Course $course, BookingStatus $status = BookingStatus::Confirmed, ?int $clientId = null, string $email = 'alice@test.be'): Booking
    {
        return $course->bookings()->create([
            'client_id' => $clientId ?? $this->client->id, 'first_name' => 'Alice', 'last_name' => 'Test',
            'email' => $email, 'status' => $status,
        ]);
    }

    private function runCounter(): void
    {
        $this->artisan('clients:count-courses')->assertSuccessful();
    }

    public function test_un_cours_termine_est_compte_une_seule_fois(): void
    {
        $this->book($this->makeCourse('-2 hours'));

        $this->runCounter();
        $this->runCounter(); // le planificateur repasse chaque heure

        $this->assertSame(1, $this->client->fresh()->courses_done);
    }

    public function test_un_cours_a_venir_ou_encore_en_cours_n_est_pas_compte(): void
    {
        $this->book($this->makeCourse('+1 day'));
        $this->book($this->makeCourse('-30 minutes'), email: 'alice2@test.be'); // 60 min : pas encore fini

        $this->runCounter();

        $this->assertSame(0, $this->client->fresh()->courses_done);
    }

    public function test_une_personne_restee_en_liste_d_attente_n_est_pas_comptee(): void
    {
        $this->book($this->makeCourse('-2 hours'), BookingStatus::Waiting);

        $this->runCounter();

        $this->assertSame(0, $this->client->fresh()->courses_done);
    }

    public function test_le_compteur_survit_a_la_suppression_des_vieilles_reservations(): void
    {
        $this->book($this->makeCourse('-7 months'));
        $this->runCounter();

        $this->artisan('model:prune')->assertSuccessful();

        $this->assertSame(0, Booking::count());
        $this->assertSame(1, $this->client->fresh()->courses_done);
    }

    public function test_une_reservation_rattachee_a_la_confirmation_du_compte_est_comptee(): void
    {
        $client = Client::factory()->unverified()->create(['email' => 'bob@test.be']);
        // Réservation faite sans compte, avant la création du compte
        $booking = $this->makeCourse('-2 hours')->bookings()->create([
            'first_name' => 'Bob', 'last_name' => 'Test', 'email' => 'bob@test.be', 'status' => BookingStatus::Confirmed,
        ]);

        $this->runCounter();
        $this->assertSame(0, $client->fresh()->courses_done);

        (new VerifyClientEmail)->handle($client);
        $this->runCounter();

        $this->assertSame($client->id, $booking->fresh()->client_id);
        $this->assertSame(1, $client->fresh()->courses_done);
    }

    public function test_retirer_un_absent_apres_le_cours_fait_baisser_le_compteur_sans_mail(): void
    {
        $course = $this->makeCourse('-2 hours');
        $absent = $this->book($course);
        $this->book($course, BookingStatus::Waiting, email: 'attente@test.be');
        $this->runCounter();

        $coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->actingAs($coach, 'web')
            ->delete(route('coach.bookings.destroy', $absent))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'sans mail'));

        $this->assertModelMissing($absent);
        $this->assertSame(0, $this->client->fresh()->courses_done);
        Mail::assertNotQueued(BookingRemoved::class);
        Mail::assertNotQueued(BookingPromoted::class); // la liste d'attente ne reçoit pas de place pour un cours fini
    }

    public function test_la_page_clients_affiche_le_compteur(): void
    {
        $this->book($this->makeCourse('-2 hours'));
        $this->runCounter();

        $coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->actingAs($coach, 'web')
            ->get(route('coach.clients'))
            ->assertSee('<td data-label="Cours faits">1</td>', false);
    }

    public function test_les_cours_termines_sont_accessibles_au_coach_seulement(): void
    {
        $this->get(route('coach.courses.finished'))->assertRedirect(route('coach.login'));

        $finished = $this->makeCourse('-2 hours');
        $finished->update(['title' => 'Cours fini']);
        $this->makeCourse('+1 day')->update(['title' => 'Cours futur']);

        $coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->actingAs($coach, 'web')
            ->get(route('coach.dashboard'))
            ->assertSee('href="'.route('coach.courses.finished').'"', false);

        $this->get(route('coach.courses.finished'))
            ->assertSee('Cours fini')
            ->assertDontSee('Cours futur');

        // Les inscrits d'un cours terminé : plus de formulaire d'inscription
        $this->get(route('coach.bookings.index', $finished))
            ->assertOk()
            ->assertDontSee('Inscrire quelqu&#039;un', false)
            ->assertSee('Retour aux cours terminés');
    }
}
