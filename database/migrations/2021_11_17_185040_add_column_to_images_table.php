<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_images')) {
            return;
        }

        Schema::table('images', function (Blueprint $table) {
            $table->integer('hotel_id')->after('client_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_images')) {
            return;
        }

        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn('hotel_id');
        });
    }
}
