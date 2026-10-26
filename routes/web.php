<?php

use App\Http\Controllers\BookingController;
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