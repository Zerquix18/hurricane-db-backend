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

// this file is a mess i better clean it up

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

  public static function topByMonth(int $limit = 10)
  {
    $hurricanes = DB::table('hurricanes')
                  ->selectRaw('hurricanes.*, MAX(hurricane_windspeeds.measurement) AS max_speed')
                  ->join('hurricane_windspeeds', 'hurricane_windspeeds.hurricane_id', '=', 'hurricanes.id')
                  ->groupBy('hurricanes.id')
                  ->get()
                  ->reduce(function ($accumulator, $hurricane) {
                    $month_index = date('n', strtotime($hurricane->formed)) - 1;
                    if (! $accumulator->has($month_index)) {
                      $accumulator->put($month_index, []);
                    }
                    $month = $accumulator->get($month_index);
                    $month[] = $hurricane;
                    $accumulator->put($month_index, $month);
                    return $accumulator;
                  }, collect([]))
                  ->map(function ($hurricanes) { // by month
                    usort($hurricanes, function ($a, $b) {
                      return $b->max_speed <=> $a->max_speed;
                    });
                    return $hurricanes[0];
                  })
                  ->sortKeys();

    return $hurricanes;
  }

  public static function topSortBySeason($sort = 'desc', $limit = 10)
  {
    return DB::table('hurricanes')
           ->selectRaw('season, COUNT(*) AS total')
           ->groupBy('season')
           ->orderBy('total', $sort)
           ->limit($limit)
           ->get();
  }

  /********************************** FASTEST...   ****************************/

  public static function fastestMovementAcrossLand()
  {
    $hurricanes = DB::table('hurricanes')->get();
    $hurricanes_and_speed = []; // [{ hurricane, speed }]

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
      if ($positions_count < 2) {
        continue;
      }
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


      $total_time = abs(strtotime($positions[$positions_count - 1]->moment) - strtotime($positions[0]->moment));

      $speed = $total_distance / $total_time;

      $hurricanes_and_speed[] = [
        'hurricane' => $hurricane,
        'speed' => $speed,
        'total_distance' => $total_distance,
        'total_time' => $total_time,
      ];
    }

    usort($hurricanes_and_speed, function ($a, $b) {
      return $b['speed'] <=> $a['speed'];
    });

    return array_splice($hurricanes_and_speed, 0, 10);
  }

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

  public static function formationDateByCategory($sort = 'asc')
  {
    $hurricanes = DB::table('hurricanes')->get();
    $hurricanes_and_categories = []; // { hurricane, categories: { category, at }[] }

    foreach ($hurricanes as $hurricane) {
      $wind_speeds = DB::table('hurricane_windspeeds')
                     ->where(['hurricane_windspeeds.hurricane_id' => $hurricane->id])
                     ->join('hurricane_positions', 'hurricane_positions.id', '=', 'hurricane_windspeeds.position_id')
                     ->orderBy('hurricane_windspeeds.moment', 'ASC')
                     ->get();
      $categories = [];
      
      // trop storm

      $tropical_storm = $wind_speeds->first(function ($wp) {
        return $wp->measurement > 34 && 63 > $wp->measurement && $wp->classification === 'TS';
      });

      if ($tropical_storm) {
        $categories[] = ['category' => 'tropical_storm', 'at' => $tropical_storm->moment];
      } else {
        $hurricanes_and_categories[] = [
          'hurricane' => $hurricane,
          'categories' => $categories,
        ];
        continue;
      }

      // category 1

      $category_1 = $wind_speeds->first(function ($wp) {
        return $wp->measurement > 64 && 82 > $wp->measurement && $wp->classification === 'HU';
      });

      if ($category_1) {
        $categories[] = ['category' => 'category_1', 'at' => $category_1->moment];
      } else {
        $hurricanes_and_categories[] = [
          'hurricane' => $hurricane,
          'categories' => $categories,
        ];
        continue;
      }

      // category 2

      $category_2 = $wind_speeds->first(function ($wp) {
        return $wp->measurement > 83 && 95 > $wp->measurement && $wp->classification === 'HU';
      });

      if ($category_2) {
        $categories[] = ['category' => 'category_2', 'at' => $category_2->moment];
      } else {
        $hurricanes_and_categories[] = [
          'hurricane' => $hurricane,
          'categories' => $categories,
        ];
        continue;
      }

      // category 3

      $category_3 = $wind_speeds->first(function ($wp) {
        return $wp->measurement > 96 && 112 > $wp->measurement && $wp->classification === 'HU';
      });

      if ($category_3) {
        $categories[] = ['category' => 'category_3', 'at' => $category_3->moment];
      } else {
        $hurricanes_and_categories[] = [
          'hurricane' => $hurricane,
          'categories' => $categories,
        ];
        continue;
      }

      // category 4

      $category_4 = $wind_speeds->first(function ($wp) {
        return $wp->measurement > 113 && 136 > $wp->measurement && $wp->classification === 'HU';
      });

      if ($category_4) {
        $categories[] = ['category' => 'category_4', 'at' => $category_4->moment];
      } else {
        $hurricanes_and_categories[] = [
          'hurricane' => $hurricane,
          'categories' => $categories,
        ];
        continue;
      }

      // category 5

      $category_5 = $wind_speeds->first(function ($wp) {
        return $wp->measurement > 137 && $wp->classification === 'HU';
      });

      if ($category_5) {
        $categories[] = ['category' => 'category_5', 'at' => $category_5->moment];
      } else {
        $hurricanes_and_categories[] = [
          'hurricane' => $hurricane,
          'categories' => $categories,
        ];
        continue;
      }

      $hurricanes_and_categories[] = [
        'hurricane' => $hurricane,
        'categories' => $categories,
      ];

    }

    $hurricanes_and_categories = collect($hurricanes_and_categories);

    $tropical_storms = $hurricanes_and_categories->filter(function ($storm) {
      return collect($storm['categories'])->first(function ($category) {
        return $category['category'] === 'tropical_storm';
      });
    })->toArray();

    $category_1s = $hurricanes_and_categories->filter(function ($storm) {
      return collect($storm['categories'])->first(function ($category) {
        return $category['category'] === 'category_1';
      });
    })->toArray();

    $category_2s = $hurricanes_and_categories->filter(function ($storm) {
      return collect($storm['categories'])->first(function ($category) {
        return $category['category'] === 'category_2';
      });
    })->toArray();

    $category_3s = $hurricanes_and_categories->filter(function ($storm) {
      return collect($storm['categories'])->first(function ($category) {
        return $category['category'] === 'category_3';
      });
    })->toArray();

    $category_4s = $hurricanes_and_categories->filter(function ($storm) {
      return collect($storm['categories'])->first(function ($category) {
        return $category['category'] === 'category_4';
      });
    })->toArray();

    $category_5s = $hurricanes_and_categories->filter(function ($storm) {
      return collect($storm['categories'])->first(function ($category) {
        return $category['category'] === 'category_5';
      });
    })->toArray();

    usort($tropical_storms, function ($a, $b) use ($sort) {
      $day_of_year_a = date('z', strtotime($a['categories'][0]['at'])) + 1;
      $day_of_year_b = date('z', strtotime($b['categories'][0]['at'])) + 1;

      if ($sort === 'asc') {
        return $day_of_year_a <=> $day_of_year_b;
      } else {
        return $day_of_year_b <=> $day_of_year_a;
      }
    });

    usort($category_1s, function ($a, $b) use ($sort) {
      $day_of_year_a = date('z', strtotime($a['categories'][1]['at'])) + 1;
      $day_of_year_b = date('z', strtotime($b['categories'][1]['at'])) + 1;

      if ($sort === 'asc') {
        return $day_of_year_a <=> $day_of_year_b;
      } else {
        return $day_of_year_b <=> $day_of_year_a;
      }
    });

    usort($category_2s, function ($a, $b) use ($sort) {
      $day_of_year_a = date('z', strtotime($a['categories'][2]['at'])) + 1;
      $day_of_year_b = date('z', strtotime($b['categories'][2]['at'])) + 1;

      if ($sort === 'asc') {
        return $day_of_year_a <=> $day_of_year_b;
      } else {
        return $day_of_year_b <=> $day_of_year_a;
      }
    });

    usort($category_3s, function ($a, $b) use ($sort) {
      $day_of_year_a = date('z', strtotime($a['categories'][3]['at'])) + 1;
      $day_of_year_b = date('z', strtotime($b['categories'][3]['at'])) + 1;

      if ($sort === 'asc') {
        return $day_of_year_a <=> $day_of_year_b;
      } else {
        return $day_of_year_b <=> $day_of_year_a;
      }
    });

    usort($category_4s, function ($a, $b) use ($sort) {
      $day_of_year_a = date('z', strtotime($a['categories'][4]['at'])) + 1;
      $day_of_year_b = date('z', strtotime($b['categories'][4]['at'])) + 1;

      if ($sort === 'asc') {
        return $day_of_year_a <=> $day_of_year_b;
      } else {
        return $day_of_year_b <=> $day_of_year_a;
      }
    });

    usort($category_5s, function ($a, $b) use ($sort) {
      $day_of_year_a = date('z', strtotime($a['categories'][5]['at'])) + 1;
      $day_of_year_b = date('z', strtotime($b['categories'][5]['at'])) + 1;

      if ($sort === 'asc') {
        return $day_of_year_a <=> $day_of_year_b;
      } else {
        return $day_of_year_b <=> $day_of_year_a;
      }
    });

    return [
      [
        'type' => 'tropical_storm',
        'storm' => $tropical_storms[0]['hurricane'],
        'reached_at' => $tropical_storms[0]['categories'][0]['at'],
      ],
      [
        'type' => 'category_1',
        'storm' => $category_1s[0]['hurricane'],
        'reached_at' => $category_1s[0]['categories'][1]['at'],
      ],
      [
        'type' => 'category_2',
        'storm' => $category_2s[0]['hurricane'],
        'reached_at' => $category_2s[0]['categories'][2]['at'],
      ],
      [
        'type' => 'category_3',
        'storm' => $category_3s[0]['hurricane'],
        'reached_at' => $category_3s[0]['categories'][3]['at'],
      ],
      [
        'type' => 'category_4',
        'storm' => $category_4s[0]['hurricane'],
        'reached_at' => $category_4s[0]['categories'][4]['at'],
      ],
      [
        'type' => 'category_5',
        'storm' => $category_5s[0]['hurricane'],
        'reached_at' => $category_5s[0]['categories'][5]['at'],
      ],
    ];
  }
}
