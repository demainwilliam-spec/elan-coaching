<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une réservation peut appartenir à un compte client (client_id rempli) ou à un visiteur sans compte (null)
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // nullable : les réservations sans compte restent possibles
            // nullOnDelete : si le compte est supprimé, ses réservations restent (comme celles d'un visiteur)
            // au lieu de disparaître ; name, email et le lien d'annulation suffisent pour les gérer
            $table->foreignId('client_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // dropConstrainedForeignId : supprime d'abord la clé étrangère, puis la colonne
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
