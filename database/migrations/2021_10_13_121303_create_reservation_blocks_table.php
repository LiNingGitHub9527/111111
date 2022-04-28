<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationBlocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservation_blocks')) {
            return;
        }
        Schema::create('reservation_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('hotel_id');
            $table->integer('room_type_id');
            $table->integer('reservation_repeat_group_id')->nullable();
            $table->integer('is_available')->default('1');
            $table->integer('reserved_num')->default('0');
            $table->integer('room_num');
            $table->integer('person_capacity');
            $table->integer('price');
            $table->date('date');
            $table->integer('start_hour');
            $table->integer('start_minute');
            $table->integer('end_hour');
            $table->integer('end_minute');
            $table->integer('is_updated')->default('1');
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
        if (config('migrate.migrations.skip_reservation_blocks')) {
            return;
        }
        Schema::dropIfExists('reservation_blocks');
    }
}
