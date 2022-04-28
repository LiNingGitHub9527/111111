<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForgotTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forgot_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email', 100)->comment('メールアドレス')->unique();
            $table->string('token', 100)->unique();
            $table->timestamp('token_expires_at');
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
        Schema::dropIfExists('forgot_tokens');
    }
}
