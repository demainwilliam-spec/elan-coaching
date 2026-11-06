<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jetons « mot de passe oublié » des clients. Table séparée de password_reset_tokens (celle du coach) :
     * la clé est l'email, donc un coach et un client de même adresse s'écraseraient leurs jetons
     */
    public function up(): void
    {
        Schema::create('client_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); // un seul jeton valable par adresse (le dernier demandé)
            $table->string('token'); // empreinte (hash) du jeton, jamais le jeton envoyé par mail
            $table->timestamp('created_at')->nullable(); // sert à calculer l'expiration (60 min)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_password_reset_tokens');
    }
};
