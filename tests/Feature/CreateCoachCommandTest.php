<?php

namespace Tests\Feature;

use App\Mail\CoachResetPasswordEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CreateCoachCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_la_commande_cree_le_compte_et_envoie_le_lien(): void
    {
        // expectsQuestion : simule les réponses tapées dans le terminal, dans l'ordre des questions
        $this->artisan('coach:create')
            ->expectsQuestion('Email du coach', 'Marie@Test.be')
            ->expectsQuestion('Nom affiché', 'Marie')
            ->assertSuccessful();

        $user = User::firstWhere('email', 'marie@test.be');
        $this->assertSame('Marie', $user->name);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'marie@test.be']);
        Mail::assertQueued(CoachResetPasswordEmail::class, fn ($mail) => $mail->hasTo('marie@test.be'));
    }

    public function test_plusieurs_coachs_peuvent_coexister(): void
    {
        User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->artisan('coach:create')
            ->expectsQuestion('Email du coach', 'marie@test.be')
            ->expectsQuestion('Nom affiché', 'Marie')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 2);
        $this->assertTrue(Hash::check('un-long-mot-de-passe', User::firstWhere('email', 'jf@test.be')->password));
    }

    public function test_une_adresse_invalide_est_refusee(): void
    {
        $this->artisan('coach:create')
            ->expectsQuestion('Email du coach', 'pas-une-adresse')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
        Mail::assertNothingQueued();
    }

    public function test_un_compte_existant_recoit_le_lien_sans_changer_son_mot_de_passe(): void
    {
        User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'ancien-mot-de-passe']);

        $this->artisan('coach:create')
            ->expectsQuestion('Email du coach', 'jf@test.be')
            ->expectsConfirmation('Le compte jf@test.be existe déjà. Lui envoyer un lien pour changer son mot de passe ?', 'yes')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('ancien-mot-de-passe', User::first()->password));
        Mail::assertQueued(CoachResetPasswordEmail::class);
    }

    public function test_refuser_la_confirmation_n_envoie_rien(): void
    {
        User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'ancien-mot-de-passe']);

        $this->artisan('coach:create')
            ->expectsQuestion('Email du coach', 'jf@test.be')
            ->expectsConfirmation('Le compte jf@test.be existe déjà. Lui envoyer un lien pour changer son mot de passe ?', 'no')
            ->assertSuccessful();

        Mail::assertNothingQueued();
    }
}
