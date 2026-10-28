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
            ->assertSee('<main>', false); // accessibilité : repère du contenu principal
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
