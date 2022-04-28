<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReservationPlanFormItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservation_plan_form_items')) {
            return;
        }
        
        Schema::create('reservation_plan_form_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('reservation_id');
            $table->integer('form_item_id');
            $table->json('answer');
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
        if (config('migrate.migrations.skip_reservation_plan_form_items')) {
            return;
        }
        
        Schema::dropIfExists('reservation_plan_form_items');
    }
}
