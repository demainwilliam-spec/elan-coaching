<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\EmailVerificationController;
// Alias : il existe aussi un LoginController côté coach
use App\Http\Controllers\Account\LoginController as AccountLoginController;
use App\Http\Controllers\Account\PasswordResetController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\RegisterController;
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

// Route::view : affiche directement une vue, sans contrôleur (pages sans données de la base)
Route::view('/mentions-legales', 'legal.notice')->name('legal.notice');
Route::view('/confidentialite', 'legal.privacy')->name('legal.privacy');

// Liste des pages pour les moteurs de recherche. Générée par Laravel (et non un fichier fixe dans public/)
// pour que les adresses complètes suivent automatiquement le domaine du site.
// header() : précise au navigateur que la réponse est du XML et non du HTML
Route::get('/sitemap.xml', fn () => response()->view('sitemap')->header('Content-Type', 'application/xml'))
    ->name('sitemap');

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

// ---------- Comptes des clients (guard "client") ----------
Route::prefix('compte')->name('account.')->group(function () {
    // guest:client : pages réservées aux visiteurs non connectés comme client
    // (un client déjà connecté est renvoyé vers son compte, cf. bootstrap/app.php)
    Route::middleware('guest:client')->group(function () {
        Route::get('/inscription', [RegisterController::class, 'show'])->name('register');
        // throttle:5,10 : limite la création de comptes à la chaîne par un robot
        Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:5,10');
        Route::get('/connexion', [AccountLoginController::class, 'show'])->name('login');
        Route::post('/connexion', [AccountLoginController::class, 'store'])->middleware('throttle:5,1');

        // Mot de passe oublié : demande du lien (visiteur non connecté)
        Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'email'])->middleware('throttle:5,10');
    });

    // Formulaire du nouveau mot de passe (lien reçu par mail) : accessible connecté ou non,
    // car le lien est aussi demandé depuis le profil par un client connecté (le jeton suffit comme preuve)
    Route::get('/reinitialisation/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reinitialisation', [PasswordResetController::class, 'update'])
        ->name('password.update')
        ->middleware('throttle:5,10');

    // Lien de confirmation reçu par mail : accessible connecté ou non (la signature du lien suffit)
    // {client} : le compte, retrouvé par son id ; {hash} : empreinte de l'adresse à confirmer
    Route::get('/verification/{client}/{hash}', [EmailVerificationController::class, 'verify'])
        ->name('verification.verify')
        ->middleware('throttle:6,1');

    // auth:client : pages réservées au client connecté (sinon redirection vers account.login)
    Route::middleware('auth:client')->group(function () {
        Route::get('/', AccountController::class)->name('show');
        // throttle:3,10 : 3 renvois max toutes les 10 minutes, pour ne pas inonder une boîte mail
        Route::post('/verification', [EmailVerificationController::class, 'send'])
            ->name('verification.send')
            ->middleware('throttle:3,10');
        Route::post('/deconnexion', [AccountLoginController::class, 'destroy'])->name('logout');

        // Profil : nom / email, lien de changement de mot de passe, suppression du compte
        // (throttle : essais de mot de passe et envois de mails limités)
        Route::get('/profil', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update')->middleware('throttle:10,10');
        Route::post('/profil/mot-de-passe', [ProfileController::class, 'sendPasswordLink'])
            ->name('profile.password')
            ->middleware('throttle:3,10');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy')->middleware('throttle:10,10');
    });

    // Page affichée après la suppression du compte (la personne n'est plus connectée)
    Route::view('/supprime', 'account.deleted')->name('deleted');
});

// ---------- Interface du coach ----------
// prefix('coach') : toutes les URL commencent par /coach ; name('coach.') : tous les noms par "coach."
Route::prefix('coach')->name('coach.')->group(function () {
    // guest:web : pages réservées aux visiteurs NON connectés en tant que coach.
    // Le guard est toujours nommé (web = coach) : sans lui, "auth" et "guest" utilisent le guard
    // par défaut, qui peut devenir "client" pendant une requête (cf. config/auth.php)
    Route::middleware('guest:web')->group(function () {
        Route::get('/connexion', [LoginController::class, 'show'])->name('login');
        // throttle:5,1 : 5 tentatives par minute et par IP, contre les essais de mots de passe à la chaîne
        Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:5,1');
    });

    // auth:web : pages réservées au coach connecté (sinon redirection vers coach.login, cf. bootstrap/app.php)
    Route::middleware('auth:web')->group(function () {
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
