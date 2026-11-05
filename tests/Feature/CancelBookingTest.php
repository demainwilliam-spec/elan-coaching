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
        return (new BookCourse)->handle($course, $name, 'Test', strtolower($name).'@test.be');
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

    public function test_l_annulation_est_refusee_apres_midi_le_jour_du_cours(): void
    {
        // travelTo : fige l'horloge (now()) à ce moment-là, pour que le test ne dépende pas de l'heure où il tourne
        $this->travelTo(now()->setTime(12, 1)); // 12h01
        $course = $this->makeCourse(capacity: 5, startsIn: '+6 hours'); // cours à 18h01, le même jour
        $alice = $this->book($course, 'Alice');

        try {
            (new CancelBooking)->handle($alice);
            $this->fail('Une BookingException était attendue'); // si on arrive ici, le test échoue
        } catch (BookingException $e) {
            $this->assertStringContainsString('jusqu\'à midi le jour du cours', $e->getMessage());
        }

        $this->assertModelExists($alice); // la réservation est toujours là
    }

    public function test_l_annulation_est_acceptee_le_matin_du_cours(): void
    {
        $this->travelTo(now()->setTime(11, 59)); // 11h59 : encore dans les temps
        $alice = $this->book($this->makeCourse(capacity: 5, startsIn: '+7 hours'), 'Alice'); // cours à 18h59

        (new CancelBooking)->handle($alice);

        $this->assertModelMissing($alice);
    }

    public function test_l_heure_limite_est_midi_le_jour_du_cours_et_jamais_apres_son_debut(): void
    {
        $evening = new Course(['starts_at' => '2026-11-10 18:30']);
        $this->assertEquals('2026-11-10 12:00', $evening->cancellationDeadline()->format('Y-m-d H:i'));

        // Garde-fou : un cours avant midi (le coach n'en donne pas) se ferme à son début, pas après
        $morning = new Course(['starts_at' => '2026-11-10 10:00']);
        $this->assertEquals('2026-11-10 10:00', $morning->cancellationDeadline()->format('Y-m-d H:i'));
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
