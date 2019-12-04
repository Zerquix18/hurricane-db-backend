<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Hurricane;
use App\HurricaneAffectedArea;
use Lib\WikipediaParser;

class CompleteFromWikipedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'complete_from_wikipedia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Completes the hurricane database with info from Wikipedia';

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
        $hurricanes = Hurricane::all();

        foreach ($hurricanes as $hurricane) {
            $this->completeHurricane($hurricane);
        }
    }

    private function completeHurricane(Hurricane $hurricane): void
    {
        $this->info("Getting data for $hurricane->name ($hurricane->season) [$hurricane->id]");
        $wikipedia_parser = new WikipediaParser($hurricane);
        $data = $wikipedia_parser->getData();
        if (! $data) {
            dump("No data found");
            return;
        }

        $hurricane->description = $data['first_paragraph'];
        $hurricane->description_source = $data['first_paragraph_source'];

        $hurricane->min_range_fatalities = $data['min_range_fatalities'];
        $hurricane->max_range_fatalities = $data['max_range_fatalities'];
        $hurricane->min_range_damage = $data['min_range_damage'];
        $hurricane->max_range_damage = $data['max_range_damage'];

        $hurricane->image_url = $data['default_image'];

        $hurricane->save();

        foreach ($data['affected_areas'] as $area_name) {
            HurricaneAffectedArea::firstOrCreate(['hurricane_id' => $hurricane->id, 'area_name' => $area_name]);
        }

        $this->info("Done with $hurricane->name");
    }
}
