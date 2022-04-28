<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLpLayoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_lp_layouts')) {
            return;
        }

        Schema::create('lp_layouts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id');
            $table->integer('hotel_id');
            $table->integer('lp_id');
            $table->integer('layout_id');
            $table->integer('component_id');
            $table->text('render_html');
            $table->integer('layout_order');
            $table->string('unique_key', 30)->unique();
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
        if (config('migrate.migrations.skip_lp_layouts')) {
            return;
        }

        Schema::dropIfExists('lp_layouts');
    }
}
