<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Actions\CancelBooking;
use App\Mail\BookingPromoted;
use App\Mail\BookingReceived;
use App\Models\Client;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

// Case « Recevoir un mail de confirmation à chaque réservation » (Mon profil)
class BookingEmailsPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function makeCourse(int $capacity = 5): Course
    {
        return Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle de test',
            'starts_at' => now()->addDays(3)->setTime(18, 0),
            'duration_minutes' => 60,
            'capacity' => $capacity,
        ]);
    }

    public function test_un_nouveau_compte_recoit_les_mails_par_defaut(): void
    {
        $client = Client::factory()->create();

        $this->assertTrue($client->booking_emails);
        $this->assertTrue($client->fresh()->booking_emails); // relu depuis la base
    }

    public function test_le_client_coche_et_decoche_la_case(): void
    {
        $client = Client::factory()->create();

        // Case décochée : seul le champ caché (0) est envoyé
        $this->actingAs($client, 'client')
            ->put(route('account.profile.emails'), ['booking_emails' => '0'])
            ->assertSessionHas('account_success');
        $this->assertFalse($client->fresh()->booking_emails);

        $this->put(route('account.profile.emails'), ['booking_emails' => '1']);
        $this->assertTrue($client->fresh()->booking_emails);
    }

    public function test_une_valeur_inattendue_est_refusee(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($client, 'client')
            ->put(route('account.profile.emails'), ['booking_emails' => 'peut-etre'])
            ->assertSessionHasErrors('booking_emails');
        $this->assertTrue($client->fresh()->booking_emails);
    }

    public function test_le_profil_affiche_la_case_selon_le_choix(): void
    {
        $this->actingAs(Client::factory()->create(), 'client')
            ->get(route('account.profile'))
            ->assertSee('name="booking_emails" value="1" checked', false);

        $this->actingAs(Client::factory()->create(['email' => 'bob@test.be'])->forceFill(['booking_emails' => false]), 'client')
            ->get(route('account.profile'))
            ->assertDontSee('name="booking_emails" value="1" checked', false);
    }

    public function test_pas_de_mail_de_confirmation_si_la_case_est_decochee(): void
    {
        $course = $this->makeCourse();
        $client = Client::factory()->create(['email' => 'alice@test.be']);
        $client->forceFill(['booking_emails' => false])->save();

        $this->actingAs($client, 'client')
            ->post(route('bookings.store', $course))
            ->assertSessionHas('booking_success'); // la réservation est bien faite

        Mail::assertNotQueued(BookingReceived::class);
    }

    public function test_le_mail_de_confirmation_part_si_la_case_est_cochee(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']);

        $this->actingAs($client, 'client')->post(route('bookings.store', $this->makeCourse()));

        Mail::assertQueued(BookingReceived::class, fn (BookingReceived $mail) => $mail->hasTo('alice@test.be'));
    }

    public function test_l_inscription_par_le_coach_respecte_le_choix_du_compte_de_meme_adresse(): void
    {
        Client::factory()->create(['email' => 'alice@test.be'])->forceFill(['booking_emails' => false])->save();

        // Comme le coach depuis son espace : sans compte, rattaché au compte confirmé de même adresse
        (new BookCourse)->handle($this->makeCourse(), 'Alice', 'Test', 'alice@test.be');
        Mail::assertNotQueued(BookingReceived::class);

        // Personne sans compte : le mail part toujours
        (new BookCourse)->handle($this->makeCourse(), 'Bob', 'Test', 'bob@test.be');
        Mail::assertQueued(BookingReceived::class, fn (BookingReceived $mail) => $mail->hasTo('bob@test.be'));
    }

    public function test_la_place_liberee_est_toujours_annoncee(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $first = (new BookCourse)->handle($course, 'Bob', 'Test', 'bob@test.be');
        $client = Client::factory()->create(['email' => 'alice@test.be']);
        $client->forceFill(['booking_emails' => false])->save();
        (new BookCourse)->handle($course, 'Alice', 'Test', 'alice@test.be', $client); // liste d'attente

        (new CancelBooking)->handle($first);

        // Place due passé midi : la personne promue doit le savoir, même sans mails de confirmation
        Mail::assertQueued(BookingPromoted::class, fn (BookingPromoted $mail) => $mail->hasTo('alice@test.be'));
    }
}
