<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\HurricanePosition;
use App\HurricanePressure;
use App\HurricaneWindSpeed;

class Hurricane extends Model
{
    protected $table = 'hurricanes';
    protected $casts = [
        'season' => 'integer',
        'peak_intensity' => 'integer',
        'minimum_pressure' => 'integer',
        'minimum_temperature' => 'integer',
        'lowest_pressure' => 'float',
        'highest_pressure' => 'float',
        'lowest_windspeed' => 'float',
        'highest_windspeed' => 'float',
        'distance_traveled' => 'float',
        'speed' => 'float',
        'ace' => 'float',
    ];
    protected $dates = ['formed', 'dissipated'];
    protected $fillable = [
        'name',
        'basin',
        'season',
        'formed',
        'dissipated',
        'min_range_fatalities',
        'max_range_fatalities',
        'min_range_damage',
        'max_range_damage',
        'sources',
        'lowest_pressure',
        'highest_pressure',
        'lowest_windspeed',
        'highest_windspeed',
        'distance_traveled',
        'speed',
        'ace',
    ];

    public function positions()
    {
        return $this->hasMany('App\HurricanePosition');
    }
    public function pressures()
    {
        return $this->hasMany('App\HurricanePressure');
    }
    public function windSpeeds()
    {
        return $this->hasMany('App\HurricaneWindSpeed');
    }
}
