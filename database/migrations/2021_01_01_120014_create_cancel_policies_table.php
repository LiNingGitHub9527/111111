<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCancelPoliciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_cancel_policies')) {
            return;
        }
        
        Schema::create('cancel_policies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 40);
            $table->integer('hotel_id');
            $table->boolean('is_free_cancel');
            $table->tinyInteger('free_day');
            $table->tinyInteger('free_time');
            $table->tinyInteger('cancel_charge_rate');
            $table->tinyInteger('no_show_charge_rate');
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
        if (config('migrate.migrations.skip_cancel_policies')) {
            return;
        }
        
        Schema::dropIfExists('cancel_policies');
    }
}
