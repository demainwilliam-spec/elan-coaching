<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

// POST : réception des données du formulaire de contact.
// name() permet d'écrire route('contact.send') dans les vues au lieu de l'URL en dur.
// throttle:5,10 = middleware anti-spam : 5 envois max par adresse IP toutes les 10 minutes (sinon erreur 429).
Route::post('/contact', [ContactController::class, 'send'])
    ->name('contact.send')
    ->middleware('throttle:5,10');