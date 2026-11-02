<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CourseTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Planning type du coach : liste, ajout, modification, suppression des créneaux
class CoachTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);
    }

    private function formData(array $overrides = []): array
    {
        return array_merge([
            'weekday' => 2,
            'start_time' => '18:30',
            'title' => 'Cardio training',
            'description' => '',
            'location' => 'Salle de test',
            'duration_minutes' => 60,
            'capacity' => 26,
        ], $overrides);
    }

    public function test_le_planning_est_reserve_au_coach(): void
    {
        $this->get(route('coach.templates.index'))->assertRedirect(route('coach.login'));

        $this->actingAs(Client::factory()->create(), 'client')
            ->get(route('coach.templates.index'))
            ->assertRedirect(route('coach.login'));
    }

    public function test_le_planning_liste_les_creneaux_dans_l_ordre_de_la_semaine(): void
    {
        $this->seed(\Database\Seeders\CourseTemplateSeeder::class);

        $this->actingAs($this->coach, 'web')
            ->get(route('coach.templates.index'))
            ->assertOk()
            ->assertSeeInOrder(['Mardi · 18h30', 'Mercredi · 18h00', 'Mercredi · 19h10', 'Jeudi · 18h00']);
    }

    public function test_le_coach_ajoute_un_creneau(): void
    {
        $this->actingAs($this->coach, 'web')
            ->post(route('coach.templates.store'), $this->formData())
            ->assertRedirect(route('coach.templates.index'))
            ->assertSessionHas('status', 'Créneau du Mardi à 18h30 ajouté.');

        $template = CourseTemplate::sole();
        $this->assertSame(2, $template->weekday);
        $this->assertSame('18:30:00', $template->start_time); // secondes ajoutées par le mutateur
    }

    public function test_deux_creneaux_le_meme_jour_a_la_meme_heure_sont_refuses(): void
    {
        CourseTemplate::create($this->formData());

        $this->actingAs($this->coach, 'web')
            ->post(route('coach.templates.store'), $this->formData(['title' => 'Autre cours']))
            ->assertSessionHasErrors(['weekday' => 'Il y a déjà un créneau ce jour-là à cette heure.']);

        // Même heure un autre jour : accepté
        $this->post(route('coach.templates.store'), $this->formData(['weekday' => 4]))->assertSessionHasNoErrors();
        $this->assertSame(2, CourseTemplate::count());
    }

    public function test_un_jour_ou_une_heure_invalide_est_refuse(): void
    {
        $this->actingAs($this->coach, 'web')
            ->post(route('coach.templates.store'), $this->formData(['weekday' => 8, 'start_time' => '25:00']))
            ->assertSessionHasErrors(['weekday', 'start_time']);
    }

    public function test_le_coach_modifie_un_creneau_sans_que_son_heure_compte_comme_un_doublon(): void
    {
        $template = CourseTemplate::create($this->formData());

        // Même jour et même heure qu'avant : ce n'est pas un doublon (ignore() dans la règle unique)
        $this->actingAs($this->coach, 'web')
            ->get(route('coach.templates.edit', $template))
            ->assertSee('value="18:30"', false);

        $this->put(route('coach.templates.update', $template), $this->formData(['capacity' => 20]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('coach.templates.index'));

        $this->assertSame(20, $template->fresh()->capacity);
    }

    public function test_le_coach_supprime_un_creneau(): void
    {
        $template = CourseTemplate::create($this->formData());

        $this->actingAs($this->coach, 'web')
            ->delete(route('coach.templates.destroy', $template))
            ->assertRedirect(route('coach.templates.index'));

        $this->assertModelMissing($template);
    }

    public function test_un_nouveau_creneau_est_prerempli(): void
    {
        $this->actingAs($this->coach, 'web')
            ->get(route('coach.templates.create'))
            ->assertOk()
            ->assertSee('value="26"', false)
            ->assertSee('value="'.e(config('coach.places.group')).'"', false);
    }
}
