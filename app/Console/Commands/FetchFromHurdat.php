<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

use Lib\HurdatParser;
use App\Hurricane;
use App\HurricanePosition;
use App\HurricaneWindSpeed;
use App\HurricanePressure;

class FetchFromHurdat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch_from_hurdat {database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch from the hurricane database';

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
        $link = $this->argument('database');
        $analysis = new HurdatParser($link);
        $this->info('Fetching...');
        $data = $analysis->getData();

        foreach ($data as $hurricane) {
            $this->handleHurricane($hurricane);
        }
    }

    private function handleHurricane(array $hurricane_data): void
    {
        $name = $hurricane_data['name'];
        if ($name === 'Unnamed') {
            $name = $this->translateName($hurricane_data['number']);
        }
        $this->info("Inserting {$name}...");

        $basin = $hurricane_data['basin'];
        $season = $hurricane_data['season'];
        
        $formed = new \DateTime();
        $dissipated = new \DateTime();

        $formed->setTimestamp($hurricane_data['events'][0]['timestamp']);
        $dissipated->setTimestamp($hurricane_data['events'][count($hurricane_data['events']) - 1]['timestamp']);
        
        $min_range_casualties = null;
        $max_range_casualties = null;
        $min_range_damage = null;
        $max_range_damage = null;

        $sources = $this->argument('database');

        $hurricane = Hurricane::firstOrCreate(
            ['name' => $name, 'basin' => $basin, 'season' => $season],
            [
                'name' => $name,
                'basin' => $basin,
                'season' => $season,
                'formed' => $formed,
                'dissipated' => $dissipated,
                'min_range_casualties' => $min_range_casualties,
                'max_range_casualties' => $max_range_casualties,
                'min_range_damage' => $min_range_damage,
                'max_range_damage' => $max_range_damage,
                'sources' => $sources,
            ]
        );

        foreach ($hurricane_data['events'] as $event) {
            $moment = new \DateTime();
            $moment->setTimestamp($event['timestamp']);

            $position = HurricanePosition::firstOrCreate(
                ['hurricane_id' => $hurricane->id, 'moment' => $moment],
                [
                    'hurricane_id' => $hurricane->id,
                    'latitude' => $event['latitude'],
                    'longitude' => $event['longitude'],
                    'moment' => $moment,
                    'event_type' => $event['event_type'] ? $event['event_type'] : null,
                ]
            );

            if ($event['wind_speed']) {
                $windspeed = HurricaneWindSpeed::firstOrCreate(
                    ['hurricane_id' => $hurricane->id, 'moment' => $moment],
                    [
                    'hurricane_id' => $hurricane->id,
                    'position_id' => $position->id,
                    'measurement' => $event['wind_speed'],
                    'moment' => $moment,
                    ]
                );
            }

            if ($event['pressure']) {
                $pressure = HurricanePressure::firstOrCreate(
                    ['hurricane_id' => $hurricane->id, 'moment' => $moment],
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

    private function translateName(int $number): string
    {
        $numbers = [
            'One',
            'Two',
            'Three',
            'Four',
            'Five',
            'Six',
            'Seven',
            'Eight',
            'Nine',
            'Ten',
            'Eleven',
            'Twelve',
            'Thirtheen',
            'Fourteen',
            'Fifteen',
            'Seventeen',
            'Eighteen',
            'Nineteen',
            'Twenty',
            'Twenty One',
        ];

        $number--;
        if (! key_exists($number, $numbers)) {
            return 'Unnamed'; // :(
        }
        return $numbers[$number];
    }   
}