<?php
namespace Lib;

use Illuminate\Support\Collection;
use \DOMDocument;
use \DOMXPath;
use \DateTime;
use \DateTimeZone;

class CurrentYearParser
{
  public $basin;

  public function __construct(string $basin)
  {
    if (! in_array($basin, ['atlantic'])) {
      throw new Exception('Unsupported basin');
    }
    $this->basin = $basin;
    libxml_use_internal_errors(true);
  }

  private function getHurricanes(DOMDocument $document): array
  {
    $headers = [
      'atlantic' => 'al'
    ];
    $header = $headers[$this->basin];

    $domxpath = new DOMXPath($document);
    $result = $domxpath->query("//*[contains(@headers, '$header')]");
    $td = $result[0];
    $children = $td->childNodes;

    $result = [];
    foreach ($children as $node) {
      if ($node->nodeName !== "a") {
        continue;
      }
      $href = $node->attributes->getNamedItem('href')->nodeValue;
      $name = ucfirst(strtolower(explode('.', $href)[0]));
      $url = "https://www.nhc.noaa.gov/archive/2020/$href";
      $result[] = [
        'name' => $name,
        'url' => $url,
      ];
    }

    return $result;
  }

  private function getPositionsUrl(DOMDocument $document): array
  {
    $domxpath = new DOMXPath($document);
    $node_list = $domxpath->query("//*[contains(@headers, 'col2')]");

    $result = []; // { 
    foreach ($node_list as $td) {
      foreach ($td->childNodes as $node) {
        if ($node->nodeName !== "a") {
          continue;
        }
        $href = $node->attributes->getNamedItem('href')->nodeValue;
        $url = "https://www.nhc.noaa.gov/$href";
        $result[] = $url;
      }
    }

    return $result;
  }

  private function getPositionData(DOMDocument $document): array
  {
    $elements = $document->getElementsByTagName('pre');
    $pre = $elements[0]->nodeValue;

    $lines = explode("\n", $pre);

    $time_line = '';

    foreach ($lines as $line) {
      if (! preg_match('/^([0-9]{3,4})\s(AM|PM)/', $line)) {
        continue;
      }
      $time_line = $line;
    }

    if (! $time_line) {
      throw new \Exception('Could not find line with time');
    }

    [$hour_str, $ampm, $timezone, $day_of_week, $month_str, $day_str, $year_str] = explode(" ", $time_line);

    $datetime = new DateTime();
    $datetime->setTimezone(new DateTimeZone($timezone));
    
    $year = (int) $year_str;
    $months = [
      'Jan' => 1,
      'Feb' => 2,
      'Mar' => 3,
      'Apr' => 4,
      'May' => 5,
      'Jun' => 6,
      'Jul' => 7,
      'Aug' => 8,
      'Sep' => 9,
      'Oct' => 10,
      'Nov' => 11,
      'Dec' => 12,
    ];
    $month = $months[$month_str];
    $day = (int) $day_str;

    $datetime->setDate($year, $month, $day);

    if (strlen($hour_str) === 4) {
      $minutes_str = substr($hour_str, 2);
      $hour_str = substr($hour_str, 0, 2); 
    } else {
      $minutes_str = substr($hour_str, 1);
      $hour_str = $hour_str[0];
    }

    $hour = (int) $hour_str;
    $minute = (int) $minutes_str;

    $datetime->setTime($hour, $minute);

    $timestamp = $datetime->getTimestamp();

    // event type

    $event_type = null; // for now...

    // classification

    $classification = '';

    switch (true) {
      case stripos($lines[5], 'tropical depression') !== false:
        $classification = 'TD';
      break;
      case stripos($lines[5], 'Post-Tropical Cyclone') !== false:
        $classification = 'EX';
      break;
      case stripos($lines[5], 'Tropical Storm') !== false:
        $classification = 'TS';
      break;
      case stripos($lines[5], 'Potential Tropical Cyclone') !== false:
        $classification = 'LO';
      break;
      case stripos($lines[5], 'Hurricane') !== false;
        $classification = 'HU';
      case stripos($lines[5], 'Subtropical Storm Andrea') !== false:
        $classification = 'SS';
      break;
      default:
        throw new Exception("Could not get classification from '$lines[5]'");
    }

    /// -- location

    $location_line = '';

    foreach ($lines as $line) {
      if (strpos($line, 'LOCATION...') !== false) {
        $location_line = $line;
        break;
      }
    }

    if (! $location_line) {
      throw new Exception('Could not find location line');
    }

    $location_line = str_replace('LOCATION...', '', $location_line);
    [$lat_str, $lng_str] = explode(" ", $location_line);

    $latitude = (float) trim($lat_str, 'NSWE');
    $longitude = (float) trim($lng_str, 'NSWE');

    if (strpos($lat_str, 'S') !== false) {
      $latitude = -$latitude;
    }
    if (strpos($lng_str, 'W') !== false) {
      $longitude = -$longitude;
    }

    // wind_speed

    $wind_speed_line = '';

    foreach ($lines as $line) {
      if (strpos($line, 'MAXIMUM SUSTAINED WINDS...') !== false) {
        $wind_speed_line = $line;
        break;
      }
    }

    if (! $wind_speed_line) {
      throw new Exception('Could not find wind speed line');
    }

    $wind_speed_line = str_replace('MAXIMUM SUSTAINED WINDS...', '', $wind_speed_line);
    $wind_speed_line = explode(" ", $wind_speed_line);

    $wind_speed = round((int) $wind_speed_line[0] * 0.868976242);

    // pressure

    $pressure_line = '';

    foreach ($lines as $line) {
      if (strpos($line, 'MINIMUM CENTRAL PRESSURE...') !== false) {
        $pressure_line = $line;
        break;
      }
    }

    if (! $pressure_line) {
      throw new Exception('Could not find prssre line');
    }

    $pressure_line = str_replace('MINIMUM CENTRAL PRESSURE...', '', $pressure_line);
    $pressure_line = explode(" ", $pressure_line);

    $pressure = (int) $pressure_line[0];
    
    // wind radii

    $wind_radii_34kt_ne_quadrant = null; // maybe there's another source for this?
    $wind_radii_34kt_se_quadrant = null; // maybe there's another source for this?
    $wind_radii_34kt_sw_quadrant = null; // maybe there's another source for this?
    $wind_radii_34kt_nw_quadrant = null; // maybe there's another source for this?
    $wind_radii_50kt_ne_quadrant = null; // maybe there's another source for this?
    $wind_radii_50kt_se_quadrant = null; // maybe there's another source for this?
    $wind_radii_50kt_sw_quadrant = null; // maybe there's another source for this?
    $wind_radii_50kt_nw_quadrant = null; // maybe there's another source for this?
    $wind_radii_64kt_ne_quadrant = null; // maybe there's another source for this?
    $wind_radii_64kt_se_quadrant = null; // maybe there's another source for this?
    $wind_radii_64kt_sw_quadrant = null; // maybe there's another source for this?
    $wind_radii_64kt_nw_quadrant = null; // maybe there's another source for this?

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
  }

