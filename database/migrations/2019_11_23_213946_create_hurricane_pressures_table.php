<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHurricanePressuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hurricane_pressures', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('hurricane_id');
            $table->integer('position_id');

            $table->float('measurement');

            $table->dateTime('moment');

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
        Schema::dropIfExists('hurricane_pressures');
    }
}
