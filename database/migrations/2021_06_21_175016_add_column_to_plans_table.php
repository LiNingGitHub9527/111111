<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToPlansTable extends Migration
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
            $table->string('cover_image', 255)->nullable()->after('fee_class_type');
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
            $table->dropColumn('cover_image');
        });
    }
}
