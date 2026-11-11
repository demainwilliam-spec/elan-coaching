<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_pages_legales_s_affichent(): void
    {
        $this->get(route('legal.notice'))->assertOk()->assertSee('Conditions de réservation');
        $this->get(route('legal.privacy'))->assertOk()->assertSee('Autorité de protection des données');
    }

    public function test_la_confidentialite_decrit_le_compte_client(): void
    {
        $this->get(route('legal.privacy'))
            ->assertSee('Compte client (facultatif)')
            // Durée tirée de la constante du modèle : la page suit si la règle change
            ->assertSee('supprimé automatiquement après '.\App\Models\Client::UNVERIFIED_RETENTION_DAYS.' jours')
            ->assertSee('remember_client_');

        // La page d'inscription renvoie bien vers la politique de confidentialité
        $this->get(route('account.register'))->assertSee('href="'.route('legal.privacy').'"', false);
    }

    public function test_le_site_ne_charge_rien_depuis_google_fonts(): void
    {
        // La page de confidentialité ne cite comme tiers que l'hébergeur et la messagerie du coach
        $this->get('/')->assertDontSee('fonts.googleapis.com')->assertDontSee('fonts.gstatic.com');
        $this->get(route('coach.login'))->assertDontSee('fonts.googleapis.com');
    }

    public function test_le_footer_et_les_formulaires_menent_aux_pages_legales(): void
    {
        $this->get('/')
            ->assertSee('href="'.route('legal.notice').'"', false)
            ->assertSee('href="'.route('legal.privacy').'"', false)
            ->assertSee('Vos données servent uniquement à vous répondre')
            ->assertSee('Vos données servent uniquement à gérer votre réservation');
    }
}
