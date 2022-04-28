<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBaseCustomerItemValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_base_customer_item_values')) {
            return;
        }
        Schema::create('base_customer_item_values', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reservation_id');
            $table->integer('base_customer_item_id');
            $table->string('name');
            $table->integer('data_type');
            $table->text('value');
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
        if (config('migrate.migrations.skip_base_customer_item_values')) {
            return;
        }
        Schema::dropIfExists('base_customer_item_values');
    }
}
