<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeDissipatedNullable extends Migration
{
    // see: https://stackoverflow.com/a/42107554/1932946
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hurricanes', function (Blueprint $table) {
            $table->dateTime('dissipated')->nullable()->change();
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
            $table->dateTime('dissipated')->change();
        });
    }
}
