<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHurricanesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hurricanes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug', 50);

            $table->enum('basin', [
                'north_atlantic',
                'eatern_pacific',
                'western_pacific',
                'north_indian',
                'australian_region',
                'southern_pacific',
            ]);
            $table->integer('season');

            $table->integer('peak_intensity');
            $table->integer('minimum_pressure');
            $table->integer('minimum_temperature');

            $table->dateTime('formed');
            $table->dateTime('dissipated');

            $table->string('casualties');
            $table->string('damage');

            $table->string('sources');

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
        Schema::dropIfExists('hurricanes');
    }
}
