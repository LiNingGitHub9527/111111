<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessColumnToHotelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotels')) {
            return;
        }
        Schema::table('hotels', function (Blueprint $table) {
            $table->tinyInteger('business_type')->default(1)->comment('1: ホテル、2: 塗装、3: 美容、4: サウナ、5: 不動産');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_hotels')) {
            return;
        }
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('business_type');
        });
    }
}
