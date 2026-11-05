<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Le total est gardé sur le compte : les réservations, elles, sont supprimées 6 mois après le cours
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedInteger('courses_done')->default(0);
        });

        // Date du comptage : une réservation n'est jamais comptée deux fois
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('counted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', fn (Blueprint $table) => $table->dropColumn('courses_done'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('counted_at'));
    }
};
