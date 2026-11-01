<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_accueil_a_une_description_et_une_carte_de_partage(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<title>JF Coaching — Coach sportif à Éghezée', false) // référencement local : la ville dans le titre
            ->assertSee('<meta name="description" content="Coach sportif à Éghezée', false)
            ->assertSee('<meta property="og:title" content="JF Coaching — Coach sportif à Éghezée', false)
            ->assertSee('<meta property="og:description" content="Coach sportif à Éghezée', false)
            ->assertSee('<h1>Coach sportif à Éghezée</h1>', false) // titre principal : 2e signal SEO après <title>
            ->assertSee('<main>', false) // accessibilité : repère du contenu principal
            ->assertSee('<link rel="icon" href="/favicon.ico"', false)
            ->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png">', false);
    }

    public function test_le_carrousel_affiche_les_photos_avec_texte_alternatif(): void
    {
        $this->get('/')
            ->assertSee('alt="Le coach grimpe à la corde', false)
            ->assertSee('alt="Le coach soulève un haltère', false)
            ->assertSee('alt="Le coach guide un client', false)
            // assertSeeInOrder : ces morceaux apparaissent dans cet ordre (peu importe ce qu'il y a entre)
            // → la 1re photo est chargée tout de suite, les suivantes à l'approche
            ->assertSeeInOrder(['width="444"', 'loading="eager"', 'width="689"', 'loading="lazy"', 'width="597"', 'loading="lazy"'], false);
    }

    public function test_la_presentation_du_coach_precede_les_photos(): void
    {
        $this->get('/')->assertSeeInOrder([
            '<section class="about">',
            '<h2>Votre coach</h2>',
            'Mon objectif : vous aider à progresser',
            'data-photo-carousel',
        ], false);
    }

    public function test_les_cartes_de_services_menent_au_contact_et_a_l_agenda(): void
    {
        $this->get('/')
            ->assertSeeInOrder([
                'href="#contact" class="service-card" data-reveal data-service="le coaching individuel"',
                'href="#cours" class="service-card" data-reveal>',
            ], false)
            ->assertDontSee('Petit groupe')
            ->assertDontSee('À distance');
    }

    public function test_les_reseaux_sont_dans_le_header_et_le_footer(): void
    {
        $instagram = '<a href="https://www.instagram.com/j.f.coaching/" target="_blank" rel="noopener"';
        $facebook = '<a href="https://www.facebook.com/JFCoachingpro/" target="_blank" rel="noopener"';

        // Deux fois chacun, dans l'ordre : header puis footer
        $this->get('/')->assertSeeInOrder(['<header>', $instagram, $facebook, '<footer>', $instagram, $facebook], false);
    }

    public function test_le_sitemap_liste_les_pages_publiques_en_xml_valide(): void
    {
        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml');

        // simplexml_load_string : renvoie false si le XML est mal formé
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $locs = [];
        foreach ($xml->url as $url) {
            $locs[] = (string) $url->loc;
        }
        $this->assertSame([route('home'), route('legal.notice'), route('legal.privacy')], $locs);
    }

    public function test_l_adresse_canonique_ignore_les_parametres(): void
    {
        // Un lien partagé avec un suivi publicitaire (?utm_source=...) reste la même page pour Google
        $this->get('/?utm_source=facebook')
            ->assertSee('<link rel="canonical" href="'.url('/').'">', false);
    }
}
