<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationRepeatGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservation_repeat_groups')) {
            return;
        }
        Schema::create('reservation_repeat_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('hotel_id');
            $table->integer('room_type_id');
            $table->integer('start_hour');
            $table->integer('start_minute');
            $table->integer('end_hour');
            $table->integer('end_minute');
            $table->integer('repeat_interval_type');
            $table->date('repeat_start_date');
            $table->date('repeat_end_date');
            $table->softDeletes();
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
        if (config('migrate.migrations.skip_reservation_repeat_groups')) {
            return;
        }
        Schema::dropIfExists('reservation_repeat_groups');
    }
}
