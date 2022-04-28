<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOriginalHotelHardItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_original_hotel_hard_items')) {
            return;
        }

        Schema::create('original_hotel_hard_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hard_category_id');
            $table->string('name', 40);
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
        if (config('migrate.migrations.skip_original_hotel_hard_items')) {
            return;
        }

        Schema::dropIfExists('original_hotel_hard_items');
    }
}
