<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservations')) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->tinyInteger('reservation_update_status')->nullable()->after('reservation_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_reservations')) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('reservation_update_statue');
        });
    }
}
