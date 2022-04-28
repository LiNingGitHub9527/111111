<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsClosedToReservationBlocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservation_blocks')) {
            return;
        }

        Schema::table('reservation_blocks', function (Blueprint $table) {
            $table->boolean('is_closed')->default(0)->after('is_updated');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_reservation_blocks')) {
            return;
        }

        Schema::table('reservation_blocks', function (Blueprint $table) {
            $table->dropColumn('is_closed');
        });
    }
}
