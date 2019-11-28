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
            $table->string('image_url')->nullable();

            $table->enum('basin', [
                'atlantic',
                'eatern_pacific',
                'western_pacific',
                'southern_pacific',
                'indian',
                'australian_region',
            ]);
            $table->integer('season');

            $table->dateTime('formed');
            $table->dateTime('dissipated');

            $table->integer('min_range_casualties')->nullable();
            $table->integer('max_range_casualties')->nullable();
            $table->integer('min_range_damage')->nullable();
            $table->integer('max_range_damage')->nullable();

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
