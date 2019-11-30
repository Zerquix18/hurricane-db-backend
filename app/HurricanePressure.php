<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HurricanePressure extends Model
{
    protected $table = 'hurricane_pressures';
    protected $casts = ['measurement' => 'float'];
    protected $dates = ['moment'];
    protected $fillable = [
        'hurricane_id',
        'position_id',
        'measurement',
        'moment',
    ];
}
