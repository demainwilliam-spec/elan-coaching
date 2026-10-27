<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Enums\BookingStatus;
use App\Mail\BookingPromoted;
use App\Mail\CourseUpdated;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CoachCourseTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);
    }

    private function makeCourse(int $capacity = 5): Course
    {
        return Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle A',
            'starts_at' => now()->addDays(3)->setTime(18, 30),
            'duration_minutes' => 60,
            'capacity' => $capacity,
        ]);
    }

    // Les champs du formulaire, avec possibilité d'en remplacer certains : $this->formData(['capacity' => 2])
    private function formData(Course $course, array $overrides = []): array
    {
        return array_merge([
            'title' => $course->title,
            'description' => $course->description,
            'location' => $course->location,
            'starts_at' => $course->starts_at->format('Y-m-d\TH:i'),
            'duration_minutes' => $course->duration_minutes,
            'capacity' => $course->capacity,
        ], $overrides);
    }

    private function book(Course $course, string $name): void
    {
        (new BookCourse)->handle($course, $name, strtolower($name).'@test.be');
    }

    public function test_un_visiteur_ne_peut_pas_creer_de_cours(): void
    {
        $this->post(route('coach.courses.store'), [])->assertRedirect(route('coach.login'));
    }

    public function test_le_coach_cree_un_cours(): void
    {
        $this->actingAs($this->coach)->post(route('coach.courses.store'), [
            'title' => 'Yoga',
            'location' => 'Parc',
            'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'duration_minutes' => 45,
            'capacity' => 12,
        ])->assertRedirect(route('coach.dashboard'))->assertSessionHas('status');

        $this->assertDatabaseHas('courses', ['title' => 'Yoga', 'capacity' => 12]);
    }

    public function test_une_date_passee_est_refusee(): void
    {
        $this->actingAs($this->coach)
            ->post(route('coach.courses.store'), ['title' => 'Yoga', 'location' => 'Parc', 'starts_at' => now()->subDay()->format('Y-m-d\TH:i'), 'duration_minutes' => 45, 'capacity' => 12])
            ->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_dupliquer_preremplit_le_formulaire_une_semaine_plus_tard(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->coach)
            ->get(route('coach.courses.duplicate', $course))
            ->assertOk()
            ->assertSee('Nouveau cours')
            ->assertSee('value="'.$course->starts_at->copy()->addWeek()->format('Y-m-d\TH:i').'"', false);

        $this->assertDatabaseCount('courses', 1); // rien n'est créé tant que le formulaire n'est pas envoyé
    }

    public function test_changer_le_lieu_previent_tous_les_inscrits(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $this->book($course, 'Alice');
        $this->book($course, 'Bob'); // en attente : prévenu aussi

        $this->actingAs($this->coach)
            ->put(route('coach.courses.update', $course), $this->formData($course, ['location' => 'Salle B']))
            ->assertRedirect(route('coach.dashboard'));

        Mail::assertQueued(CourseUpdated::class, 2);
        Mail::assertQueued(CourseUpdated::class, fn ($mail) => $mail->hasTo('bob@test.be'));
    }

    public function test_changer_seulement_le_titre_ne_previent_personne(): void
    {
        $course = $this->makeCourse();
        $this->book($course, 'Alice');

        $this->actingAs($this->coach)
            ->put(route('coach.courses.update', $course), $this->formData($course, ['title' => 'Cross-training intense']));

        Mail::assertNotQueued(CourseUpdated::class);
    }

    public function test_la_capacite_ne_peut_pas_descendre_sous_les_confirmes(): void
    {
        $course = $this->makeCourse(capacity: 5);
        $this->book($course, 'Alice');
        $this->book($course, 'Bob');

        $this->actingAs($this->coach)
            ->put(route('coach.courses.update', $course), $this->formData($course, ['capacity' => 1]))
            ->assertSessionHasErrors('capacity');

        $this->assertSame(5, $course->fresh()->capacity);
    }

    public function test_augmenter_la_capacite_promeut_la_liste_d_attente(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $this->book($course, 'Alice');
        $this->book($course, 'Bob');
        $this->book($course, 'Chloe');
        $this->book($course, 'David');

        $this->actingAs($this->coach)
            ->put(route('coach.courses.update', $course), $this->formData($course, ['capacity' => 3]));

        // Bob et Chloé (les 2 premiers en attente) sont promus, David reste en attente
        $statuses = $course->bookings()->orderBy('id')->pluck('status', 'name');
        $this->assertSame(BookingStatus::Confirmed, $statuses['Chloe']);
        $this->assertSame(BookingStatus::Waiting, $statuses['David']);
        Mail::assertQueued(BookingPromoted::class, 2);
    }
}
