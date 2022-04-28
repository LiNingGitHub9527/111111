<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservedReservationBlocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reserved_reservation_blocks')) {
            return;
        }
        Schema::create('reserved_reservation_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reservation_id');
            $table->integer('reservation_block_id');
            $table->integer('customer_id');
            $table->integer('line_user_id');
            $table->integer('person_num');
            $table->integer('price');
            $table->date('date');
            $table->integer('start_hour');
            $table->integer('start_minute');
            $table->integer('end_hour');
            $table->integer('end_minute');
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
        if (config('migrate.migrations.skip_reserved_reservation_blocks')) {
            return;
        }
        Schema::dropIfExists('reserved_reservation_blocks');
    }
}
