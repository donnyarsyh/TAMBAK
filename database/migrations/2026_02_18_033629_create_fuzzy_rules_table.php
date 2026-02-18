<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('fuzzy_rules', function (Blueprint $table) {
            $table->id();
            $table->string('suhu');      // Dingin, Normal, Panas
            $table->string('ph');        // Asam, Netral, Basa
            $table->string('salinitas');  // Rendah, Normal, Tinggi
            $table->string('output');    // Baik, Normal, Buruk
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuzzy_rules');
    }
};
