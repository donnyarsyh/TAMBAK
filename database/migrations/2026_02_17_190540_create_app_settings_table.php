<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // contoh: 'monitoring_status'
            $table->string('value');         // contoh: 'start' atau 'stop'
            $table->timestamps();
        });
        
        // Isi data awal
        DB::table('app_settings')->insert(['key' => 'monitoring_status', 'value' => 'stop']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
