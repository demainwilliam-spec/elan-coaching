<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCourseTest extends TestCase
{
    // Chaque test démarre avec une base vide et neuve (SQLite en mémoire, cf. phpunit.xml)
    use RefreshDatabase;

    // Raccourci pour créer un cours de test ; $startsIn : délai avant le début (ex. '+2 days', '-1 hour')
    private function makeCourse(int $capacity, string $startsIn = '+2 days'): Course
    {
        return Course::create([
            'title' => 'Cours de test',
            'location' => 'Salle de test',
            'starts_at' => now()->modify($startsIn),
            'duration_minutes' => 60,
            'capacity' => $capacity,
        ]);
    }

    public function test_une_reservation_est_confirmee_quand_il_reste_de_la_place(): void
    {
        // Arrange
        $course = $this->makeCourse(capacity: 2); // argument nommé : plus lisible qu'un "2" seul

        // Act
        $booking = (new BookCourse)->handle($course, 'Alice', 'alice@test.be');

        // Assert
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertNull($booking->waitingPosition());
    }

    public function test_une_reservation_passe_en_attente_quand_le_cours_est_complet(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $action = new BookCourse;

        $action->handle($course, 'Alice', 'alice@test.be'); // prend la seule place
        $bob = $action->handle($course, 'Bob', 'bob@test.be');
        $chloe = $action->handle($course, 'Chloé', 'chloe@test.be');

        $this->assertSame(BookingStatus::Waiting, $bob->status);
        $this->assertSame(1, $bob->waitingPosition());
        $this->assertSame(2, $chloe->waitingPosition());
    }

    public function test_un_email_deja_inscrit_au_cours_est_refuse_meme_en_majuscules(): void
    {
        $course = $this->makeCourse(capacity: 5);
        $action = new BookCourse;
        $action->handle($course, 'Alice', 'alice@test.be');

        // expectException : le test réussit seulement si cette exception est levée ensuite
        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('déjà inscrite');

        $action->handle($course, 'Alice', '  ALICE@test.be ');
    }

    public function test_un_doublon_refuse_ne_laisse_aucune_trace_en_base(): void
    {
        $course = $this->makeCourse(capacity: 5);
        $action = new BookCourse;
        $action->handle($course, 'Alice', 'alice@test.be');

        try {
            $action->handle($course, 'Alice', 'alice@test.be');
        } catch (BookingException) {
            // attendu : on vérifie juste ce qu'il reste en base
        }

        $this->assertSame(1, $course->bookings()->count());
    }

    public function test_le_meme_email_peut_reserver_deux_cours_differents(): void
    {
        $action = new BookCourse;

        $first = $action->handle($this->makeCourse(capacity: 5), 'Alice', 'alice@test.be');
        $second = $action->handle($this->makeCourse(capacity: 5), 'Alice', 'alice@test.be');

        $this->assertSame(BookingStatus::Confirmed, $first->status);
        $this->assertSame(BookingStatus::Confirmed, $second->status);
    }

    public function test_un_cours_deja_commence_ne_peut_plus_etre_reserve(): void
    {
        $course = $this->makeCourse(capacity: 5, startsIn: '-10 minutes');

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('déjà commencé');

        (new BookCourse)->handle($course, 'Alice', 'alice@test.be');
    }

    public function test_l_email_est_enregistre_en_minuscules_sans_espaces(): void
    {
        $booking = (new BookCourse)->handle($this->makeCourse(capacity: 5), 'Alice', '  Alice@Test.BE ');

        $this->assertSame('alice@test.be', $booking->email);
    }
}
