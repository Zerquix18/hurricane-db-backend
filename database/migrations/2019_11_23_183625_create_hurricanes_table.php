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

            $table->enum('basin', [
                'atlantic',
                'eatern_pacific',
                'western_pacific',
                'southern_pacific',
                'indian',
                'australian_region',
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

            $table->unique(['name', 'season']);
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
