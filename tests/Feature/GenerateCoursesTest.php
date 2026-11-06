<?php

namespace Tests\Feature;

use App\Actions\GenerateCourses;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\CourseTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// Génération des cours à partir du planning type (mardi 18h30, mercredi 18h00 et 19h10, jeudi 18h00)
class GenerateCoursesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-16 10:00')); // un lundi matin
        $this->seed(CourseTemplateSeeder::class);
    }

    private function generate(string $from, string $until): array
    {
        return (new GenerateCourses)->handle(Carbon::parse($from), Carbon::parse($until));
    }

    public function test_deux_semaines_creent_huit_cours_aux_bons_jours_et_heures(): void
    {
        $this->assertSame(['created' => 8, 'skipped' => 0], $this->generate('2026-11-16', '2026-11-29'));

        $this->assertSame([
            '2026-11-17 18:30', '2026-11-18 18:00', '2026-11-18 19:10', '2026-11-19 18:00',
            '2026-11-24 18:30', '2026-11-25 18:00', '2026-11-25 19:10', '2026-11-26 18:00',
        ], Course::orderBy('starts_at')->get()->map(fn ($c) => $c->starts_at->format('Y-m-d H:i'))->all());

        $course = Course::orderBy('starts_at')->first();
        $this->assertSame('Cardio training', $course->title);
        $this->assertSame(26, $course->capacity);
        $this->assertSame(config('coach.places.group'), $course->location);
    }

    public function test_relancer_la_meme_periode_ne_cree_pas_de_doublon(): void
    {
        $this->generate('2026-11-16', '2026-11-22');

        $this->assertSame(['created' => 0, 'skipped' => 4], $this->generate('2026-11-16', '2026-11-22'));
        $this->assertSame(4, Course::count());
    }

    public function test_un_cours_cree_a_la_main_au_meme_moment_est_garde(): void
    {
        Course::create([
            'title' => 'Cours spécial', 'location' => 'Ailleurs', 'starts_at' => '2026-11-17 18:30',
            'duration_minutes' => 90, 'capacity' => 10,
        ]);

        $this->assertSame(['created' => 3, 'skipped' => 1], $this->generate('2026-11-16', '2026-11-22'));
        $this->assertSame('Cours spécial', Course::where('starts_at', '2026-11-17 18:30:00')->sole()->title);
    }

    public function test_un_creneau_deja_passe_aujourd_hui_est_ignore(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 18:30')); // mercredi : le cours de 18h00 a commencé

        // Seulement mercredi 19h10 et jeudi 18h00
        $this->assertSame(['created' => 2, 'skipped' => 0], $this->generate('2026-11-18', '2026-11-19'));
    }

    public function test_le_coach_genere_depuis_le_planning(): void
    {
        $coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->actingAs($coach, 'web')
            ->post(route('coach.templates.generate'), ['from' => '2026-11-16', 'until' => '2026-11-22'])
            ->assertRedirect(route('coach.templates.index'))
            ->assertSessionHas('status', '4 cours créés.');

        $this->post(route('coach.templates.generate'), ['from' => '2026-11-16', 'until' => '2026-11-22'])
            ->assertSessionHas('status', 'Aucun cours créé. 4 existaient déjà.');
    }

    public function test_la_periode_par_defaut_prolonge_l_agenda(): void
    {
        $coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        // Agenda vide : à partir d'aujourd'hui, 4 semaines
        $this->actingAs($coach, 'web')
            ->get(route('coach.templates.index'))
            ->assertSee('value="2026-11-16"', false)
            ->assertSee('value="2026-12-13"', false);

        // Agenda rempli jusqu'au jeudi 26 : on repart du vendredi 27
        $this->generate('2026-11-16', '2026-11-29');
        $this->get(route('coach.templates.index'))
            ->assertSee('Votre agenda va jusqu\'au jeudi 26 novembre', false)
            ->assertSee('value="2026-11-27"', false);
    }

    public function test_les_dates_invalides_sont_refusees(): void
    {
        $coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);

        $this->actingAs($coach, 'web')
            ->post(route('coach.templates.generate'), ['from' => '2026-11-10', 'until' => '2026-11-09'])
            ->assertSessionHasErrors(['from', 'until']); // début passé, fin avant le début

        $this->post(route('coach.templates.generate'), ['from' => '2026-11-16', 'until' => '2027-06-01'])
            ->assertSessionHasErrors(['until' => 'Maximum 6 mois à l\'avance.']);

        $this->assertSame(0, Course::count());
    }
}
