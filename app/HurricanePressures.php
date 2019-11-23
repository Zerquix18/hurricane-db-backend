<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HurricanePressures extends Model
{
    protected $table = 'hurricane_pressures';
    protected $casts = ['measurement' => 'float'];
    protected $dates = ['moment'];
}
