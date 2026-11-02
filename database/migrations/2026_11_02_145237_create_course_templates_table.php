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
        // Planning type : créneaux habituels du coach, qui servent de modèle pour générer les vrais cours
        Schema::create('course_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday'); // jour de la semaine, norme ISO : 1 = lundi … 7 = dimanche
            $table->time('start_time'); // une heure sans date (ex. 18:30:00)
            $table->string('title', 100); // mêmes tailles que la table courses
            $table->text('description')->nullable();
            $table->string('location', 150);
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_templates');
    }
};
