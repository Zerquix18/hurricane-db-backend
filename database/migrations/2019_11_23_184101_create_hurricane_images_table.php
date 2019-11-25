<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHurricaneImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hurricane_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->integer('hurricane_id');

            $table->string('key');
            $table->text('description');

            $table->enum('type', [
                'satellite',
                'land',
                'measurement',
                'misc'
            ]);

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
        Schema::dropIfExists('hurricane_images');
    }
}
