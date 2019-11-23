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
        Schema::create('hurricane_positions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('hurricane_id');
            $table->float('latitude', 10, 6);
            $table->float('longitude', 10, 6);
            
            $table->dateTime('moment');
            
            $table->string('direction');

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
