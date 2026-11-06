<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Enums\BookingStatus;
use App\Mail\BookingPromoted;
use App\Mail\BookingReceived;
use App\Mail\BookingRemoved;
use App\Models\Booking;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CoachBookingsTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);
    }

    private function makeCourse(int $capacity = 1, string $startsIn = '+3 days'): Course
    {
        return Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle A',
            'starts_at' => now()->modify($startsIn),
            'duration_minutes' => 60,
            'capacity' => $capacity,
        ]);
    }

    private function book(Course $course, string $name): Booking
    {
        return (new BookCourse)->handle($course, $name, 'Test', strtolower($name).'@test.be');
    }

    public function test_la_page_liste_les_confirmes_et_la_liste_d_attente(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $this->book($course, 'Alice');
        $this->book($course, 'Bob');

        $this->actingAs($this->coach)
            ->get(route('coach.bookings.index', $course))
            ->assertOk()
            ->assertSeeInOrder(['Confirmés · 1 / 1', 'Alice', 'Liste d&#039;attente · 1', 'n°1', 'Bob'], false);
    }

    public function test_les_confirmes_sont_tries_par_nom_et_la_liste_d_attente_reste_par_arrivee(): void
    {
        $course = $this->makeCourse(capacity: 3);
        $action = new BookCourse;

        // Les 3 premiers sont confirmés, les 2 suivants en attente
        foreach ([['Zoé', 'Martin'], ['Alice', 'Peeters'], ['Bruno', 'Martin'], ['Yann', 'Zeller'], ['Adam', 'Adams']] as [$first, $last]) {
            $action->handle($course, $first, $last, strtolower($last.$first).'@test.be');
        }

        $this->actingAs($this->coach)
            ->get(route('coach.bookings.index', $course))
            ->assertSeeInOrder(['Martin Bruno', 'Martin Zoé', 'Peeters Alice', 'n°1', 'Zeller Yann', 'n°2', 'Adams Adam']);
    }

    public function test_le_coach_doit_indiquer_le_prenom_et_le_nom(): void
    {
        $course = $this->makeCourse(capacity: 5);

        $this->actingAs($this->coach)
            ->post(route('coach.bookings.store', $course), ['first_name' => '', 'last_name' => '', 'email' => 'alice@test.be'])
            ->assertSessionHasErrors(['first_name', 'last_name']);

        $this->assertSame(0, $course->bookings()->count());
    }

    public function test_le_coach_inscrit_quelqu_un_avec_les_regles_habituelles(): void
    {
        $course = $this->makeCourse(capacity: 1);

        $this->actingAs($this->coach)
            ->post(route('coach.bookings.store', $course), ['first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'alice@test.be'])
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'place confirmée'));

        $this->actingAs($this->coach)
            ->post(route('coach.bookings.store', $course), ['first_name' => 'Bob', 'last_name' => 'Test', 'email' => 'bob@test.be'])
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'n°1'));

        Mail::assertQueued(BookingReceived::class, 2); // chacun reçoit son lien d'annulation
    }

    public function test_un_doublon_ajoute_par_le_coach_est_refuse(): void
    {
        $course = $this->makeCourse(capacity: 5);
        $this->book($course, 'Alice');

        $this->actingAs($this->coach)
            ->post(route('coach.bookings.store', $course), ['first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'ALICE@test.be'])
            ->assertSessionHasErrors('email');
    }

    public function test_le_coach_retire_quelqu_un_meme_a_moins_de_24_h(): void
    {
        $course = $this->makeCourse(capacity: 1, startsIn: '+5 hours');
        $alice = $this->book($course, 'Alice');
        $bob = $this->book($course, 'Bob');

        $this->actingAs($this->coach)
            ->delete(route('coach.bookings.destroy', $alice))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'Bob Test passe'));

        $this->assertModelMissing($alice);
        $this->assertSame(BookingStatus::Confirmed, $bob->fresh()->status);
        Mail::assertQueued(BookingRemoved::class, fn (BookingRemoved $mail) => $mail->hasTo('alice@test.be'));
        Mail::assertQueued(BookingPromoted::class, fn (BookingPromoted $mail) => $mail->hasTo('bob@test.be'));
    }

    public function test_l_annulation_par_le_lien_n_envoie_pas_le_mail_du_coach(): void
    {
        $alice = $this->book($this->makeCourse(capacity: 5), 'Alice');

        $this->post(route('bookings.destroy', $alice->cancel_token));

        Mail::assertNotQueued(BookingRemoved::class); // la personne a annulé elle-même
    }

    public function test_un_visiteur_ne_peut_pas_retirer_quelqu_un(): void
    {
        $alice = $this->book($this->makeCourse(), 'Alice');

        $this->delete(route('coach.bookings.destroy', $alice))->assertRedirect(route('coach.login'));

        $this->assertModelExists($alice);
    }
}
