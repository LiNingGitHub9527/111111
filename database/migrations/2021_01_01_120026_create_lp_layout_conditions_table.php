<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLpLayoutConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_lp_layout_conditions')) {
            return;
        }
        
        Schema::create('lp_layout_conditions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lp_layout_id')->index();
            $table->tinyInteger('start_point_type')->default(0);
            $table->integer('default_start_point_seconds')->default(0);
            $table->integer('default_start_point_scroll')->default(0);
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
        if (config('migrate.migrations.skip_lp_layout_conditions')) {
            return;
        }
        
        Schema::dropIfExists('lp_layout_conditions');
    }
}
