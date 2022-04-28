<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationCancelPolicyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservation_cancel_policy', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('hotel_id');
            $table->integer('cancel_policy_id');
            $table->integer('reservation_id');

            $table->boolean('is_free_cancel');
            $table->tinyInteger('free_day');
            $table->tinyInteger('cancel_charge_rate');
            $table->tinyInteger('no_show_charge_rate');

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
        Schema::dropIfExists('reservation_cancel_policy');
    }
}
