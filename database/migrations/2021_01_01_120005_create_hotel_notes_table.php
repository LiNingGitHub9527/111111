<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotel_notes')) {
            return;
        }
        
        Schema::create('hotel_notes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hotel_id');
            $table->string('title', 40)->comment('タイトル​');
            $table->text('content')->comment('内容​');
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
        if (config('migrate.migrations.skip_hotel_notes')) {
            return;
        }
        
        Schema::dropIfExists('hotel_notes');
    }
}
