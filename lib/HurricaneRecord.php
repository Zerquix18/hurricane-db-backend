<?php
/**
 * Refactor on June 15, 2020
 * Basically it's going to computer everything and then we store it in the database
 * 
 */

namespace Lib;

// previously the file to get the records
// most are now cached

use Illuminate\Support\Facades\DB;

class HurricaneRecord
{
  private function __construct()
  {
    //
  }

  /************************************* Effects / Impact *******************************/

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
