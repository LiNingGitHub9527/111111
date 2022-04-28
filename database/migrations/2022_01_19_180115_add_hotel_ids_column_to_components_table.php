<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHotelIdsColumnToComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_components')) {
            return;
        }

        Schema::table('components', function (Blueprint $table) {
            $table->json('hotel_ids')->nullable()->after('business_types');
            $table->boolean('is_limit_hotel')->default(0)->after('business_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_components')) {
            return;
        }
        
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn('hotel_ids');
            $table->dropColumn('is_limit_hotel');
        });
    }
}
