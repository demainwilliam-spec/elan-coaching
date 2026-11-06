<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // Même requête que l'agenda public (HomeController) : cours à venir + nombre de confirmés / en attente
    public function __invoke(): View
    {
        return view('coach.dashboard', [
            'courses' => Course::upcoming()->withBookingCounts()->get(),
        ]);
    }
}
