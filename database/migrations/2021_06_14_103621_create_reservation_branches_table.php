<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservation_branches', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('reservation_id');
            $table->integer('reservation_branch_num');
            $table->integer('plan_id');
            $table->integer('room_type_id');

            $table->tinyInteger('reservation_status')->nullable()->default(0);
            $table->dateTime('reservation_date_time')->nullable();
            $table->tinyInteger('tema_reservation_type')->default(0);
            $table->dateTime('cancel_date_time')->nullable();
            $table->dateTime('change_date_time')->nullable();

            $table->integer('accommodation_price');
            $table->integer('room_num');

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
        Schema::dropIfExists('reservation_branches');
    }
}
