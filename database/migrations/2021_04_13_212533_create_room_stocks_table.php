<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('client_id');
            $table->integer('hotel_id');
            $table->integer('hotel_room_type_id');
            $table->date('date')->nullable();
            $table->tinyInteger('date_sale_condition')->default(0)->comment('0: 販売中, 1: 停止中');
            $table->integer('date_stock_num')->comment('日在庫数');
            $table->integer('date_reserve_num')->nullable()->default(0)->comment('日予約数');
            $table->timestamps();

            $table->unique(['hotel_id', 'hotel_room_type_id', 'date'], 'unique_row');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('room_stocks');
    }
}
