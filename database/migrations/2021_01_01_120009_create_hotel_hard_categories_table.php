<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelHardCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotel_hard_categories')) {
            return;
        }
        
        Schema::create('hotel_hard_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hotel_id');
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
        if (config('migrate.migrations.skip_hotel_hard_categories')) {
            return;
        }
        
        Schema::dropIfExists('hotel_hard_categories');
    }
}
