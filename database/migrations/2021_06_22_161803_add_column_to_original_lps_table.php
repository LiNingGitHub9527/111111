<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToOriginalLpsTable extends Migration
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
            $table->json('category_ids')->nullable()->after('cover_image');
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
            $table->dropColumn('category_ids');
        });
    }
}
