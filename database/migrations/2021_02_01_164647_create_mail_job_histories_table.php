<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailJobHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_job_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('mail_to', 1024);
            $table->string('mail_from', 250);
            $table->string('mail_cc', 1024)->nullable();
            $table->string('mail_reply_to', 250)->nullable();
            $table->string('subject', 200);
            $table->text('contents');
            $table->string('charset', 20)->default('utf-8');
            $table->integer('remain')->default(3);
            $table->integer('mail_status')->default(0);
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
        Schema::dropIfExists('mail_job_histories');
    }
}
