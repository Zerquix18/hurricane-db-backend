<?php
namespace Lib;

use Illuminate\Support\Collection;

class HurdatParser
{
  private $link;

  public function __construct(string $link)
  {
    $this->link = $link;
  }

  public function getData(): array
  {
    $result = file_get_contents($this->link);
    $result = collect(explode("\n", $result));

    $lines = $result->map(function ($line) {
      $line = trim($line, ',');
      return collect(explode(',', $line))->map(function ($cell) {
        return trim($cell);
      });
    });

    $header_index = 0;
    $lines_count = $lines->count();
    $hurricanes = [];

    while (($header_index + 1) < $lines_count) {
      $header = $lines[$header_index];
      $metadata = $header[0];
      $hurricane_name = $header[1];
      $data_count = (int) $header[2];

      $hurricane_data = $lines->slice($header_index + 1, $data_count)->values();
      $hurricanes[] = $this->handleHurricane($metadata, $hurricane_name, $hurricane_data);
      $header_index += $data_count + 1;
    }

    return $hurricanes;
  }

  private function handleHurricane(string $metadata, string $name, Collection $data): array
  {
    $basins = [
      'AL' => 'atlantic',
    ];

    $basin = $basins[substr($metadata, 0, 2)];
    $number = (int) substr($metadata, 2, 2);
    $season = (int) substr($metadata, 4);

    $name = ucfirst(strtolower($name));
    $events = $data->map(function ($event) {
      $timestamp = $this->parseDateTime($event[0], $event[1]);
      $event_type = $event[2];
      $classification = $event[3];
      if ($classification === 'ET') {
        $classification = 'EX'; // harvey 1993 has a typo?
      }

      $latitude = $this->translateCoordinate($event[4]);
      $longitude = $this->translateCoordinate($event[5]);

      $wind_speed = (float) $event[6];
      $pressure = (float) $event[7];

      $wind_radii_34kt_ne_quadrant = (int) $event[8];
      $wind_radii_34kt_se_quadrant = (int) $event[9];
      $wind_radii_34kt_sw_quadrant = (int) $event[10];
      $wind_radii_34kt_nw_quadrant = (int) $event[11];

      $wind_radii_50kt_ne_quadrant = (int) $event[12];
      $wind_radii_50kt_se_quadrant = (int) $event[13];
      $wind_radii_50kt_sw_quadrant = (int) $event[14];
      $wind_radii_50kt_nw_quadrant = (int) $event[15];

      $wind_radii_64kt_ne_quadrant = (int) $event[16];
      $wind_radii_64kt_se_quadrant = (int) $event[17];
      $wind_radii_64kt_sw_quadrant = (int) $event[18];
      $wind_radii_64kt_nw_quadrant = (int) $event[19];

      $pressure = $pressure > 0 ? $pressure : null;
      $wind_radii_34kt_ne_quadrant = $wind_radii_34kt_ne_quadrant > 0 ? $wind_radii_34kt_ne_quadrant : null;
      $wind_radii_34kt_se_quadrant = $wind_radii_34kt_se_quadrant > 0 ? $wind_radii_34kt_se_quadrant : null;
      $wind_radii_34kt_sw_quadrant = $wind_radii_34kt_sw_quadrant > 0 ? $wind_radii_34kt_sw_quadrant : null;
      $wind_radii_34kt_nw_quadrant = $wind_radii_34kt_nw_quadrant > 0 ? $wind_radii_34kt_nw_quadrant : null;
      $wind_radii_50kt_ne_quadrant = $wind_radii_50kt_ne_quadrant > 0 ? $wind_radii_50kt_ne_quadrant : null;
      $wind_radii_50kt_se_quadrant = $wind_radii_50kt_se_quadrant > 0 ? $wind_radii_50kt_se_quadrant : null;
      $wind_radii_50kt_sw_quadrant = $wind_radii_50kt_sw_quadrant > 0 ? $wind_radii_50kt_sw_quadrant : null;
      $wind_radii_50kt_nw_quadrant = $wind_radii_50kt_nw_quadrant > 0 ? $wind_radii_50kt_nw_quadrant : null;
      $wind_radii_64kt_ne_quadrant = $wind_radii_64kt_ne_quadrant > 0 ? $wind_radii_64kt_ne_quadrant : null;
      $wind_radii_64kt_se_quadrant = $wind_radii_64kt_se_quadrant > 0 ? $wind_radii_64kt_se_quadrant : null;
      $wind_radii_64kt_sw_quadrant = $wind_radii_64kt_sw_quadrant > 0 ? $wind_radii_64kt_sw_quadrant : null;
      $wind_radii_64kt_nw_quadrant = $wind_radii_64kt_nw_quadrant > 0 ? $wind_radii_64kt_nw_quadrant : null;

      return [
        'timestamp' => $timestamp,
        'event_type' => $event_type,
        'classification' => $classification,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'wind_speed' => $wind_speed,
        'pressure' => $pressure,
        'wind_radii_34kt_ne_quadrant' => $wind_radii_34kt_ne_quadrant,
        'wind_radii_34kt_se_quadrant' => $wind_radii_34kt_se_quadrant,
        'wind_radii_34kt_sw_quadrant' => $wind_radii_34kt_sw_quadrant,
        'wind_radii_34kt_nw_quadrant' => $wind_radii_34kt_nw_quadrant,
        'wind_radii_50kt_ne_quadrant' => $wind_radii_50kt_ne_quadrant,
        'wind_radii_50kt_se_quadrant' => $wind_radii_50kt_se_quadrant,
        'wind_radii_50kt_sw_quadrant' => $wind_radii_50kt_sw_quadrant,
        'wind_radii_50kt_nw_quadrant' => $wind_radii_50kt_nw_quadrant,
        'wind_radii_64kt_ne_quadrant' => $wind_radii_64kt_ne_quadrant,
        'wind_radii_64kt_se_quadrant' => $wind_radii_64kt_se_quadrant,
        'wind_radii_64kt_sw_quadrant' => $wind_radii_64kt_sw_quadrant,
        'wind_radii_64kt_nw_quadrant' => $wind_radii_64kt_nw_quadrant,
      ];
    });

    $lowest_pressure = $events->pluck('pressure')->min();
    $highest_pressure = $events->pluck('pressure')->max();

    $lowest_windspeed = $events->pluck('wind_speed')->min();
    $highest_windspeed = $events->pluck('wind_speed')->max();

    $distance_traveled = $this->getDistanceTraveled($events);
    $ace = $this->getAce($events);

    return [
      'name' => $name,
      'basin' => $basin,
      'number' => $number,
      'season' => $season,
      'lowest_pressure' => $lowest_pressure,
      'highest_pressure' => $highest_pressure,
      'lowest_windspeed' => $lowest_windspeed,
      'highest_windspeed' => $highest_windspeed,
      'distance_traveled' => $distance_traveled,
      'ace' => $ace,
      'events' => $events->toArray(),
    ];
  }

