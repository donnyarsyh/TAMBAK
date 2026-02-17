<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            // Menambahkan kolom yang kurang
            if (!Schema::hasColumn('sensor_data', 'nilai_z')) {
                $table->float('nilai_z')->nullable()->after('salinitas');
            }
            if (!Schema::hasColumn('sensor_data', 'kondisi_air')) {
                $table->string('kondisi_air')->after('nilai_z');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->dropColumn(['nilai_z', 'kondisi_air']);
        });
    }
};
