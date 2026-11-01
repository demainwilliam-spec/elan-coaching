<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suppression des comptes inactifs (RGPD : durée de conservation limitée) :
     * date de dernière activité (connexion ou réservation) et date de l'avertissement envoyé avant suppression
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->after('remember_token');
            $table->timestamp('inactivity_warned_at')->nullable()->after('last_active_at'); // null = pas averti
        });

        // Comptes déjà créés : on part d'aujourd'hui (aucune activité n'était enregistrée avant)
        DB::table('clients')->update(['last_active_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['last_active_at', 'inactivity_warned_at']);
        });
    }
};
