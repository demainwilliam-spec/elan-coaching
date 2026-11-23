<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Le client peut refuser le mail de confirmation de ses réservations (case dans « Mon profil »).
    // default(true) : tous les comptes, existants et nouveaux, reçoivent ce mail tant qu'ils ne décochent rien
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('booking_emails')->default(true)->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('booking_emails');
        });
    }
};
