<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReservationPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservation_plans')) {
            return;
        }
        
        Schema::create('reservation_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('reservation_id');
            $table->integer('reservation_branch_id');
            $table->integer('room_number')->default(0);
            // $table->integer('plan_id');
            // $table->integer('room_type_id');
            $table->integer('adult_num');
            $table->integer('child_num');
            $table->integer('amount')->default(0);
            $table->date('date')->nullable()->comment('宿泊日');
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
        if (config('migrate.migrations.skip_reservation_plans')) {
            return;
        }
        
        Schema::dropIfExists('reservation_plans');
    }
}
