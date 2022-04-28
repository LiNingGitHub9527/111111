<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelMonthFeeSummaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotel_month_fee_summary')) {
            return;
        }
        
        Schema::create('hotel_month_fee_summary', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hotel_id')->index();
            $table->integer('client_id');
            $table->date('month')->index();
            $table->integer('rate_plan_id');
            $table->integer('monthly_fee');
            $table->integer('reservation_fee')->comment('予約手数料');
            $table->integer('reservation_num')->comment('対象予約件数');
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
        if (config('migrate.migrations.skip_hotel_month_fee_summary')) {
            return;
        }
        
        Schema::dropIfExists('hotel_month_fee_summary');
    }
}
