<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessColumnToOriginalLpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_original_lps')) {
            return;
        }

        Schema::table('original_lps', function (Blueprint $table) {
            $table->json('business_types')->nullable()->comment('1: ホテル、2: 塗装、3: 美容、4: サウナ、5: 不動産')->after('category_ids');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_original_lps')) {
            return;
        }
        
        Schema::table('original_lps', function (Blueprint $table) {
            $table->dropColumn('business_types');
        });
    }
}
