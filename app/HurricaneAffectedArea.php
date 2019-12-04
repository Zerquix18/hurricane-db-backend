<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HurricaneAffectedArea extends Model
{
    protected $table = 'hurricane_affected_areas';
    protected $fillable = [
        'hurricane_id', 
        'area_name'
    ];
}
