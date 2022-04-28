<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_rate_plans')) {
            return;
        }
        
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->integer('fee');
            $table->boolean('is_effective')->default(false);
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
        if (config('migrate.migrations.skip_rate_plans')) {
            return;
        }
        
        Schema::dropIfExists('rate_plans');
    }
}
