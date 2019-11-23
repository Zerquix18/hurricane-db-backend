<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HurricaneTemperature extends Model
{
    protected $table = 'hurricane_temperatures';
    protected $casts = [
        'measurement' => 'float',
    ];
    protected $dates = ['moment'];
}
