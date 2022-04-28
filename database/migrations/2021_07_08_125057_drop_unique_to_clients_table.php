<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_clients')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_clients')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->unique('email');
        });
    }
}
