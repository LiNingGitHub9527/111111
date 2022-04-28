<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelPolicyIdColumnToFormsTable extends Migration
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
            $table->integer('cancel_policy_id')->nullable()->after('public_status');
            $table->integer('prepay')->nullable()->after('cancel_policy_id');
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
        if (Schema::hasColumn('forms', 'cancel_policy_id')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->dropColumn('cancel_policy_id');
            });
        }
        if (Schema::hasColumn('forms', 'prepay')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->dropColumn('prepay');
            });
        }
    }
}
