<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Actions\CancelBooking;
use App\Exceptions\BookingException;
use App\Mail\BookingPromoted;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingMailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mail::fake() : plus aucun mail n'est envoyé, Laravel se contente de noter ceux qui sont demandés
        Mail::fake();
    }

    private function makeCourse(int $capacity): Course
    {
        return Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle de test',
            'starts_at' => now()->addDays(3),
            'duration_minutes' => 60,
            'capacity' => $capacity,
        ]);
    }

    private function book(Course $course, string $name): Booking
    {
        return (new BookCourse)->handle($course, $name, strtolower($name).'@test.be');
    }

    public function test_une_reservation_met_un_mail_en_queue_pour_la_personne(): void
    {
        $this->book($this->makeCourse(capacity: 5), 'Alice');

        // assertQueued (et non assertSent) : le mail doit passer par la queue
        Mail::assertQueued(BookingReceived::class, fn (BookingReceived $mail) => $mail->hasTo('alice@test.be'));
    }

    public function test_un_doublon_refuse_n_envoie_pas_de_mail(): void
    {
        $course = $this->makeCourse(capacity: 5);
        $this->book($course, 'Alice');

        try {
            $this->book($course, 'Alice');
        } catch (BookingException) {
        }

        Mail::assertQueuedCount(1); // seulement celui de la première réservation
    }

    public function test_le_mail_de_confirmation_contient_le_lien_d_annulation(): void
    {
        $booking = $this->book($this->makeCourse(capacity: 5), 'Alice');

        $mail = new BookingReceived($booking);

        $mail->assertHasSubject('Réservation confirmée : Cross-training du '.$booking->course->starts_at->translatedFormat('j F'));
        $mail->assertSeeInHtml('Votre place est confirmée');
        $mail->assertSeeInHtml(route('bookings.cancel', $booking->cancel_token));
    }

    public function test_le_mail_de_liste_d_attente_donne_la_position(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $this->book($course, 'Alice');
        $bob = $this->book($course, 'Bob');

        $mail = new BookingReceived($bob);

        $mail->assertSeeInHtml('n°1 sur la liste d', false); // false : l'apostrophe est échappée dans le HTML
    }

    public function test_une_place_liberee_previent_la_personne_promue(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $alice = $this->book($course, 'Alice');
        $this->book($course, 'Bob');

        (new CancelBooking)->handle($alice);

        Mail::assertQueued(BookingPromoted::class, fn (BookingPromoted $mail) => $mail->hasTo('bob@test.be'));
        Mail::assertQueuedCount(3); // 2 confirmations de réservation + 1 promotion
    }

    public function test_annuler_une_place_en_attente_ne_previent_personne(): void
    {
        $course = $this->makeCourse(capacity: 1);
        $this->book($course, 'Alice');
        $bob = $this->book($course, 'Bob');

        (new CancelBooking)->handle($bob);

        Mail::assertNotQueued(BookingPromoted::class);
    }
}
