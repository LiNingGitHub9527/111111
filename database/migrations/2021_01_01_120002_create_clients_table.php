<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientsTable extends Migration
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

        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pms_client_id')->nullable()->index();
            $table->string('company_name')->comment('会社名');
            $table->string('address')->nullable()->comment('会社所在地');
            $table->string('tel', 20)->nullable()->comment('電話番号');
            $table->string('person_in_charge', 40)->nullable()->comment('担当者様氏名');
            $table->string('email', 100)->comment('メールアドレス')->unique();
            $table->string('password', 128)->comment('パスワード');
            $table->string('initial_password', 128)->nullable()->comment('初期パスワード');
            $table->integer('hotel_num')->default(0);
            $table->tinyInteger('sync_status')->default(0);
            $table->timestamp('last_sync_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
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

        Schema::dropIfExists('clients');
    }
}
