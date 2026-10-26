<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\View\View;

class HomeController extends Controller
{
    // __invoke : contrôleur à action unique, appelé directement par la route
    public function __invoke(): View
    {
        // Tous les cours à venir : la vue les découpe en pages de 4 pour le carrousel
        $courses = Course::upcoming()->withBookingCounts()->get();

        // Le tableau devient la variable $courses dans la vue home.blade.php
        return view('home', ['courses' => $courses]);
    }
}
