<?php

namespace Tests\Feature;

use App\Mail\CoachResetPasswordEmail;
use App\Mail\ResetPasswordEmail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

// Mot de passe oublié de l'espace coach
class CoachPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coach = User::create(['name' => 'Marie', 'email' => 'marie@test.be', 'password' => 'ancien-mot-de-passe']);
    }

    private function resetWith(string $token, string $password = 'nouveau-mot-de-passe'): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('coach.password.update'), [
            'token' => $token,
            'email' => $this->coach->email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    public function test_la_page_de_connexion_propose_le_lien(): void
    {
        $this->get(route('coach.login'))->assertSee(route('coach.password.request'), false);
    }

    public function test_le_lien_est_envoye_au_coach(): void
    {
        Mail::fake();

        $this->post(route('coach.password.request'), ['email' => 'MARIE@test.be '])
            ->assertSessionHas('status');

        Mail::assertQueued(CoachResetPasswordEmail::class, fn ($mail) => $mail->hasTo('marie@test.be'));
    }

    public function test_une_adresse_inconnue_recoit_la_meme_reponse_sans_mail(): void
    {
        Mail::fake();

        $this->post(route('coach.password.request'), ['email' => 'inconnu@test.be'])
            ->assertSessionHas('status');

        Mail::assertNothingQueued();
    }

    public function test_un_client_ne_recoit_pas_de_lien_coach(): void
    {
        Mail::fake();
        Client::factory()->create(['email' => 'client@test.be']);

        $this->post(route('coach.password.request'), ['email' => 'client@test.be']);

        Mail::assertNotQueued(CoachResetPasswordEmail::class);
        Mail::assertNotQueued(ResetPasswordEmail::class);
    }

    public function test_le_mail_contient_le_lien(): void
    {
        $mail = new CoachResetPasswordEmail($this->coach, 'jeton-test');

        $mail->assertSeeInHtml('Bonjour Marie,');
        $mail->assertSeeInHtml('/coach/reinitialisation/jeton-test');
    }

    public function test_le_lien_change_le_mot_de_passe_et_connecte_le_coach(): void
    {
        $oldRemember = $this->coach->remember_token;
        $token = Password::broker('users')->createToken($this->coach);

        $this->resetWith($token)->assertRedirect(route('coach.dashboard'));

        $this->coach->refresh();
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $this->coach->password));
        $this->assertNotSame($oldRemember, $this->coach->remember_token);
        $this->assertAuthenticatedAs($this->coach, 'web');
    }

    public function test_le_lien_ne_sert_qu_une_fois(): void
    {
        $token = Password::broker('users')->createToken($this->coach);

        $this->resetWith($token);
        auth('web')->logout();

        $this->resetWith($token, 'encore-un-autre-mot-de-passe')
            ->assertRedirect(route('coach.password.request'))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $this->coach->refresh()->password));
    }

    public function test_un_lien_invalide_est_refuse(): void
    {
        $this->resetWith('faux-jeton')->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('ancien-mot-de-passe', $this->coach->refresh()->password));
        $this->assertGuest('web');
    }

    public function test_le_mot_de_passe_doit_faire_12_caracteres(): void
    {
        $token = Password::broker('users')->createToken($this->coach);

        $this->resetWith($token, 'trop-court')->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('ancien-mot-de-passe', $this->coach->refresh()->password));
    }
}
