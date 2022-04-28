<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToReservationCancelPolicyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservation_cancel_policy')) {
            return;
        }
        Schema::table('reservation_cancel_policy', function (Blueprint $table) {
            $table->tinyInteger('free_time')->after('free_day');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_reservation_cancel_policy')) {
            return;
        }
        Schema::table('reservation_cancel_policy', function (Blueprint $table) {
            $table->dropColumn('free_time');
        });
    }
}
