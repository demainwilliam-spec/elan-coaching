<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Remplace la colonne "name" par "first_name" et "last_name", pour pouvoir trier par nom de famille
return new class extends Migration
{
    private const TABLES = ['clients', 'bookings'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            // Nullable le temps de remplir les lignes existantes
            Schema::table($name, function (Blueprint $table) {
                $table->string('first_name', 100)->nullable()->after('id');
                $table->string('last_name', 100)->nullable()->after('first_name');
            });

            // Lignes existantes : coupées au premier espace (sans espace, tout va dans le prénom)
            DB::table($name)->orderBy('id')->each(function (object $row) use ($name) {
                $parts = explode(' ', $row->name, 2);

                DB::table($name)->where('id', $row->id)->update([
                    'first_name' => $parts[0],
                    'last_name' => $parts[1] ?? '',
                ]);
            });

            // change() : modifie une colonne existante ; désormais obligatoires
            Schema::table($name, function (Blueprint $table) {
                $table->string('first_name', 100)->nullable(false)->change();
                $table->string('last_name', 100)->nullable(false)->change();
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('name', 100)->nullable()->after('id');
            });

            DB::table($name)->orderBy('id')->each(function (object $row) use ($name) {
                DB::table($name)->where('id', $row->id)->update([
                    'name' => mb_substr(trim("{$row->first_name} {$row->last_name}"), 0, 100),
                ]);
            });

            Schema::table($name, function (Blueprint $table) {
                $table->string('name', 100)->nullable(false)->change();
                $table->dropColumn(['first_name', 'last_name']);
            });
        }
    }
};
