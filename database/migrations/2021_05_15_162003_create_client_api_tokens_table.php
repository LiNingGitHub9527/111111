<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientApiTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_api_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('client_id');
            $table->string('api_token', 100)->unique();
            $table->integer('pms_user_id')->nullable();
            $table->timestamp('api_token_expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_api_tokens');
    }
}
