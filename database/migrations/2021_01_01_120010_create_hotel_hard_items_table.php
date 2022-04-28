<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelHardItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotel_hard_items')) {
            return;
        }
        
        Schema::create('hotel_hard_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hotel_id');
            $table->integer('hard_category_id');
            $table->string('name', 40);
            $table->boolean('is_all_room')->comment('全部屋タイプ');
            $table->json('room_type_ids')->comment('特定の部屋タイプ');
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
        if (config('migrate.migrations.skip_hotel_hard_items')) {
            return;
        }
        
        Schema::dropIfExists('hotel_hard_items');
    }
}
