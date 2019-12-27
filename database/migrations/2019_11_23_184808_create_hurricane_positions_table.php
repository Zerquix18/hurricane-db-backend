<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHurricanePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // see: https://www.nhc.noaa.gov/data/hurdat/hurdat2-format-atlantic.pdf
        $event_types = ['C', 'G', 'I', 'L', 'P', 'R', 'S', 'T', 'W'];
        $classifications = ['TD', 'TS', 'HU', 'EX', 'SD', 'SS', 'LO', 'WV', 'DB'];
        Schema::create('hurricane_positions', function (Blueprint $table) use ($event_types, $classifications) {
            $table->bigIncrements('id');

            $table->integer('hurricane_id');
            $table->float('latitude', 10, 6);
            $table->float('longitude', 10, 6);
            
            $table->dateTime('moment');

            $table->enum('event_type', $event_types)->nullable();
            $table->enum('classification', $classifications);

            $table->string('source')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hurricane_positions');
    }
}
