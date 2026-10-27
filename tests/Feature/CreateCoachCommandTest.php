<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateCoachCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_commande_cree_le_compte_avec_un_mot_de_passe_hashe(): void
    {
        // expectsQuestion : simule les réponses tapées dans le terminal, dans l'ordre des questions
        $this->artisan('coach:create')
            ->expectsQuestion('Email du coach', 'JF@Test.be')
            ->expectsQuestion('Nom affiché', 'JF')
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'un-long-mot-de-passe')
            ->expectsQuestion('Retapez le mot de passe', 'un-long-mot-de-passe')
            ->expectsOutput('Compte coach créé pour jf@test.be.')
            ->assertSuccessful();

        $user = User::firstWhere('email', 'jf@test.be');
        $this->assertNotSame('un-long-mot-de-passe', $user->password); // jamais stocké en clair
        $this->assertTrue(Hash::check('un-long-mot-de-passe', $user->password)); // mais il correspond bien
    }

    public function test_un_mot_de_passe_trop_court_est_refuse(): void
    {
        $this->artisan('coach:create')
            ->expectsQuestion('Email du coach', 'jf@test.be')
            ->expectsQuestion('Nom affiché', 'JF')
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'court')
            ->expectsQuestion('Retapez le mot de passe', 'court')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_la_commande_change_le_mot_de_passe_d_un_compte_existant(): void
    {
        User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'ancien-mot-de-passe']);

        $this->artisan('coach:create')
            ->expectsQuestion('Email du coach', 'jf@test.be')
            ->expectsConfirmation('Le compte jf@test.be existe déjà. Changer son mot de passe ?', 'yes')
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'nouveau-mot-de-passe')
            ->expectsQuestion('Retapez le mot de passe', 'nouveau-mot-de-passe')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', User::first()->password));
    }
}
