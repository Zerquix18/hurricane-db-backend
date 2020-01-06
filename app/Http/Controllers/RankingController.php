<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cache;
use Lib\HurricaneRecord;

class RankingController extends Controller
{
    const CACHE_TIME = 60 * 60 * 24 * 7; // 1 week

    public function topByLowestPressure()
    {
        $hurricanes = Cache::remember('top_by_lowest_pressure', self::CACHE_TIME, function () {
            return HurricaneRecord::topByLowestPressure();
        });
        return $hurricanes;
    }

    public function topByHighestWindSpeed()
    {
        $hurricanes = Cache::remember('top_by_highest_windspeed', self::CACHE_TIME, function () {
            return HurricaneRecord::topByHighestWindSpeed();
        });
        return $hurricanes;
    }

    public function topByFatalities()
    {
        $hurricanes = Cache::remember('top_by_fatalities', self::CACHE_TIME, function () {
            return HurricaneRecord::topByFatalities();
        });
        return $hurricanes;
    }

    public function topByDamage()
    {
        $hurricanes = Cache::remember('top_by_damage', self::CACHE_TIME, function () {
            return HurricaneRecord::topByDamage();
        });
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
        $hurricanes = Cache::remember('top_by_season', self::CACHE_TIME, function () {
            return HurricaneRecord::topBySeason();
        });
        return $hurricanes;
    }

    public function fastestMovement()
    {
        $hurricanes = Cache::remember('top_by_fastest_movement', self::CACHE_TIME, function () {
            return HurricaneRecord::fastestMovement();
        });
        return $hurricanes;
    }

    public function topByLargestPath()
    {
        $hurricanes = Cache::remember('top_by_largest_path', self::CACHE_TIME, function () {
            return HurricaneRecord::topByLargestPath();
        });
        return $hurricanes;
    }

    public function topByLandfalls()
    {
        $hurricanes = Cache::remember('top_by_landfalls', self::CACHE_TIME, function () {
            return HurricaneRecord::topByLandfalls();
        });
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
