<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHurricaneWindspeedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hurricane_windspeeds', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('hurricane_id');
            $table->integer('position_id');

            $table->float('measurement');

            $table->dateTime('moment');

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
        Schema::dropIfExists('hurricane_windspeeds');
    }
}
