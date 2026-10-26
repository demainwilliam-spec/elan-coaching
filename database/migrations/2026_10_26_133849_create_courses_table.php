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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->text('description')->nullable(); // nullable : colonne facultative (NULL autorisé)
            $table->string('location', 150);
            $table->dateTime('starts_at')->index(); // index : accélère le tri / filtre sur les cours à venir
            $table->unsignedSmallInteger('duration_minutes'); // unsigned : pas de valeur négative
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
