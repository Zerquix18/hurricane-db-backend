<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Hurricane extends Model
{
    protected $table = 'hurricanes';
    protected $casts = [
        'season' => 'integer',
        'peak_intensity' => 'integer',
        'minimum_pressure' => 'integer',
        'minimum_temperature' => 'integer',
    ];
    protected $dates = ['formed', 'dissipated'];
}
