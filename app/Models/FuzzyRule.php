<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuzzyRule extends Model
{
    protected $fillable = [
        'kode_rule',
        'suhu',
        'ph',
        'salinitas',
        'output'
    ];
}
