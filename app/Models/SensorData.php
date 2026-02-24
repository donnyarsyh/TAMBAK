<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    protected $fillable = [
        'suhu',
        'ph',
        'salinitas',
        'nilai_z',
        'kondisi_air'
    ];
}