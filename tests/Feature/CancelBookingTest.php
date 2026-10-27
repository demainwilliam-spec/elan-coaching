<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Actions\CancelBooking;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelBookingTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(int $capacity, string $startsIn = '+3 days'): Course
    {
        return Course::create([
            'title' => 'Cours de test',
            'location' => 'Salle de test',
            'starts_at' => now()->modify($startsIn),
            'duration_minutes' => 60,
            'capacity' => $capacity,
        ]);
    }

    // On passe par BookCourse pour créer les réservations : mêmes règles qu'en vrai
    private function book(Course $course, string $name): Booking
    {
        return (new BookCourse)->handle($course, $name, strtolower($name).'@test.be');
    }

    public function test_annuler_une_place_confirmee_promeut_le_premier_en_attente(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $alice = $this->book($course, 'Alice');
        $bob = $this->book($course, 'Bob');
        $chloe = $this->book($course, 'Chloe');

        $promoted = (new CancelBooking)->handle($alice);

        $this->assertTrue($promoted->is($bob)); // is() : même ligne en base
        $this->assertModelMissing($alice);
        $this->assertSame(BookingStatus::Confirmed, $bob->fresh()->status); // fresh() : relit la ligne en base
        $this->assertSame(1, $chloe->fresh()->waitingPosition());
    }

    public function test_annuler_une_place_en_attente_fait_avancer_la_file_sans_promotion(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $alice = $this->book($course, 'Alice');
        $bob = $this->book($course, 'Bob');
        $chloe = $this->book($course, 'Chloe');

        $promoted = (new CancelBooking)->handle($bob);

        $this->assertNull($promoted);
        $this->assertSame(BookingStatus::Confirmed, $alice->fresh()->status);
        $this->assertSame(1, $chloe->fresh()->waitingPosition());
    }

    public function test_annuler_sans_liste_d_attente_libere_simplement_la_place(): void
    {
        $course = $this->makeCourse(capacity: 5);
        $alice = $this->book($course, 'Alice');

        $this->assertNull((new CancelBooking)->handle($alice));
        $this->assertSame(0, $course->bookings()->count());
    }

    public function test_l_annulation_est_refusee_a_moins_de_24_h_du_cours(): void
    {
        $course = $this->makeCourse(capacity: 5, startsIn: '+23 hours');
        $alice = $this->book($course, 'Alice');

        try {
            (new CancelBooking)->handle($alice);
            $this->fail('Une BookingException était attendue'); // si on arrive ici, le test échoue
        } catch (BookingException $e) {
            $this->assertStringContainsString('moins de 24 h', $e->getMessage());
        }

        $this->assertModelExists($alice); // la réservation est toujours là
    }

    public function test_l_annulation_est_acceptee_a_plus_de_24_h_du_cours(): void
    {
        $alice = $this->book($this->makeCourse(capacity: 5, startsIn: '+25 hours'), 'Alice');

        (new CancelBooking)->handle($alice);

        $this->assertModelMissing($alice);
    }

    public function test_une_deuxieme_annulation_est_refusee_sans_promouvoir_personne_de_plus(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $alice = $this->book($course, 'Alice');
        $bob = $this->book($course, 'Bob');
        $chloe = $this->book($course, 'Chloe');
        $action = new CancelBooking;

        $action->handle($alice); // Bob est promu

        // $alice contient encore l'ancien statut "confirmed" : c'est exactement le cas du double clic
        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('déjà été annulée');

        try {
            $action->handle($alice);
        } finally {
            // finally : exécuté même si l'exception est levée → Chloé n'a pas été promue en trop
            $this->assertSame(BookingStatus::Waiting, $chloe->fresh()->status);
        }
    }
}
