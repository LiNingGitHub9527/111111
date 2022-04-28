<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelKidsPoliciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotel_kids_policies')) {
            return;
        }
        
        Schema::create('hotel_kids_policies', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hotel_id');
            $table->integer('age_start');
            $table->integer('age_end');
            $table->boolean('is_forbidden')->comment('宿泊不可');
            $table->boolean('is_all_room')->comment('全部屋タイプ');
            $table->json('room_type_ids')->nullable()->comment('特定の部屋タイプ');
            $table->tinyInteger('is_rate')->comment('定額');
            $table->integer('fixed_amount')->nullable()->comment('定額料金');
            $table->integer('rate')->nullable()->comment('大人料金のxx%');
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
        if (config('migrate.migrations.skip_hotel_kids_policies')) {
            return;
        }
        
        Schema::dropIfExists('hotel_kids_policies');
    }
}
