<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/methode', function () {
    return view('methode');
});

Route::get('/services', function () {
    return view('services');
});

Route::get('/coach', function () {
    return view('coach');
});

Route::get('/avis', function () {
    return view('avis');
});

Route::get('/contact', function () {
    return view('contact');
});