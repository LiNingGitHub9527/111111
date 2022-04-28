<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToLayoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_layouts')) {
            return;
        }

        Schema::table('layouts', function (Blueprint $table) {
            $table->integer('sort_num')->default(0)->after('public_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_layouts')) {
            return;
        }

        Schema::table('layouts', function (Blueprint $table) {
            $table->dropColumn('sort_num');
        });
    }
}
