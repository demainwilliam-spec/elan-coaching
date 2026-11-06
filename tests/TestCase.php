<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Les tests ne dépendent ni du serveur Vite (npm run dev) ni d'un build à jour :
        // @vite et Vite::asset() renvoient des valeurs factices
        $this->withoutVite();
    }
}
