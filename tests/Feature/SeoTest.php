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
            ->assertSee('<meta name="description" content="Coaching sportif individuel', false)
            ->assertSee('<meta property="og:title" content="JF Coaching — Coach sportif', false)
            ->assertSee('<meta property="og:description" content="Coaching sportif individuel', false)
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
