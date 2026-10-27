<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoachLoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeCoach(): User
    {
        return User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);
    }

    public function test_un_visiteur_est_renvoye_vers_la_connexion(): void
    {
        $this->get('/coach')->assertRedirect(route('coach.login'));
    }

    public function test_la_page_de_connexion_n_est_pas_indexee(): void
    {
        $this->get(route('coach.login'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_le_coach_se_connecte_avec_le_bon_mot_de_passe(): void
    {
        $coach = $this->makeCoach();

        $this->post(route('coach.login'), ['email' => 'JF@test.be', 'password' => 'un-long-mot-de-passe'])
            ->assertRedirect(route('coach.dashboard'));

        $this->assertAuthenticatedAs($coach);
    }

    public function test_un_mauvais_mot_de_passe_est_refuse(): void
    {
        $this->makeCoach();

        $this->post(route('coach.login'), ['email' => 'jf@test.be', 'password' => 'mauvais'])
            ->assertSessionHasErrors('email');

        $this->assertGuest(); // personne n'est connecté
    }

    public function test_la_connexion_est_bloquee_apres_5_essais(): void
    {
        $this->makeCoach();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('coach.login'), ['email' => 'jf@test.be', 'password' => 'mauvais']);
        }

        // 6e essai, même avec le bon mot de passe : 429 Too Many Requests
        $this->post(route('coach.login'), ['email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe'])
            ->assertStatus(429);
    }

    public function test_le_coach_connecte_accede_au_tableau_de_bord(): void
    {
        // actingAs() : simule un utilisateur déjà connecté, sans passer par le formulaire
        $this->actingAs($this->makeCoach())
            ->get(route('coach.dashboard'))
            ->assertOk()
            ->assertSee('Bonjour JF');
    }

    public function test_le_coach_connecte_qui_ouvre_la_connexion_va_au_tableau_de_bord(): void
    {
        $this->actingAs($this->makeCoach())
            ->get(route('coach.login'))
            ->assertRedirect(route('coach.dashboard'));
    }

    public function test_la_deconnexion_ramene_sur_le_site(): void
    {
        $this->actingAs($this->makeCoach())
            ->post(route('coach.logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
