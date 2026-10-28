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

    public function test_le_footer_et_les_formulaires_menent_aux_pages_legales(): void
    {
        $this->get('/')
            ->assertSee('href="'.route('legal.notice').'"', false)
            ->assertSee('href="'.route('legal.privacy').'"', false)
            ->assertSee('Vos données servent uniquement à vous répondre')
            ->assertSee('Vos données servent uniquement à gérer votre réservation');
    }
}
