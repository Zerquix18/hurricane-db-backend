<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cache;
use App\Hurricane;
use Lib\HurricaneRecord;

class RankingController extends Controller
{
    const CACHE_TIME = 60 * 60 * 24 * 7; // 1 week
    const LIMIT = 10;

    public function topByLowestPressure()
    {
        $hurricanes = Hurricane::whereNotNull('lowest_pressure')
                              ->orderBy('lowest_pressure', 'asc')
                              ->limit(10)
                              ->get();
        return $hurricanes;
    }

    public function topByHighestWindSpeed()
    {
        $hurricanes = Hurricane::whereNotNull('highest_windspeed')
                              ->orderBy('highest_windspeed', 'desc')
                              ->limit(10)
                              ->get();
        return $hurricanes;
    }

    public function topByFatalities()
    {
        $hurricanes = Hurricane::whereNotNull('max_range_fatalities')
                              ->orderBy('max_range_fatalities', 'desc')
                              ->limit(10)
                              ->get();
        return $hurricanes;
    }

    public function topByDamage()
    {
        $hurricanes = Hurricane::whereNotNull('max_range_damage')
                              ->orderBy('max_range_damage', 'desc')
                              ->limit(10)
                              ->get();
        return $hurricanes;
    }

    public function topByMonth()
    {
        $hurricanes = Cache::remember('top_by_month', self::CACHE_TIME, function () {
            return HurricaneRecord::topByMonth();
        });
        return $hurricanes;
    }

    public function topBySeason()
    {
        $hurricanes = Hurricane::selectRaw('season, COUNT(*) AS total')
                               ->groupBy('season')
                               ->orderBy('total', 'desc')
                               ->limit(10)
                               ->get();
        return $hurricanes;
    }

    public function topByLargestPath()
    {
        $hurricanes = Hurricane::whereNotNull('distance_traveled')
                              ->orderBy('distance_traveled', 'desc')
                              ->limit(10)
                              ->get();
        return $hurricanes;
    }

    public function topByLandfalls()
    {
        $hurricanes = Hurricane::join('hurricane_positions', 'hurricanes.id', '=', 'hurricane_positions.hurricane_id')
                               ->selectRaw('hurricanes.*, COUNT(hurricane_positions.hurricane_id) AS landfalls')
                               ->where('hurricane_positions.event_type', '=', 'L')
                               ->groupBy('hurricanes.id')
                               ->orderBy('landfalls', 'DESC')
                               ->limit($limit)
                               ->get();
        return $hurricanes;
    }

    public function topByACE()
    {
        $hurricanes = Hurricane::whereNotNull('ace')
                              ->orderBy('ace', 'desc')
                              ->limit(10)
                              ->get();
        return $hurricanes;
    }

    public function earlierFormationByCategory()
    {
        $hurricanes = Cache::remember('earlier_formation_by_category', self::CACHE_TIME, function () {
            return HurricaneRecord::formationDateByCategory('asc');
        });
        return $hurricanes;
    }

    public function latestFormationByCategory()
    {
        $hurricanes = Cache::remember('latest_formation_by_category', self::CACHE_TIME, function () {
            return HurricaneRecord::formationDateByCategory('desc');
        });
        return $hurricanes;
    }

}
