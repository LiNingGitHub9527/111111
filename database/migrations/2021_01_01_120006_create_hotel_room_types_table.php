<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelRoomTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotel_room_types')) {
            return;
        }

        Schema::create('hotel_room_types', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hotel_id');
            $table->string('name', 120)->comment('名前');
            $table->integer('room_num')->comment('部屋数');
            $table->integer('adult_num')->comment('大人数');
            $table->integer('child_num')->comment('子供数');
            $table->integer('room_size')->comment('平米数');
            $table->integer('sort_num')->comment('検索順位');
            $table->tinyInteger('sale_condition')->default(0)->comment('0: 販売中, 1: 停止中');
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
        if (config('migrate.migrations.skip_hotel_room_types')) {
            return;
        }
        
        Schema::dropIfExists('hotel_room_types');
    }
}
