<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelRoomTypeImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotel_room_type_images')) {
            return;
        }
        
        Schema::create('hotel_room_type_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('room_type_id');
            $table->string('image', 255);
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
        if (config('migrate.migrations.skip_hotel_room_type_images')) {
            return;
        }
        
        Schema::dropIfExists('hotel_room_type_images');
    }
}
