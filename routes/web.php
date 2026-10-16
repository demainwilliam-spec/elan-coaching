<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

// POST : réception des données du formulaire de contact.
// name() permet d'écrire route('contact.send') dans les vues au lieu de l'URL en dur.
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');