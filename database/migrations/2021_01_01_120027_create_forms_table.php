<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_forms')) {
            return;
        }
        
        Schema::create('forms', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id');
            $table->integer('hotel_id');
            $table->string('name', 40);
            $table->json('form_parts_ids');

            $table->boolean('is_deadline');
            $table->timestamp('deadline_start')->nullable();
            $table->timestamp('deadline_end')->nullable();

            $table->boolean('is_sale_period');
            $table->timestamp('sale_period_start')->nullable();
            $table->timestamp('sale_period_end')->nullable();

            $table->boolean('is_plan');
            $table->json('plan_ids');
            $table->boolean('is_room_type');
            $table->json('room_type_ids');
            $table->boolean('is_special_price');
            $table->boolean('is_hand_input');
            $table->json('hand_input_room_prices');
            $table->boolean('is_all_plan');
            $table->json('all_plan_price');
            $table->json('special_plan_prices');
            $table->json('custom_form_item_ids');
            $table->boolean('public_status')->default(0);
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
        if (config('migrate.migrations.skip_forms')) {
            return;
        }
        
        Schema::dropIfExists('forms');
    }
}
