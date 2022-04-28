<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToFormsTable extends Migration
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
            $table->json('all_room_type_price')->nullable()->after('hand_input_room_prices');
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
            $table->dropColumn('all_room_type_price');
        });
    }
}
