<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuzzyRule extends Model
{
    // Tambahkan baris ini
    protected $fillable = [
        'kode_rule',
        'suhu',
        'ph',
        'salinitas',
        'output'
    ];
}
