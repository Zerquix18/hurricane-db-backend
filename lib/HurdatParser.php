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
      $hurricane_name = $header[1];
      $data_count = (int) $header[2];

      $hurricane_data = $lines->slice($header_index + 1, $data_count)->values();
      $hurricanes[] = $this->handleHurricane($hurricane_name, $hurricane_data);
      $header_index += $data_count + 1;
    }

    return $hurricanes;
  }

  private function handleHurricane(string $name, Collection $data): array
  {
    $name = ucfirst(strtolower($name));
    $events = $data->map(function ($event) {
      $timestamp = $this->parseDateTime($event[0], $event[1]);
      $event_type = $event[2];
      $classification = $event[3];

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
    
    return [
      'name' => $name,
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
}
