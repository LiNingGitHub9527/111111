<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_images')) {
            return;
        }
        
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id');
            $table->string('name', 100);
            $table->string('path', 255);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_images')) {
            return;
        }
        
        Schema::dropIfExists('images');
    }
}
