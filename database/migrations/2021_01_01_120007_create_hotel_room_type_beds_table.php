<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelRoomTypeBedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotel_room_type_beds')) {
            return;
        }
        
        Schema::create('hotel_room_type_beds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('room_type_id');
            $table->tinyInteger('bed_size')->comment('ベッドサイズ');
            $table->integer('bed_num')->comment('台数');
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
        if (config('migrate.migrations.skip_hotel_room_type_beds')) {
            return;
        }
        
        Schema::dropIfExists('hotel_room_type_beds');
    }
}
