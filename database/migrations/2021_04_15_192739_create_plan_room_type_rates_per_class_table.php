<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanRoomTypeRatesPerClassTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_room_type_rates_per_class', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('plan_room_type_rate_id');
            $table->tinyInteger('class_type');
            $table->integer('class_person_num');
            $table->integer('class_amount');
            $table->timestamps();

            $table->unique(['plan_room_type_rate_id', 'class_person_num'], 'unique_row');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plan_room_type_rates_per_class');
    }
}
