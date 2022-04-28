<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHotelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_hotels')) {
            return;
        }
        
        Schema::create('hotels', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id')->index();
            $table->integer('crm_base_id')->nullable()->index();
            $table->string('name', 100)->comment('ホテル名');
            $table->string('address', 255)->comment('住所');
            $table->string('tel', 20)->comment('電話番号');
            $table->string('email', 100)->comment('メールアドレス');
            $table->string('person_in_charge', 40)->nullable()->comment('担当者様氏名');
            $table->integer('rate_plan_id')->nullable()->default(0)->comment('料金プラン');
            $table->datetime('agreement_date')->nullable()->comment('契約日');
            $table->string('logo_img', 255)->nullable();
            $table->time('checkin_start')->nullable();
            $table->time('checkin_end')->nullable();
            $table->time('checkout_end')->nullable();
            $table->tinyInteger('sync_status')->default(0);
            $table->timestamp('last_sync_time')->nullable();
            $table->string('tema_login_id', 100)->nullable()->index();
            $table->string('tema_login_password', 255)->nullable();
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
        if (config('migrate.migrations.skip_hotels')) {
            return;
        }
        
        Schema::dropIfExists('hotels');
    }
}
