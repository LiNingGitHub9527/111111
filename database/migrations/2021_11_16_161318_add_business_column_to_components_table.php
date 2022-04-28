<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessColumnToComponentsTable extends Migration
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
            $table->json('business_types')->nullable()->comment('1: ホテル、2: 塗装、3: 美容、4: サウナ、5: 不動産')->after('public_status');
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
            $table->dropColumn('business_types');
        });
    }
}
