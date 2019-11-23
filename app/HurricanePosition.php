<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HurricanePosition extends Model
{
    protected $table = 'hurricane_positions';
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];
    protected $dates = ['moment'];
}
