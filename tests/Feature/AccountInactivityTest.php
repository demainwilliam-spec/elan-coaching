<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Mail\InactiveAccountWarning;
use App\Models\Client;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountInactivityTest extends TestCase
{
    use RefreshDatabase;

    // Client confirmé dont la dernière activité date d'il y a $months mois
    private function clientInactiveFor(int $months, array $attributes = []): Client
    {
        $client = Client::factory()->create($attributes);
        $client->forceFill(['last_active_at' => now()->subMonths($months)])->save();

        return $client;
    }

    public function test_un_nouveau_compte_est_actif_des_sa_creation(): void
    {
        $this->assertNotNull(Client::factory()->create()->last_active_at);
    }

    public function test_se_connecter_compte_comme_une_activite_et_annule_l_avertissement(): void
    {
        $client = $this->clientInactiveFor(11, ['email' => 'alice@test.be']);
        $client->forceFill(['inactivity_warned_at' => now()])->save();

        $this->post(route('account.login'), ['email' => 'alice@test.be', 'password' => 'password']);

        $client->refresh();
        $this->assertTrue($client->last_active_at->isToday());
        $this->assertNull($client->inactivity_warned_at);
    }

    public function test_reserver_avec_son_compte_compte_comme_une_activite(): void
    {
        $client = $this->clientInactiveFor(6, ['email' => 'alice@test.be']);
        $course = Course::create([
            'title' => 'Cross-training', 'location' => 'Salle A', 'starts_at' => now()->addDays(2),
            'duration_minutes' => 60, 'capacity' => 5,
        ]);

        (new BookCourse)->handle($course, 'Alice', 'Test', 'alice@test.be', $client);

        $this->assertTrue($client->fresh()->last_active_at->isToday());
    }

    public function test_les_clients_inactifs_depuis_11_mois_sont_avertis_une_seule_fois(): void
    {
        Mail::fake();
        $inactive = $this->clientInactiveFor(12, ['email' => 'inactif@test.be']);
        $this->clientInactiveFor(6, ['email' => 'actif@test.be']);
        $unverified = Client::factory()->unverified()->create(['email' => 'non-confirme@test.be']);
        $unverified->forceFill(['last_active_at' => now()->subMonths(12)])->save();

        $this->artisan('clients:warn-inactive')->assertSuccessful();

        Mail::assertQueued(InactiveAccountWarning::class, fn ($mail) => $mail->hasTo('inactif@test.be'));
        Mail::assertQueuedCount(1); // ni le client actif, ni le non confirmé (il a sa propre règle)
        $this->assertNotNull($inactive->fresh()->inactivity_warned_at);

        // Le lendemain : déjà averti, pas de second mail
        $this->artisan('clients:warn-inactive');
        Mail::assertQueuedCount(1);
    }

    public function test_un_compte_inactif_n_est_supprime_que_30_jours_apres_l_avertissement(): void
    {
        $warnedLongAgo = $this->clientInactiveFor(13);
        $warnedLongAgo->forceFill(['inactivity_warned_at' => now()->subDays(31)])->save();

        $warnedRecently = $this->clientInactiveFor(13);
        $warnedRecently->forceFill(['inactivity_warned_at' => now()->subDays(10)])->save();

        $neverWarned = $this->clientInactiveFor(24); // ex. compte ancien le jour de la mise en ligne
        $active = $this->clientInactiveFor(2);

        $this->artisan('model:prune', ['--model' => Client::class])->assertSuccessful();

        $this->assertModelMissing($warnedLongAgo);
        $this->assertModelExists($warnedRecently);
        $this->assertModelExists($neverWarned); // préavis de 30 jours garanti
        $this->assertModelExists($active);
    }

    public function test_le_circuit_complet_avertissement_puis_suppression(): void
    {
        Mail::fake();
        $client = Client::factory()->create();
        // Un jour de plus que 11 mois : la limite est stricte (« plus de » 11 mois)
        $client->forceFill(['last_active_at' => now()->subMonths(11)->subDay()])->save();

        $this->artisan('clients:warn-inactive');
        Mail::assertQueued(InactiveAccountWarning::class);

        // 29 jours plus tard : toujours là
        $this->travel(29)->days();
        $this->artisan('model:prune', ['--model' => Client::class]);
        $this->assertModelExists($client);

        // 31 jours après l'avertissement : supprimé
        $this->travel(2)->days();
        $this->artisan('model:prune', ['--model' => Client::class]);
        $this->assertModelMissing($client);
    }
}
