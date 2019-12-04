<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\HurricanePosition;
use App\HurricanePressure;
use App\WindSpeed;

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
    protected $fillable = [
        'name',
        'basin',
        'season',
        'formed',
        'dissipated',
        'min_range_casualties',
        'max_range_casualties',
        'min_range_damage',
        'max_range_damage',
        'sources',
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
