<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_form_items')) {
            return;
        }
        
        Schema::create('form_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id');
            $table->integer('hotel_id');
            $table->string('name', 40);
            $table->boolean('required');
            $table->tinyInteger('item_type');
            $table->string('option_default', 40);
            $table->json('options');
            $table->integer('sort_order');
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
        if (config('migrate.migrations.skip_form_items')) {
            return;
        }
        
        Schema::dropIfExists('form_items');
    }
}
