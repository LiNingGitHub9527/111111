<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToFormsTable extends Migration
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

        Schema::table('forms', function (Blueprint $table) {
            $table->json('all_special_plan_prices')->after('special_plan_prices');
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

        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('all_special_plan_prices');
        });
    }
}