  private function parseDateTime($date, $time): int
  {
    $year = (int) substr($date, 0, 4);
    $month = (int) substr($date, 4, 2);
    $day = (int) substr($date, 6);

    $hour = (int) substr($time, 0, 2);
    $minute = (int) substr($time, 2);

    $datetime = new \DateTime();

    $datetime->setDate($year, $month, $day);
    $datetime->setTime($hour, $minute);

    $timestamp = $datetime->getTimestamp();
    return $timestamp;
  }

  private function translateCoordinate(string $coordinate): float
  {
    $value = (float) trim($coordinate, 'NW');
    if (strpos($coordinate, 'W') !== false || strpos($coordinate, 'S') !== false) {
      $value = -$value;
    }

    return $value;
  }

  private function getDistanceTraveled(Collection $positions): float
  {
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

    $positions = $positions->toArray();

    $positions_count = count($positions);
    if ($positions_count < 2) {
      return 0;
    }

    $speeds = [];
    $total_distance = 0;

    for ($i = 1; $i < $positions_count; $i++) {
      $latitude_from = $positions[$i - 1]['latitude'];
      $longitude_from = $positions[$i - 1]['longitude'];
      $latitude_to = $positions[$i]['latitude'];
      $longitude_to = $positions[$i]['longitude'];
      
      $distance = $haversineGreatCircleDistance(
        $latitude_from,
        $longitude_from,
        $latitude_to,
        $longitude_to
      );

      $total_distance += $distance;
    }

    return $total_distance;
  }

  private function getAce(Collection $positions): float
  {
    $ace = $positions->filter(function ($position) {
      if (! $position['wind_speed']) {
        return false;
      }

      $valid_hours = [0, 6, 12, 18];
      $hour = (int) date('G', $position['timestamp']);
      return in_array($hour, $valid_hours);
    })->reduce(function ($ace, $current) {
      $wind_speed = $current['wind_speed'] ** 2;
      return $ace + $wind_speed;
    }, 0) / 10e3;

    return $ace;
  }
}
