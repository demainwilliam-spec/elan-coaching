<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Mail\VerifyAccountEmail;
use App\Models\Client;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountVerificationTest extends TestCase
{
    use RefreshDatabase;

    // Le lien tel qu'il est mis dans le mail
    private function verificationUrl(Client $client, int $minutes = 60): string
    {
        return URL::temporarySignedRoute(
            'account.verification.verify',
            now()->addMinutes($minutes),
            ['client' => $client->id, 'hash' => sha1($client->email)],
        );
    }

    private function makeCourse(): Course
    {
        return Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle de test',
            'starts_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'capacity' => 5,
        ]);
    }

    public function test_l_inscription_envoie_le_mail_de_confirmation(): void
    {
        Mail::fake(); // intercepte les mails au lieu de les envoyer

        $this->post(route('account.register'), [
            'first_name' => 'Alice', 'last_name' => 'Test',
            'email' => 'alice@test.be',
            'password' => 'mot-de-passe',
            'password_confirmation' => 'mot-de-passe',
        ]);

        // assertQueued : mis en queue (le Mailable est ShouldQueue), à la bonne adresse
        Mail::assertQueued(VerifyAccountEmail::class, fn ($mail) => $mail->hasTo('alice@test.be'));
        $this->assertFalse(Client::sole()->hasVerifiedEmail());
    }

    public function test_le_mail_contient_un_lien_qui_confirme_l_adresse(): void
    {
        $client = Client::factory()->unverified()->create();

        $html = (new VerifyAccountEmail($client))->render();
        $this->assertStringContainsString('/compte/verification/'.$client->id.'/', $html);
        $this->assertStringContainsString('signature=', $html);
    }

    public function test_le_lien_confirme_l_adresse_et_rattache_les_reservations(): void
    {
        $course = $this->makeCourse();
        $mine = $course->bookings()->create(['first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'alice@test.be', 'status' => BookingStatus::Confirmed]);
        $other = $course->bookings()->create(['first_name' => 'Bob', 'last_name' => 'Test', 'email' => 'bob@test.be', 'status' => BookingStatus::Confirmed]);
        $client = Client::factory()->unverified()->create(['email' => 'alice@test.be']);

        // Sans être connecté : retour vers la page de connexion, avec le message de succès
        $this->get($this->verificationUrl($client))
            ->assertRedirect(route('account.login'))
            ->assertSessionHas('account_success');

        $this->assertTrue($client->fresh()->hasVerifiedEmail());
        $this->assertSame($client->id, $mine->fresh()->client_id);
        $this->assertNull($other->fresh()->client_id); // la réservation d'une autre adresse n'est pas touchée
    }

    public function test_connecte_le_lien_ramene_au_compte(): void
    {
        $client = Client::factory()->unverified()->create();

        $this->actingAs($client, 'client')
            ->get($this->verificationUrl($client))
            ->assertRedirect(route('account.show'));
    }

    public function test_un_lien_modifie_ou_expire_ne_confirme_rien(): void
    {
        $client = Client::factory()->unverified()->create();
        $other = Client::factory()->unverified()->create();

        // Id remplacé dans un lien valide : la signature ne correspond plus
        $tampered = str_replace('/verification/'.$client->id.'/', '/verification/'.$other->id.'/', $this->verificationUrl($client));
        $this->get($tampered)->assertSessionHas('account_error');

        // Lien expiré : on avance l'horloge de 61 minutes
        $url = $this->verificationUrl($client);
        $this->travel(61)->minutes();
        $this->get($url)->assertSessionHas('account_error');

        $this->assertFalse($client->fresh()->hasVerifiedEmail());
        $this->assertFalse($other->fresh()->hasVerifiedEmail());
    }

    public function test_un_lien_pour_une_ancienne_adresse_ne_confirme_pas_la_nouvelle(): void
    {
        $client = Client::factory()->unverified()->create(['email' => 'ancienne@test.be']);
        $url = $this->verificationUrl($client);
        $client->update(['email' => 'nouvelle@test.be']);

        $this->get($url)->assertSessionHas('account_error');
        $this->assertFalse($client->fresh()->hasVerifiedEmail());
    }

    public function test_mon_compte_propose_de_renvoyer_le_mail_tant_que_l_adresse_n_est_pas_confirmee(): void
    {
        Mail::fake();
        $client = Client::factory()->unverified()->create();

        $this->actingAs($client, 'client')
            ->get(route('account.show'))
            ->assertSee('Confirmez votre adresse email');

        $this->post(route('account.verification.send'))->assertSessionHas('account_success');
        Mail::assertQueued(VerifyAccountEmail::class);

        $this->actingAs(Client::factory()->create(), 'client') // compte confirmé
            ->get(route('account.show'))
            ->assertDontSee('Confirmez votre adresse email');
    }

    public function test_les_comptes_jamais_confirmes_sont_supprimes_apres_7_jours(): void
    {
        $old = Client::factory()->unverified()->create(['created_at' => now()->subDays(8)]);
        $recent = Client::factory()->unverified()->create(['created_at' => now()->subDays(6)]);
        $verified = Client::factory()->create(['created_at' => now()->subYear()]);

        $this->artisan('model:prune', ['--model' => Client::class])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
        $this->assertModelExists($verified);
    }
}
