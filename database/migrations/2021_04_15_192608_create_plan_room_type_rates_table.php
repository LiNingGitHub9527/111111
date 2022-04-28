<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanRoomTypeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_room_type_rates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('client_id');
            $table->integer('hotel_id');
            $table->integer('room_type_id');
            $table->integer('plan_id');
            $table->date('date')->nullable();
            $table->tinyInteger('date_sale_condition')->default(0)->comment('0: 販売中, 1:停止中');
            $table->timestamps();

            $table->unique(['hotel_id', 'room_type_id', 'plan_id', 'date'], 'unique_row');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plan_room_type_rates');
    }
}
