<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordEmail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function resetData(string $token, string $email, array $overrides = []): array
    {
        return array_merge([
            'token' => $token,
            'email' => $email,
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ], $overrides);
    }

    public function test_la_demande_envoie_un_lien_au_client(): void
    {
        Mail::fake();
        Client::factory()->create(['email' => 'alice@test.be']);

        $this->post(route('account.password.request'), ['email' => 'Alice@test.be'])
            ->assertSessionHas('account_success');

        Mail::assertQueued(ResetPasswordEmail::class, fn ($mail) => $mail->hasTo('alice@test.be'));
        $this->assertDatabaseHas('client_password_reset_tokens', ['email' => 'alice@test.be']);
    }

    public function test_une_adresse_inconnue_recoit_la_meme_reponse_sans_mail(): void
    {
        Mail::fake();

        $this->post(route('account.password.request'), ['email' => 'inconnu@test.be'])
            ->assertSessionHas('account_success', 'Si un compte existe avec cette adresse, un lien de réinitialisation vient de lui être envoyé.');

        Mail::assertNothingQueued();
    }

    public function test_le_coach_ne_peut_pas_recevoir_de_lien_client(): void
    {
        Mail::fake();
        User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->post(route('account.password.request'), ['email' => 'jf@test.be']);

        Mail::assertNothingQueued();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'jf@test.be']); // table du coach intacte
    }

    public function test_le_lien_permet_de_changer_le_mot_de_passe_et_connecte_le_client(): void
    {
        $client = Client::factory()->unverified()->create(['email' => 'alice@test.be']);
        // createToken : le jeton tel qu'il serait mis dans le mail
        $token = Password::broker('clients')->createToken($client);

        $this->get(route('account.password.reset', ['token' => $token, 'email' => 'alice@test.be']))
            ->assertOk()
            ->assertSee('value="'.$token.'"', false);

        $this->post(route('account.password.update'), $this->resetData($token, 'alice@test.be'))
            ->assertRedirect(route('account.show'));

        $client->refresh();
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $client->password));
        $this->assertTrue($client->hasVerifiedEmail()); // le lien prouve aussi l'adresse
        $this->assertAuthenticatedAs($client, 'client');
    }

    public function test_un_lien_ne_sert_qu_une_fois(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']);
        $token = Password::broker('clients')->createToken($client);

        $this->post(route('account.password.update'), $this->resetData($token, 'alice@test.be'));
        $this->post(route('account.logout')); // déconnecté, pour pouvoir réutiliser le formulaire (guest:client)

        $this->post(route('account.password.update'), $this->resetData($token, 'alice@test.be', [
            'password' => 'troisieme-essai', 'password_confirmation' => 'troisieme-essai',
        ]))->assertSessionHas('account_error');

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $client->fresh()->password));
    }

    public function test_un_jeton_faux_ou_expire_est_refuse(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']);

        $this->post(route('account.password.update'), $this->resetData('faux-jeton', 'alice@test.be'))
            ->assertRedirect(route('account.password.request'))
            ->assertSessionHas('account_error');

        $token = Password::broker('clients')->createToken($client);
        $this->travel(61)->minutes();
        $this->post(route('account.password.update'), $this->resetData($token, 'alice@test.be'))
            ->assertSessionHas('account_error');

        $this->assertTrue(Hash::check('password', $client->fresh()->password)); // inchangé
        $this->assertGuest('client');
    }

    public function test_la_page_de_connexion_mene_au_mot_de_passe_oublie(): void
    {
        $this->get(route('account.login'))->assertSee(route('account.password.request'));
    }
}
