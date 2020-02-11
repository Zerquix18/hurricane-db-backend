<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Hurricane;

use App\HurricanePosition;
use App\HurricaneWindSpeed;
use App\HurricanePressure;

class HurricaneController extends Controller
{
    public function getBasin(string $basin, Request $request)
    {
        return ['success' => true];
    }

    public function getSeason(string $basin, string $season, Request $request)
    {
        $systems = Hurricane::where([
            'basin' => $basin,
            'season' => $season
        ])->with(['positions', 'pressures', 'windSpeeds'])
          ->get();

        if ($systems->count() === 0) {
            abort(404);
        }

        // ------------------ BOUNDARIES
        $first_system_formed_on = $systems->first()->positions->first()->moment;
        $last_system_dissipated_on = $systems->last()->positions->last()->moment;

        // ------------------ STRONGEST STORM
        // TIL strongest storm is measured by min pressure, NOT wind speed!
        
        $min_pressures_by_hurricane = $systems->map(function ($system) {
            $pressures = $system->pressures->map(function ($pressure) {
                return $pressure->measurement;
            });

            return $pressures->min();
        });

        $min = $min_pressures_by_hurricane->min();
        $index = $min_pressures_by_hurricane->search($min);
        $strongest_storm = $systems[$index];

        $strongest_storm->min_pressure = $min;
        $strongest_storm->max_windspeed = $strongest_storm->windSpeeds->map(function ($windSpeed) {
            return $windSpeed->measurement;
        })->max();

        // STATISTICS
        $total_systems = $systems->count();
        $total_storms = 0;
        $total_huriccanes = 0;
        $total_major_hurricanes = 0;
        $total_damage = 0;
        $total_fatalities = 0;

        foreach ($systems as $system) {
            $max_wind_speed = $system->windSpeeds->map(function ($windSpeed) {
                return $windSpeed->measurement;
            })->max();

            if ($max_wind_speed > 96) {
                $total_major_hurricanes++;
            }
            if ($max_wind_speed > 64) {
                $total_huriccanes++;
            }
            if ($max_wind_speed > 34) {
                $total_storms++;
            }

            // TODO: reconsider??
            if ($system->max_range_damage) {
                $total_damage += $system->max_range_damage;
            }
            if ($system->max_range_fatalities) {
                $total_fatalities += $system->max_range_fatalities;
            }
        }

        // first formed system
        return [
            'boundaries' => [
                'first_system_formed_on' => $first_system_formed_on,
                'last_system_dissipated_on' => $last_system_dissipated_on,
            ],
            'strongest_storm' => $strongest_storm,
            'statistics' => [
                'total_systems' => $total_systems,
                'total_storms' => $total_storms,
                'total_huriccanes' => $total_huriccanes,
                'total_major_hurricanes' => $total_major_hurricanes,
                'total_damage' => $total_damage,
                'total_fatalities' => $total_fatalities,
            ],
            'systems' => $systems,
        ];
    }

    public function getHurricane(string $basin, string $season, string $hurricane, Request $request)
    {
        $system = Hurricane::where([
            'basin' => $basin,
            'season' => $season,
            'name' => $hurricane,
        ])->first();

        if (! $system) {
            abort(404);
        }

        return $system;
    }

    public function getPositions(string $id)
    {
        $positions = HurricanePosition::where('hurricane_id', $id)->get();

        return $positions;
    }

    public function getWindspeeds(string $id)
    {
        $wind_speeds = HurricaneWindSpeed::where('hurricane_id', $id)->get();

        return $wind_speeds;
    }

    public function getPressures(string $id)
    {
        $pressures = HurricanePressure::where('hurricane_id', $id)->get();

        return $pressures;
    }
}
