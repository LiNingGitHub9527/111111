<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToPlansTable extends Migration
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

        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_max_stay_days')->after('is_min_stay_days');
            $table->integer('max_stay_days')->nullable()->after('min_stay_days');
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

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_max_stay_days');
            $table->dropColumn('max_stay_days');
        });
    }
}
