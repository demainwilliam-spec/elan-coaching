<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Coordonnées du coach (config/coach.php) : affichées aux bons endroits
class CoachInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_mentions_legales_contiennent_les_infos_obligatoires(): void
    {
        $this->get(route('legal.notice'))
            ->assertSee(config('coach.owner'))
            ->assertSee(config('coach.address.street'))
            ->assertSee('Numéro d\'entreprise (BCE) : '.config('coach.company_number'), false)
            ->assertSee('TVA : '.config('coach.vat'), false)
            ->assertSee('href="mailto:'.config('coach.email').'"', false)
            ->assertSee('href="tel:'.config('coach.phone.link').'"', false)
            ->assertSee(config('coach.places.individual'));

        // L'email du coach pour exercer ses droits RGPD
        $this->get(route('legal.privacy'))->assertSee('href="mailto:'.config('coach.email').'"', false);
    }

    public function test_l_accueil_affiche_le_vrai_numero_et_les_lieux(): void
    {
        $this->get('/')
            ->assertSee('href="tel:'.config('coach.phone.link').'"', false)
            ->assertDontSee('+32 476 00 00 00') // l'ancien numéro factice
            ->assertSee(config('coach.places.individual'))
            ->assertSee(config('coach.places.group'));
    }

    public function test_l_accueil_contient_des_donnees_structurees_valides(): void
    {
        $html = $this->get('/')->getContent();

        // preg_match : récupère le texte entre les balises <script type="application/ld+json">
        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches));
        $data = json_decode($matches[1], true); // null si le JSON était invalide

        $this->assertSame('LocalBusiness', $data['@type']);
        $this->assertSame(config('coach.phone.link'), $data['telephone']);
        $this->assertSame('Éghezée', $data['address']['addressLocality']);

        // Seulement sur l'accueil
        $this->get(route('legal.notice'))->assertDontSee('application/ld+json', false);
    }

    public function test_un_nouveau_cours_est_prerempli_avec_la_salle_habituelle(): void
    {
        $coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->actingAs($coach, 'web')
            ->get(route('coach.courses.create'))
            ->assertSee('value="'.e(config('coach.places.group')).'"', false);
    }
}
