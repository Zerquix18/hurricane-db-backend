<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Lib\CurrentYearParser;
use App\Hurricane;
use App\HurricanePosition;
use App\HurricaneWindSpeed;
use App\HurricanePressure;

class GetFromCurrentYear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get_from_current_year';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the hurricanes from the current year, which are not in the HURDAT yet.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $current_year = new CurrentYearParser('atlantic');

        $this->info('Fetching...');
        $data = $current_year->getData();

        $this->output->progressStart(count($data));

        foreach ($data as $hurricane) {
            $this->handleHurricane($hurricane);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
    }

    private function handleHurricane(array $hurricane_data)
    {
        $name = $hurricane_data['name'];
        $basin = $hurricane_data['basin'];
        $season = $hurricane_data['season'];

        $this->info("Inserting {$name} ({$season})...");

        $formed = new \DateTime();
        $formed->setTimestamp($hurricane_data['events'][0]['timestamp']);

        if ($hurricane_data['ended']) {
            $dissipated = new \DateTime();
            $dissipated->setTimestamp($hurricane_data['events'][count($hurricane_data['events']) - 1]['timestamp']);
        } else {
            $dissipated = null;
        }
        
        $min_range_fatalities = null;
        $max_range_fatalities = null;
        $min_range_damage = null;
        $max_range_damage = null;

        $sources = 'http://nhc.noaa.gov/archive/';

        $lowest_pressure = $hurricane_data['lowest_pressure'];
        $highest_pressure = $hurricane_data['highest_pressure'];
        $lowest_windspeed = $hurricane_data['lowest_windspeed'];
        $highest_windspeed = $hurricane_data['highest_windspeed'];
        $distance_traveled = $hurricane_data['distance_traveled'];
        $ace = $hurricane_data['ace'];

        $hurricane = Hurricane::updateOrCreate(
            [
                'name' => $name,
                'basin' => $basin,
                'season' => $season
            ],
            [
                'name' => $name,
                'basin' => $basin,
                'season' => $season,
                'formed' => $formed,
                'dissipated' => $dissipated,
                'min_range_fatalities' => $min_range_fatalities,
                'max_range_fatalities' => $max_range_fatalities,
                'min_range_damage' => $min_range_damage,
                'max_range_damage' => $max_range_damage,
                'sources' => $sources,

                'lowest_pressure' => $lowest_pressure,
                'highest_pressure' => $highest_pressure,
                'lowest_windspeed' => $lowest_windspeed,
                'highest_windspeed' => $highest_windspeed,
                'distance_traveled' => $distance_traveled,
                'ace' => $ace,
            ]
        );

        HurricanePosition::where(['hurricane_id' => $hurricane->id])->delete();
        HurricanePressure::where(['hurricane_id' => $hurricane->id])->delete();
        HurricaneWindSpeed::where(['hurricane_id' => $hurricane->id])->delete();

        foreach ($hurricane_data['events'] as $event) {
            $moment = new \DateTime();
            $moment->setTimestamp($event['timestamp']);

            $position = HurricanePosition::create(
                [
                    'hurricane_id' => $hurricane->id,
                    'latitude' => $event['latitude'],
                    'longitude' => $event['longitude'],
                    'moment' => $moment,
                    'event_type' => $event['event_type'] ? $event['event_type'] : null,
                    'classification' => $event['classification'],
                ]
            );

            if ($event['wind_speed']) {
                $windspeed = HurricaneWindSpeed::create(
                    [
                    'hurricane_id' => $hurricane->id,
                    'position_id' => $position->id,
                    'measurement' => $event['wind_speed'],
                    'moment' => $moment,
                    ]
                );
            }

            if ($event['pressure']) {
                $pressure = HurricanePressure::create(
                    [
                        'hurricane_id' => $hurricane->id,
                        'position_id' => $position->id,
                        'measurement' => $event['pressure'],
                        'moment' => $moment,
                    ]
                );
            }
        }
    }
}
