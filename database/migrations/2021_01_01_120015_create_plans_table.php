<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_plans')) {
            return;
        }
        
        Schema::create('plans', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hotel_id');
            $table->string('name', 40);
            $table->text('description');
            $table->integer('cancel_policy_id');
            $table->integer('sort_num');
            $table->boolean('is_meal');
            $table->json('meal_types');
            $table->boolean('is_min_stay_days');
            $table->integer('min_stay_days')->nullable();
            $table->boolean('is_day_ago');
            $table->integer('day_ago')->nullable();
            $table->boolean('is_new_plan');
            $table->integer('existing_plan_id')->nullable()->default(0);
            $table->tinyInteger('up_or_down')->nullable();
            $table->tinyInteger('calculate_method')->nullable();
            $table->integer('calculate_num')->nullable();
            $table->json('room_type_ids');
            $table->tinyInteger('prepay');
            $table->tinyInteger('public_status');

            $table->tinyInteger('stay_type')->default(1)->comment('1: 宿泊, 2: デイユース');
            $table->integer('checkin_start_time')->nullable();
            $table->integer('last_checkin_time')->nullable();
            $table->integer('last_checkout_time')->nullable();
            $table->integer('min_stay_time')->nullable();
            $table->integer('min_stay_days')->nullable()->change();
            $table->tinyInteger('fee_class_type')->default(2)->comment('1: 人数課金, 2: 部屋課金 ※全て2にする');

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
        if (config('migrate.migrations.skip_plans')) {
            return;
        }
        
        Schema::dropIfExists('plans');
    }
}
