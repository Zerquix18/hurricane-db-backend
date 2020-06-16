<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRankingFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hurricanes', function ($table) {
            $table->float('lowest_pressure')->nullable();
            $table->float('highest_pressure')->nullable();
            $table->float('lowest_windspeed')->nullable();
            $table->float('highest_windspeed')->nullable();
            $table->double('distance_traveled')->nullable();
            $table->float('ace')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hurricanes', function ($table) {
            $table->dropColumn('lowest_pressure');
            $table->dropColumn('highest_pressure');
            $table->dropColumn('lowest_windspeed');
            $table->dropColumn('highest_windspeed');
            $table->dropColumn('distance_traveled');
            $table->dropColumn('ace');
        });
    }
}
