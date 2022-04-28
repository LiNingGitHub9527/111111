<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToOriginalHotelHardCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_original_hotel_hard_categories')) {
            return;
        }

        Schema::table('original_hotel_hard_categories', function (Blueprint $table) {
            $table->integer('sort_num')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_original_hotel_hard_categories')) {
            return;
        }

        Schema::table('original_hotel_hard_categories', function (Blueprint $table) {
            $table->dropColumn('sort_num');
        });
    }
}
