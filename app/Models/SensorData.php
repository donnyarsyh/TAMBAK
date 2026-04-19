<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    use HasFactory;

    protected $table = 'sensor_data';

    // Kolom-kolom yang boleh diisi secara massal
    protected $fillable = [
        'suhu',
        'ph',
        'v_ph',
        'salinitas',
        'nilai_z',
        'kondisi_air'
    ];
}