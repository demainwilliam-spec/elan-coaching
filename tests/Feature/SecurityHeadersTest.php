<?php

namespace Tests\Feature;

use Tests\TestCase;

// En-têtes de sécurité (middleware SecurityHeaders)
class SecurityHeadersTest extends TestCase
{
    public function test_les_pages_envoient_les_en_tetes_de_securite(): void
    {
        $response = $this->get('/mentions-legales');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_les_scripts_ne_peuvent_venir_que_du_site(): void
    {
        $csp = $this->get('/mentions-legales')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }

    public function test_aucune_vue_ne_contient_de_script_dans_un_attribut(): void
    {
        // La CSP bloque onclick="…", onsubmit="…" : passer par un fichier de resources/js (cf. confirm-forms.js)
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($views as $file) {
            if ($file->isFile() && ! str_contains($file->getPathname(), 'emails')) {
                $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=\s*"/', file_get_contents($file->getPathname()), $file->getFilename());
            }
        }
    }

    public function test_une_page_d_erreur_a_aussi_les_en_tetes(): void
    {
        $this->get('/page-qui-n-existe-pas')
            ->assertNotFound()
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_hsts_seulement_en_https(): void
    {
        $this->get('/mentions-legales')->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/mentions-legales')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}
