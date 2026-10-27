<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Mail\CourseCancelled;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CoachDeleteCourseTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->coach = User::create(['name' => 'JF', 'email' => 'jf@test.be', 'password' => 'un-long-mot-de-passe']);
    }

    private function makeCourseWithBookings(): Course
    {
        $course = Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle A',
            'starts_at' => now()->addDays(3),
            'duration_minutes' => 60,
            'capacity' => 1,
        ]);
        (new BookCourse)->handle($course, 'Alice', 'alice@test.be');
        (new BookCourse)->handle($course, 'Bob', 'bob@test.be'); // en attente

        return $course;
    }

    public function test_la_page_de_confirmation_ne_supprime_rien(): void
    {
        $course = $this->makeCourseWithBookings();

        $this->actingAs($this->coach)
            ->get(route('coach.courses.delete', $course))
            ->assertOk()
            ->assertSee('2 personnes inscrites seront prévenues');

        $this->assertModelExists($course);
    }

    public function test_supprimer_un_cours_supprime_ses_reservations_et_previent_les_inscrits(): void
    {
        $course = $this->makeCourseWithBookings();

        $this->actingAs($this->coach)
            ->delete(route('coach.courses.destroy', $course), ['coach_message' => 'Je suis malade, désolé !'])
            ->assertRedirect(route('coach.dashboard'))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, '2 inscrits prévenus'));

        $this->assertModelMissing($course);
        $this->assertDatabaseCount('bookings', 0); // cascade
        Mail::assertQueued(CourseCancelled::class, fn (CourseCancelled $mail) => $mail->hasTo('bob@test.be')
            && $mail->coachMessage === 'Je suis malade, désolé !');
        Mail::assertQueuedCount(4); // 2 mails de réservation + 2 mails d'annulation
    }

    public function test_le_mail_d_annulation_s_affiche_meme_apres_la_suppression(): void
    {
        $course = $this->makeCourseWithBookings();
        $this->actingAs($this->coach)->delete(route('coach.courses.destroy', $course));

        // On récupère le mail mis en queue et on l'affiche : le cours n'existe plus, mais le mail n'en dépend pas
        $mail = Mail::queued(CourseCancelled::class)->first();

        $mail->assertSeeInHtml('Cross-training');
        $mail->assertDontSeeInHtml('Le mot du coach'); // pas de message → pas de bloc
    }

    public function test_un_visiteur_ne_peut_pas_supprimer_de_cours(): void
    {
        $course = $this->makeCourseWithBookings();

        $this->delete(route('coach.courses.destroy', $course))->assertRedirect(route('coach.login'));

        $this->assertModelExists($course);
    }
}
