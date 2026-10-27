<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\CancellationController;
// "as" : alias, car il existe déjà un BookingController (côté public) importé plus haut
use App\Http\Controllers\Coach\BookingController as CoachBookingController;
use App\Http\Controllers\Coach\CourseController;
use App\Http\Controllers\Coach\DashboardController;
use App\Http\Controllers\Coach\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Contrôleur invokable : on passe la classe seule, Laravel appelle sa méthode __invoke()
Route::get('/', HomeController::class)->name('home');

// POST : réception des données du formulaire de contact.
// name() permet d'écrire route('contact.send') dans les vues au lieu de l'URL en dur.
// throttle:5,10 = middleware anti-spam : 5 envois max par adresse IP toutes les 10 minutes (sinon erreur 429).
Route::post('/contact', [ContactController::class, 'send'])
    ->name('contact.send')
    ->middleware('throttle:5,10');

// POST : réservation d'une place pour un cours. {course} est remplacé par l'id du cours (ex. /cours/3/reservations)
// throttle:10,10 : un peu plus large que le contact (quelqu'un peut inscrire plusieurs proches à la suite)
Route::post('/cours/{course}/reservations', [BookingController::class, 'store'])
    ->name('bookings.store')
    ->middleware('throttle:10,10');

// Annulation via le lien reçu par mail. {booking:cancel_token} : Laravel cherche la réservation
// par sa colonne cancel_token (et non par son id), donc impossible de deviner le lien d'un autre
// missing() : jeton inconnu (faux lien, ou réservation déjà annulée) → page explicative au lieu d'une 404 brute
$cancelLinkMissing = fn () => response()->view('bookings.cancel', ['booking' => null], 404);

Route::get('/annulation/{booking:cancel_token}', [CancellationController::class, 'show'])
    ->name('bookings.cancel')
    ->missing($cancelLinkMissing);

Route::post('/annulation/{booking:cancel_token}', [CancellationController::class, 'destroy'])
    ->name('bookings.destroy')
    ->middleware('throttle:10,10')
    ->missing($cancelLinkMissing);

// ---------- Interface du coach ----------
// prefix('coach') : toutes les URL commencent par /coach ; name('coach.') : tous les noms par "coach."
Route::prefix('coach')->name('coach.')->group(function () {
    // guest : pages réservées aux visiteurs NON connectés
    Route::middleware('guest')->group(function () {
        Route::get('/connexion', [LoginController::class, 'show'])->name('login');
        // throttle:5,1 : 5 tentatives par minute et par IP, contre les essais de mots de passe à la chaîne
        Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:5,1');
    });

    // auth : pages réservées au coach connecté (sinon redirection vers coach.login, cf. bootstrap/app.php)
    Route::middleware('auth')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/cours/nouveau', [CourseController::class, 'create'])->name('courses.create');
        Route::post('/cours', [CourseController::class, 'store'])->name('courses.store');
        Route::get('/cours/{course}/dupliquer', [CourseController::class, 'duplicate'])->name('courses.duplicate');
        Route::get('/cours/{course}/modifier', [CourseController::class, 'edit'])->name('courses.edit');
        // PUT : le verbe HTTP de la modification (le formulaire l'imite avec @method('PUT'))
        Route::put('/cours/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::get('/cours/{course}/supprimer', [CourseController::class, 'delete'])->name('courses.delete');
        Route::delete('/cours/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

        Route::get('/cours/{course}/inscrits', [CoachBookingController::class, 'index'])->name('bookings.index');
        Route::post('/cours/{course}/inscrits', [CoachBookingController::class, 'store'])->name('bookings.store');
        Route::delete('/inscriptions/{booking}', [CoachBookingController::class, 'destroy'])->name('bookings.destroy');

        Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');
    });
});
