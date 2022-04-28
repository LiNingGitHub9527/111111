<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnsToHotelHardItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotel_hard_items')) {
            return;
        }

        Schema::table('hotel_hard_items', function (Blueprint $table) {
            $table->integer('original_hotel_hard_item_id')->after('hotel_id');
            $table->dropColumn('hard_category_id');
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_hotel_hard_items')) {
            return;
        }

        Schema::table('hotel_hard_items', function (Blueprint $table) {
            $table->string('name', 40);
            $table->integer('hard_category_id');
            $table->dropColumn('original_hotel_hard_item_id');
        });
    }
}
