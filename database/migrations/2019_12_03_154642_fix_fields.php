<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// its ok cuz its not in prod yet

class FixFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hurricanes', function (Blueprint $table) {
            $table->string('description_source')->nullable();
        });

        Schema::table('hurricanes', function (Blueprint $table) {
            $table->dropColumn('min_range_casualties');
            $table->dropColumn('max_range_casualties');
            $table->dropColumn('min_range_damage');
            $table->dropColumn('max_range_damage');
            $table->dropColumn('description');
        });

        Schema::table('hurricanes', function (Blueprint $table) {
            $table->bigInteger('min_range_fatalities')->nullable();
            $table->bigInteger('max_range_fatalities')->nullable();
            $table->bigInteger('min_range_damage')->nullable();
            $table->bigInteger('max_range_damage')->nullable();
            $table->longText('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hurricanes', function (Blueprint $table) {
            $table->dropColumn('description_source');

        });
        Schema::table('hurricanes', function (Blueprint $table) {

            $table->dropColumn('min_range_fatalities');
            $table->dropColumn('max_range_fatalities');
            $table->dropColumn('min_range_damage');
            $table->dropColumn('max_range_damage');
            $table->dropColumn('description');
        });
        Schema::table('hurricanes', function (Blueprint $table) {
            $table->integer('min_range_casualties')->nullable();
            $table->integer('max_range_casualties')->nullable();
            $table->integer('min_range_damage')->nullable();
            $table->integer('max_range_damage')->nullable();
            $table->string('description', 500)->nullable();
        });
    }
}
