<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class RemoveCoachCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $boss;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->boss = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);
        $this->employee = User::create(['name' => 'Marie', 'email' => 'marie@test.be', 'password' => 'un-long-mot-de-passe']);
    }

    private function remove(string $answer = 'yes'): void
    {
        $this->artisan('coach:remove')
            ->expectsQuestion('Email du coach à supprimer', 'marie@test.be')
            ->expectsConfirmation('Supprimer le compte de Marie (marie@test.be) ?', $answer)
            ->assertSuccessful();
    }

    public function test_le_compte_et_son_lien_sont_supprimes(): void
    {
        Password::broker('users')->createToken($this->employee);

        $this->remove();

        $this->assertModelMissing($this->employee);
        $this->assertModelExists($this->boss);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'marie@test.be']);
    }

    public function test_les_cours_restent(): void
    {
        $course = Course::create([
            'title' => 'Cours', 'location' => 'Salle', 'starts_at' => now()->addDay(),
            'duration_minutes' => 60, 'capacity' => 5,
        ]);

        $this->remove();

        $this->assertModelExists($course);
    }

    public function test_une_session_ouverte_ne_survit_pas(): void
    {
        $this->post(route('coach.login'), ['email' => 'marie@test.be', 'password' => 'un-long-mot-de-passe']);
        $this->get(route('coach.dashboard'))->assertOk();

        $this->remove();

        // forgetGuards : oublie le compte gardé en mémoire, comme au début d'une vraie nouvelle requête
        Auth::forgetGuards();
        $this->get(route('coach.dashboard'))->assertRedirect(route('coach.login'));
    }

    public function test_refuser_la_confirmation_ne_supprime_rien(): void
    {
        $this->remove('no');

        $this->assertModelExists($this->employee);
    }

    public function test_une_adresse_inconnue_est_signalee(): void
    {
        $this->artisan('coach:remove')
            ->expectsQuestion('Email du coach à supprimer', 'inconnu@test.be')
            ->assertFailed();

        $this->assertDatabaseCount('users', 2);
    }

    public function test_le_dernier_compte_ne_peut_pas_etre_supprime(): void
    {
        $this->employee->delete();

        $this->artisan('coach:remove')
            ->expectsQuestion('Email du coach à supprimer', 'jf@test.be')
            ->assertFailed();

        $this->assertModelExists($this->boss);
    }
}
