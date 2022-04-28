<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnRenderHtmlToLpLayoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_lp_layouts')) {
            return;
        }

        Schema::table('lp_layouts', function (Blueprint $table) {
            $table->mediumText('render_html')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_lp_layouts')) {
            return;
        }

        Schema::table('lp_layouts', function (Blueprint $table) {
            $table->text('render_html')->change();
        });
    }
}
