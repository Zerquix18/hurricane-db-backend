<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHurricaneAffectedAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hurricane_affected_areas', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('hurricane_id');
            $table->string('area_name');

            $table->unique(['hurricane_id', 'area_name']);
            
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
        Schema::dropIfExists('hurricane_affected_areas');
    }
}
