<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // Clé étrangère vers courses.id ; si le cours est supprimé, ses réservations aussi
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('email', 254);
            $table->string('status', 20); // valeurs de l'enum BookingStatus : confirmed / waiting
            $table->string('cancel_token', 64)->unique(); // lien d'annulation envoyé par mail
            $table->timestamps();

            // Index unique composé : une même adresse ne peut réserver qu'une fois le même cours
            $table->unique(['course_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
