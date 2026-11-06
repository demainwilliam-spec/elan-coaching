<?php

namespace Tests\Feature;

use App\Actions\BookCourse;
use App\Models\Booking;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(string $startsIn = '+3 days'): Booking
    {
        $course = Course::create([
            'title' => 'Cross-training',
            'location' => 'Salle de test',
            'starts_at' => now()->modify($startsIn),
            'duration_minutes' => 60,
            'capacity' => 5,
        ]);

        return (new BookCourse)->handle($course, 'Alice', 'Test', 'alice@test.be');
    }

    public function test_le_lien_affiche_la_confirmation_sans_rien_annuler(): void
    {
        $booking = $this->makeBooking();

        $this->get(route('bookings.cancel', $booking->cancel_token))
            ->assertOk()
            ->assertSee('Cross-training')
            ->assertSee('Confirmer l\'annulation', false); // false : compare le HTML brut (l'apostrophe n'est pas échappée ici)

        $this->assertModelExists($booking); // un simple GET ne doit jamais annuler
    }

    public function test_un_jeton_inconnu_affiche_une_page_explicative(): void
    {
        $this->get('/annulation/jeton-bidon')
            ->assertNotFound()
            ->assertSee('Lien plus valide');
    }

    public function test_le_bouton_annule_et_redirige_vers_l_accueil(): void
    {
        $booking = $this->makeBooking();

        $this->post(route('bookings.destroy', $booking->cancel_token))
            ->assertRedirect('/#cours')
            ->assertSessionHas('booking_success', fn (string $message) => str_contains($message, 'est annulée'));

        $this->assertModelMissing($booking);
    }

    public function test_apres_midi_le_jour_du_cours_la_page_n_affiche_pas_le_bouton(): void
    {
        $this->travelTo(now()->setTime(13, 0));
        $booking = $this->makeBooking(startsIn: '+5 hours'); // cours à 18h, le même jour

        $this->get(route('bookings.cancel', $booking->cancel_token))
            ->assertOk()
            ->assertSee('jusqu\'à midi le jour du cours', false)
            ->assertDontSee('Confirmer l\'annulation', false);
    }

    public function test_apres_midi_le_jour_du_cours_le_post_est_refuse(): void
    {
        $this->travelTo(now()->setTime(13, 0));
        $booking = $this->makeBooking(startsIn: '+5 hours');

        // Même sans bouton, quelqu'un pourrait envoyer le POST à la main : le serveur refuse aussi
        $this->post(route('bookings.destroy', $booking->cancel_token))
            ->assertRedirect(route('bookings.cancel', $booking->cancel_token))
            ->assertSessionHas('cancel_error');

        $this->assertModelExists($booking);
    }

    public function test_un_lien_deja_utilise_affiche_la_page_explicative(): void
    {
        $booking = $this->makeBooking();
        $this->post(route('bookings.destroy', $booking->cancel_token));

        $this->get(route('bookings.cancel', $booking->cancel_token))
            ->assertNotFound()
            ->assertSee('Lien plus valide');
    }
}
