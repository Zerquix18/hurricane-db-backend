<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Hurricane;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->validate(
            $request,
            [
                'name' => 'bail|max:255',
                'season' => 'bail|numeric',
                'affected_area' => 'bail|max:255',
                'sort_by' => 'bail|in:ace,lowest_pressure,highest_windspeed,distance_traveled,ace,max_range_fatalities,max_range_damage,formed,dissipated',
                'order_direction' => 'bail|in:asc,desc',
                'min_speed' => 'bail|int|min:0',
                'max_speed' => 'bail|int|min:0',
                'min_pressure' => 'bail|int|min:0',
                'max_pressure' => 'bail|int|min:0',
                'min_deaths' => 'bail|int|min:0',
                'max_deaths' => 'bail|int|min:0',
                'min_damage' => 'bail|int|min:0',
                'max_damage' => 'bail|int|min:0',
                'min_ace' => 'bail|int|min:0',
                'max_ace' => 'bail|int|min:0',
                'min_distance_traveled' => 'bail|int|min:0',
                'max_distance_traveled' => 'bail|int|min:0',
                'min_formation_month' => 'bail|int|min:1|max:12',
                'max_formation_month' => 'bail|int|min:1|max:12',
                'sw_lat' => 'bail|numeric|min:-90|max:90',
                'sw_lng' => 'bail|numeric|min:-180|max:180',
                'ne_lat' => 'bail|numeric|min:-90|max:90',
                'ne_lng' => 'bail|numeric|min:-180|max:180',
            ]
        );
        $query = Hurricane::query();

        /// --------------

        $name = $request->input('name');
        $name = preg_replace('/[^\w]/', '', $name); // too paranoid

        if ($name) {
            $query = $query->where('name', 'like', "%{$name}%");
        }

        /// -------------

        $season = $request->input('season');

        if ($season) {
            $query = $query->where('season', '=', $season);
        }

        /// ------------

        $affected_area = $request->input('affected_area');
        $affected_area = preg_replace('/[^\w\s]/', '', $affected_area); // too paranoid

        if ($affected_area) {
            $query = $query->join('hurricane_affected_areas', function ($join) use ($affected_area) {
                $join->on('hurricane_affected_areas.hurricane_id', '=', 'hurricanes.id')
                     ->where('area_name', 'LIKE', "%{$affected_area}%");
            });
        }

        /// -----------

        $min_speed = $request->input('min_speed');
        $max_speed = $request->input('max_speed');

        if ($min_speed) {
            $query = $query->where('highest_windspeed', '>=', $min_speed);
        }
        if ($max_speed) {
            $query = $query->where('highest_windspeed', '<=', $max_speed);
        }
        
        /// ----------

        $min_pressure = $request->input('min_pressure');
        $max_pressure = $request->input('max_pressure');

        if ($min_pressure) {
            $query = $query->where('lowest_pressure', '>=', $min_pressure);
        }
        if ($max_pressure) {
            $query = $query->where('lowest_pressure', '<=', $max_pressure);
        }

        /// ----------

        $min_deaths = $request->input('min_deaths');
        $max_deaths = $request->input('max_deaths');

        if ($min_deaths) {
            $query = $query->where('max_range_fatalities', '>=', $min_deaths);
        }
        if ($max_deaths) {
            $query = $query->where('max_range_fatalities', '<=', $max_deaths);
        }

        /// ----------
        
        $min_damage = $request->input('min_damage');
        $max_damage = $request->input('max_damage');

        if ($min_damage) {
            $query = $query->where('max_range_damage', '>=', $min_damage);
        }
        if ($max_damage) {
            $query = $query->where('max_range_damage', '<=', $max_damage);
        }

        /// ----------- 

        $min_ace = $request->input('min_ace');
        $max_ace = $request->input('max_ace');

        if ($min_ace) {
            $query = $query->where('ace', '>=', $min_ace);
        }
        if ($max_ace) {
            $query = $query->where('ace', '<=', $max_ace);
        }

        /// ----------- 

        $min_distance_traveled = $request->input('min_distance_traveled');
        $max_distance_traveled = $request->input('max_distance_traveled');

        if ($min_distance_traveled) {
            $query = $query->where('distance_traveled', '>=', $min_distance_traveled);
        }
        if ($max_distance_traveled) {
            $query = $query->where('distance_traveled', '<=', $max_distance_traveled);
        }

        /// ----------- 

        $min_formation_month = $request->input('min_formation_month');
        $max_formation_month = $request->input('max_formation_month');

        if ($min_formation_month) {
            $query = $query->whereRaw('MONTH(formed) >= ?', [$min_formation_month]);
        }
        if ($max_formation_month) {
            $query = $query->whereRaw('MONTH(formed) <= ?', [$max_formation_month]);
        }

        /// ----------- 

        $sw_lat = $request->input('sw_lat');
        $sw_lng = $request->input('sw_lng');
        $ne_lat = $request->input('ne_lat');
        $ne_lng = $request->input('ne_lng');

        $bounding_box = (
            $sw_lat !== null &&
            $sw_lng !== null &&
            $ne_lat !== null &&
            $ne_lng !== null
        );

        if ($bounding_box) {
            $query = $query->whereRaw("
            id IN (
                SELECT hurricane_id FROM hurricane_positions WHERE 
                (CASE WHEN $sw_lat < $ne_lat
                        THEN hurricane_positions.latitude BETWEEN $sw_lat AND $ne_lat
                        ELSE hurricane_positions.latitude BETWEEN $ne_lat AND $sw_lat
                END)
                AND
                (CASE WHEN $sw_lng < $ne_lng
                    THEN hurricane_positions.longitude BETWEEN $sw_lng AND $ne_lng
                    ELSE hurricane_positions.longitude BETWEEN $ne_lng AND $sw_lng
                END)
            )");
        }

        /// ----------- 

        $at_least_one = (
            $name ||
            $season ||
            $affected_area ||
            $min_speed ||
            $max_speed ||
            $min_pressure ||
            $max_pressure ||
            $min_deaths ||
            $max_deaths ||
            $min_damage ||
            $max_damage ||
            $min_ace ||
            $max_ace ||
            $min_distance_traveled ||
            $max_distance_traveled ||
            $min_formation_month ||
            $max_formation_month ||
            $bounding_box
        );

        if (! $at_least_one) {
            return [];
        }

        /// ----------- 

        $sort_by = $request->input('sort_by');

        if ($sort_by) {
            $sort_direction = $request->input('sort_direction') ?? 'desc';
            $query = $query->orderBy($sort_by, $sort_direction);
        } else {
            $query = $query->orderBy('formed', 'desc');
        }

        $systems = $query->with(['positions', 'pressures', 'windSpeeds'])->limit(20)->get();

        $systems = $systems->map(function ($system) {
            $system->positions = $system->positions->map(function ($position) {
                unset($position['hurricane_id']);
                unset($position['created_at']);
                unset($position['updated_at']);
                unset($position['source']);
    
                if ($position->windSpeeds->count() > 0) {
                    $position->wind_speed = $position->windSpeeds[0]->measurement;
                } else {
                    $position->wind_speed = null;
                }
                unset($position->windSpeeds);
    
                if ($position->pressures->count() > 0) {
                    $position->pressure = $position->pressures[0]->measurement;
                } else {
                    $position->pressure = null;
                }
                unset($position->pressures);
    
                return $position;
            });

            return $system;
        });

        return $systems;
    }
}
