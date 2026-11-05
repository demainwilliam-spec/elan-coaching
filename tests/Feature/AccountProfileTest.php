<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Actions\VerifyClientEmail;
use App\Enums\BookingStatus;
use App\Mail\EmailChanged;
use App\Mail\ResetPasswordEmail;
use App\Mail\VerifyAccountEmail;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountProfileTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(string $startsIn = '+3 days'): Course
    {
        return Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle de test',
            'starts_at' => now()->modify($startsIn),
            'duration_minutes' => 60,
            'capacity' => 5,
        ]);
    }

    // Réservation directe (BookCourse refuse les cours passés)
    private function bookingFor(Client $client, Course $course): Booking
    {
        return $course->bookings()->create([
            'client_id' => $client->id, 'first_name' => $client->first_name, 'last_name' => $client->last_name, 'email' => $client->email, 'status' => BookingStatus::Confirmed,
        ]);
    }

    public function test_changer_son_nom_ne_demande_pas_de_mot_de_passe_et_met_a_jour_les_reservations_a_venir(): void
    {
        $client = Client::factory()->create(['first_name' => 'Alice', 'last_name' => 'Dupont', 'email' => 'alice@test.be']);
        $upcoming = $this->bookingFor($client, $this->makeCourse('+3 days'));
        $past = $this->bookingFor($client, $this->makeCourse('-3 days'));

        $this->actingAs($client, 'client')
            ->put(route('account.profile.update'), ['first_name' => 'Alice', 'last_name' => 'Martin', 'email' => 'alice@test.be'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('account_success');

        $this->assertSame('Alice Martin', $client->fresh()->name);
        $this->assertSame('Alice Martin', $upcoming->fresh()->name);
        $this->assertSame('Alice Dupont', $past->fresh()->name); // l'historique n'est pas réécrit
    }

    public function test_changer_d_email_exige_le_mot_de_passe_actuel(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']);

        $this->actingAs($client, 'client')
            ->put(route('account.profile.update'), ['first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'nouvelle@test.be', 'current_password' => 'faux'])
            // assertSessionHasErrorsIn : erreur rangée dans le sac « profile »
            ->assertSessionHasErrorsIn('profile', 'current_password');

        $this->assertSame('alice@test.be', $client->fresh()->email);
    }

    public function test_changer_d_email_envoie_la_confirmation_et_previent_l_ancienne_adresse(): void
    {
        Mail::fake();
        $client = Client::factory()->create(['first_name' => 'Alice', 'email' => 'alice@test.be']);

        $this->actingAs($client, 'client')
            ->put(route('account.profile.update'), ['first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'Nouvelle@Test.be', 'current_password' => 'password'])
            ->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertSame('nouvelle@test.be', $client->email);
        $this->assertFalse($client->hasVerifiedEmail()); // à confirmer de nouveau

        Mail::assertQueued(VerifyAccountEmail::class, fn ($mail) => $mail->hasTo('nouvelle@test.be'));
        Mail::assertQueued(EmailChanged::class, fn ($mail) => $mail->hasTo('alice@test.be') && $mail->newEmail === 'nouvelle@test.be');
    }

    public function test_une_adresse_deja_utilisee_par_un_autre_compte_est_refusee(): void
    {
        Client::factory()->create(['email' => 'bob@test.be']);
        $client = Client::factory()->create(['email' => 'alice@test.be']);

        $this->actingAs($client, 'client')
            ->put(route('account.profile.update'), ['first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'bob@test.be', 'current_password' => 'password'])
            ->assertSessionHasErrorsIn('profile', 'email');
    }

    public function test_confirmer_la_nouvelle_adresse_y_fait_passer_les_reservations_a_venir(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']);
        $upcoming = $this->bookingFor($client, $this->makeCourse('+3 days'));
        $past = $this->bookingFor($client, $this->makeCourse('-3 days'));
        // Cours où la nouvelle adresse est déjà inscrite : la réservation du compte garde l'ancienne
        $conflict = $this->makeCourse('+5 days');
        $kept = $this->bookingFor($client, $conflict);
        (new BookCourse)->handle($conflict, 'Alice', 'Test', 'nouvelle@test.be');

        // forceFill : email_verified_at n'est pas dans $fillable, update() l'ignorerait sans rien dire
        $client->forceFill(['email' => 'nouvelle@test.be', 'email_verified_at' => null])->save();
        (new VerifyClientEmail)->handle($client);

        $this->assertSame('nouvelle@test.be', $upcoming->fresh()->email);
        $this->assertSame('alice@test.be', $past->fresh()->email);
        $this->assertSame('alice@test.be', $kept->fresh()->email);
    }

    public function test_le_profil_envoie_un_lien_pour_changer_de_mot_de_passe(): void
    {
        Mail::fake();
        $client = Client::factory()->create(['email' => 'alice@test.be']);

        $this->actingAs($client, 'client')
            ->post(route('account.profile.password'))
            ->assertSessionHas('account_success');

        Mail::assertQueued(ResetPasswordEmail::class, fn ($mail) => $mail->hasTo('alice@test.be'));

        // Deuxième demande dans la minute : refusée par le broker (throttle de 60 s)
        $this->post(route('account.profile.password'))->assertSessionHas('account_error');
        Mail::assertQueuedCount(1);
    }

    public function test_un_client_connecte_peut_utiliser_le_lien_recu(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']);
        $oldRememberToken = $client->remember_token;
        $token = Password::broker('clients')->createToken($client);

        // Connecté : la page du lien s'ouvre (elle n'est plus réservée aux visiteurs)
        $this->actingAs($client, 'client')
            ->get(route('account.password.reset', ['token' => $token, 'email' => 'alice@test.be']))
            ->assertOk();

        $this->post(route('account.password.update'), [
            'token' => $token, 'email' => 'alice@test.be', 'password' => 'nouveau-mdp', 'password_confirmation' => 'nouveau-mdp',
        ])->assertRedirect(route('account.show'));

        $client->refresh();
        $this->assertTrue(Hash::check('nouveau-mdp', $client->password));
        $this->assertNotSame($oldRememberToken, $client->remember_token);
    }

    public function test_supprimer_son_compte_exige_le_mot_de_passe(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($client, 'client')
            ->delete(route('account.destroy'), ['password' => 'faux'])
            ->assertSessionHasErrorsIn('deletion', 'password');

        $this->assertModelExists($client);
    }

    public function test_supprimer_son_compte_deconnecte_et_garde_les_reservations(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']);
        $booking = $this->bookingFor($client, $this->makeCourse());

        $this->actingAs($client, 'client')
            ->delete(route('account.destroy'), ['password' => 'password'])
            ->assertRedirect(route('account.deleted'));

        $this->assertModelMissing($client);
        $this->assertGuest('client');
        // La réservation reste, détachée du compte : la personne est toujours inscrite au cours
        $this->assertNull($booking->fresh()->client_id);
        $this->assertSame('alice@test.be', $booking->fresh()->email);
    }

    public function test_la_page_profil_est_reservee_au_client_connecte(): void
    {
        $this->get(route('account.profile'))->assertRedirect(route('account.login'));

        $this->actingAs(Client::factory()->create(['first_name' => 'Alice']), 'client')
            ->get(route('account.profile'))
            ->assertOk()
            ->assertSee('value="Alice"', false);
    }
}
