<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comptes des clients : table séparée de `users` (réservée au coach), avec son propre guard "client"
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 254)->unique(); // un seul compte par adresse
            $table->timestamp('email_verified_at')->nullable(); // null tant que le lien du mail n'a pas été cliqué
            $table->string('password'); // empreinte (hash) du mot de passe, jamais le mot de passe lui-même
            $table->rememberToken(); // colonne remember_token : jeton du cookie « rester connecté »
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
