<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HurricaneWindSpeeds extends Model
{
    protected $table = 'hurricane_windspeeds';
    protected $casts = ['measurement' => 'float'];
    protected $dates = ['moment'];
}
