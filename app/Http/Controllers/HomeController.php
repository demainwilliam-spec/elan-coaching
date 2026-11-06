<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    // __invoke : contrôleur à action unique, appelé directement par la route
    public function __invoke(Request $request): View
    {
        // Tous les cours à venir : la vue les découpe en pages de 4 pour le carrousel
        $courses = Course::upcoming()->withBookingCounts()->get();

        // Client connecté : ses réservations parmi ces cours (affichées à la place du bouton « Réserver »).
        // pluck('status', 'course_id') : tableau [id du cours => statut] ; all() : en simple tableau PHP
        $client = $request->user('client');
        $myBookings = $client
            ? $client->bookings()->whereIn('course_id', $courses->pluck('id'))->pluck('status', 'course_id')->all()
            : [];

        // Le tableau devient les variables $courses et $myBookings dans la vue home.blade.php
        return view('home', [
            'courses' => $courses,
            'myBookings' => $myBookings,
        ]);
    }
}