  public function getData()
  {
    $url = 'https://www.nhc.noaa.gov/archive/2020/'; // to be changed every year supposedly.
    $hurricanes_html = file_get_contents($url);
    if (! $hurricanes_html) {
      throw new Exception('Could not query NHC');
    }

    $hurricanes_docoument = new DOMDocument();
    $hurricanes_docoument->loadHTML(mb_convert_encoding($hurricanes_html, 'HTML-ENTITIES', 'UTF-8'));

    $hurricanes = $this->getHurricanes($hurricanes_docoument);

    $result = [];

    foreach ($hurricanes as $number => $hurricane) {
      dump($hurricane['url']);

      $positions_html = file_get_contents($hurricane['url']);
      if (! $positions_html) {
        throw new Exception('Could not query NHC');
      }
  
      $positions_document = new DOMDocument();
      $positions_document->loadHTML(mb_convert_encoding($positions_html, 'HTML-ENTITIES', 'UTF-8'));

      $positions = $this->getPositionsUrl($positions_document);

      $hurricaneForResult = [];
      $hurricaneForResult['name'] = $hurricane['name'];
      $hurricaneForResult['basin'] = $this->basin;
      $hurricaneForResult['number'] = $number + 1;
      $hurricaneForResult['season'] = 2020;
      $hurricaneForResult['ended'] = false;

      $events = [];

      foreach ($positions as $position_url) {
        dump($position_url);
        $position_html = file_get_contents($position_url);
        if (! $position_html) {
          throw new Exception('Could not query NHC');
        }

        if (strpos($position_html, 'BULLETIN') === false) {
          dump('skipping...');
          continue;
        }

        if (strpos($position_html, 'This is the last public advisory') !== false) {
          $hurricaneForResult['ended'] = true;
        }
    
        $position_document = new DOMDocument();
        $position_document->loadHTML(mb_convert_encoding($position_html, 'HTML-ENTITIES', 'UTF-8'));

        $event = $this->getPositionData($position_document);
        $events[] = $event;
      }

      $events = collect($events);
      
      $lowest_pressure = $events->pluck('pressure')->min();
      $highest_pressure = $events->pluck('pressure')->max();
  
      $lowest_windspeed = $events->pluck('wind_speed')->min();
      $highest_windspeed = $events->pluck('wind_speed')->max();
  
      $distance_traveled = $this->getDistanceTraveled($events);
      $ace = $this->getAce($events);

      $hurricaneForResult['lowest_pressure'] = $lowest_pressure;
      $hurricaneForResult['highest_pressure'] = $highest_pressure;
      $hurricaneForResult['lowest_windspeed'] = $lowest_windspeed;
      $hurricaneForResult['highest_windspeed'] = $highest_windspeed;
      $hurricaneForResult['distance_traveled'] = $distance_traveled;
      $hurricaneForResult['ace'] = $ace;
      $hurricaneForResult['events'] = $events->toArray();
      $result[] = $hurricaneForResult;
    }
    
    return $result;
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
