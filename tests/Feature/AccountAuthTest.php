<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAuthTest extends TestCase
{
    use RefreshDatabase;

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Alice', 'last_name' => 'Test',
            'email' => 'alice@test.be',
            'password' => 'mot-de-passe',
            'password_confirmation' => 'mot-de-passe',
        ], $overrides);
    }

    public function test_le_prenom_et_le_nom_sont_obligatoires_a_l_inscription(): void
    {
        $this->post(route('account.register'), $this->registrationData(['first_name' => '', 'last_name' => '']))
            ->assertSessionHasErrors(['first_name', 'last_name']);

        $this->assertSame(0, Client::count());
    }

    public function test_un_visiteur_peut_creer_un_compte_et_est_connecte(): void
    {
        $this->post(route('account.register'), $this->registrationData(['email' => '  Alice@Test.BE ']))
            ->assertRedirect(route('account.show'));

        $client = Client::sole(); // sole() : l'unique ligne de la table (échoue s'il y en a 0 ou plusieurs)
        $this->assertSame('alice@test.be', $client->email); // email normalisé
        $this->assertAuthenticatedAs($client, 'client');
        $this->assertGuest('web'); // aucune connexion côté coach
    }

    public function test_une_adresse_deja_inscrite_est_refusee_meme_avec_des_majuscules(): void
    {
        Client::factory()->create(['email' => 'alice@test.be']);

        $this->post(route('account.register'), $this->registrationData(['email' => 'ALICE@test.be']))
            ->assertSessionHasErrors(['email' => 'Un compte existe déjà avec cette adresse : connectez-vous.']);

        $this->assertSame(1, Client::count());
    }

    public function test_le_mot_de_passe_doit_etre_confirme_et_assez_long(): void
    {
        $this->post(route('account.register'), $this->registrationData(['password_confirmation' => 'autre-chose']))
            ->assertSessionHasErrors('password');

        $this->post(route('account.register'), $this->registrationData(['password' => 'court', 'password_confirmation' => 'court']))
            ->assertSessionHasErrors('password');

        $this->assertSame(0, Client::count());
    }

    public function test_un_client_peut_se_connecter_et_se_deconnecter(): void
    {
        $client = Client::factory()->create(['email' => 'alice@test.be']); // mot de passe de la factory : "password"

        $this->post(route('account.login'), ['email' => 'Alice@test.be', 'password' => 'password'])
            ->assertRedirect(route('account.show'));
        $this->assertAuthenticatedAs($client, 'client');

        $this->post(route('account.logout'))->assertRedirect('/');
        $this->assertGuest('client');
    }

    public function test_un_mauvais_mot_de_passe_est_refuse(): void
    {
        Client::factory()->create(['email' => 'alice@test.be']);

        $this->post(route('account.login'), ['email' => 'alice@test.be', 'password' => 'faux'])
            ->assertSessionHasErrors(['email' => 'Email ou mot de passe incorrect.']);
        $this->assertGuest('client');
    }

    public function test_le_compte_du_coach_ne_permet_pas_de_se_connecter_comme_client(): void
    {
        User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->post(route('account.login'), ['email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('client');
    }

    public function test_les_redirections_dependent_de_l_espace_demande(): void
    {
        // Non connecté : /compte mène à la connexion client, /coach à celle du coach
        $this->get(route('account.show'))->assertRedirect(route('account.login'));
        $this->get(route('coach.dashboard'))->assertRedirect(route('coach.login'));

        // Client déjà connecté : la page de connexion le renvoie vers son compte
        $this->actingAs(Client::factory()->create(), 'client')
            ->get(route('account.login'))
            ->assertRedirect(route('account.show'));
    }

    public function test_le_header_mene_a_la_connexion_ou_au_compte(): void
    {
        $this->get('/')->assertSee('aria-label="Se connecter à mon compte"', false);

        $this->actingAs(Client::factory()->create(['first_name' => 'Alice']), 'client')
            ->get('/')
            ->assertSee('aria-label="Mon compte"', false);
    }

    public function test_l_agenda_propose_de_creer_un_compte_ou_rappelle_qui_est_connecte(): void
    {
        $this->get('/')
            ->assertSee('Réservez plus vite avec un compte')
            ->assertSee(route('account.register'));

        $this->actingAs(Client::factory()->create(['first_name' => 'Alice']), 'client')
            ->get('/')
            ->assertSee('Connecté en tant que Alice')
            ->assertDontSee('Réservez plus vite avec un compte');
    }

    public function test_les_pages_du_compte_ne_sont_pas_indexees(): void
    {
        $this->get(route('account.login'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get(route('account.register'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        // Les pages publiques, elles, restent indexables
        $this->get('/')->assertDontSee('noindex', false);
    }
}
