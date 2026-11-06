<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_url_inconnue_affiche_la_page_404_du_site(): void
    {
        $this->get('/cette-page-n-existe-pas')
            ->assertNotFound()
            ->assertSee('Page introuvable')
            ->assertSee("Retour à l'accueil", false); // false : texte écrit tel quel dans le gabarit, pas échappé
    }

    public function test_trop_de_tentatives_affiche_la_page_429_du_site(): void
    {
        // La connexion coach est limitée à 5 essais par minute (throttle:5,1)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post(route('coach.login'), ['email' => 'x@test.be', 'password' => 'mauvais']);
        }

        $response->assertStatus(429)->assertSee('Trop de tentatives');
    }

    public function test_une_erreur_serveur_affiche_la_page_500_sans_details(): void
    {
        // En local APP_DEBUG=true affiche la page de debug : on simule la production
        config(['app.debug' => false]);
        // Route créée uniquement pour ce test, qui plante volontairement
        Route::get('/test-plantage', fn () => throw new \RuntimeException('secret interne'));

        $this->get('/test-plantage')
            ->assertStatus(500)
            ->assertSee('un problème est survenu')
            ->assertDontSee('secret interne'); // le message technique ne doit jamais s'afficher au visiteur
    }
}
