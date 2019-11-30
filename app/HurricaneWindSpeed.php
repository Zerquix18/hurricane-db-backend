<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HurricaneWindSpeed extends Model
{
    protected $table = 'hurricane_windspeeds';
    protected $casts = ['measurement' => 'float'];
    protected $dates = ['moment'];
    protected $fillable = [
        'hurricane_id',
        'position_id',
        'measurement',
        'moment',
    ];
}
