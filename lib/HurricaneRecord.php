<?php
/**
 * To get the various records. These SHOULD be cached as they change very slowly.
 * Some of them will be done via simple SQL queries and some will be too much for a noob like me,
 * so they'd be a run through all hurricanes.
 * 
 * Later on these records can be customized by date/time and location.
 * 
 */

namespace Lib;

use Illuminate\Support\Facades\DB;

class HurricaneRecord
{
  private function __construct()
  {
    //
  }


  /********************* Hurricane records in measurements  ****************************/

  public static function topByLowestPressure(int $limit = 10)
  {
    return DB::table('hurricanes')
           ->join('hurricane_pressures', 'hurricanes.id', '=', 'hurricane_pressures.hurricane_id')
           ->selectRaw('hurricanes.*, MIN(hurricane_pressures.measurement) AS min_pressure')
           ->groupBy('hurricanes.id')
           ->orderBy('min_pressure', 'ASC')
           ->limit($limit)
           ->get();
  }

  public static function topByHighestWindSpeed(int $limit = 10)
  {
    return DB::table('hurricanes')
           ->join('hurricane_windspeeds', 'hurricanes.id', '=', 'hurricane_windspeeds.hurricane_id')
           ->selectRaw('hurricanes.*, MAX(hurricane_windspeeds.measurement) AS max_speed')
           ->groupBy('hurricanes.id')
           ->orderBy('max_speed', 'DESC')
           ->limit($limit)
           ->get();
  }

  /************************************* Effects / Impact *******************************/

  public static function topByFatalities(int $limit = 10)
  {
    return DB::table('hurricanes')
           ->selectRaw('*')
           ->orderBy('max_range_fatalities', 'DESC')
           ->limit($limit)
           ->get();
  }

  // (economical, obviously!)
  public static function topByDamage(int $limit = 10)
  {
    return DB::table('hurricanes')
           ->selectRaw('*')
           ->orderBy('max_range_damage', 'DESC')
           ->limit($limit)
           ->get();
  }

  /********************************** FASTEST...   ****************************/


  /********************************* LARGEST...  *****************************/

  // this one definitely takes a while!
  public static function topByLargestPath(int $limit = 10)
  {
    $hurricanes = DB::table('hurricanes')->get();
    $hurricanes_and_distance = []; // [{ id, distance }]

    // https://stackoverflow.com/a/10054282/1932946
    $haversineGreatCircleDistance = function(
      $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
      $latFrom = deg2rad($latitudeFrom);
      $lonFrom = deg2rad($longitudeFrom);
      $latTo = deg2rad($latitudeTo);
      $lonTo = deg2rad($longitudeTo);
    
      $lonDelta = $lonTo - $lonFrom;
      $a = pow(cos($latTo) * sin($lonDelta), 2) +
        pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
      $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
    
      $angle = atan2(sqrt($a), $b);
      return $angle * $earthRadius;
    };

    foreach ($hurricanes as $hurricane) {
      $positions = DB::table('hurricane_positions')->where('hurricane_id', '=', $hurricane->id)->get()->toArray();

      usort($positions, function ($a, $b) {
        return strtotime($a->moment) <=> strtotime($b->moment);
      });

      $positions_count = count($positions);
      $total_distance = 0;

      for ($i = 1; $i < $positions_count; $i++) {
        $latitude_from = $positions[$i - 1]->latitude;
        $longitude_from = $positions[$i - 1]->longitude;
        $latitude_to = $positions[$i]->latitude;
        $longitude_to = $positions[$i]->longitude;
        
        $distance = $haversineGreatCircleDistance(
          $latitude_from,
          $longitude_from,
          $latitude_to,
          $longitude_to
        );

        $total_distance += $distance;
      }

      $hurricanes_and_distance[] = [
        'hurricane' => $hurricane,
        'total_distance' => $total_distance,
      ];
    }

    usort($hurricanes_and_distance, function ($a, $b) {
      return $b['total_distance'] <=> $a['total_distance'];
    });

    return $hurricanes_and_distance;
  }

  /*********************************** MISC  *******************************************/

  public static function topByLandfalls(int $limit = 10)
  {
    return DB::table('hurricanes')
           ->join('hurricane_positions', 'hurricanes.id', '=', 'hurricane_positions.hurricane_id')
           ->selectRaw('hurricanes.*, COUNT(hurricane_positions.hurricane_id) AS landfalls')
           ->where('hurricane_positions.event_type', '=', 'L')
           ->groupBy('hurricanes.id')
           ->orderBy('landfalls', 'DESC')
           ->limit($limit)
           ->get();
  }

}