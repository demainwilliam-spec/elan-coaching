<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Pas de WithoutModelEvents : l'événement "creating" de Booking génère le cancel_token
        $this->call(CourseSeeder::class);
    }
}
