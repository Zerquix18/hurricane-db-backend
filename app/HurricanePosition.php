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
    protected $fillable = [
        'hurricane_id',
        'classification',
        'latitude', 
        'longitude', 
        'moment', 
        'event_type', 
    ];

    public function pressures()
    {
        return $this->hasMany('App\HurricanePressure', 'position_id');
    }
    public function windSpeeds()
    {
        return $this->hasMany('App\HurricaneWindSpeed', 'position_id');
    }
}
