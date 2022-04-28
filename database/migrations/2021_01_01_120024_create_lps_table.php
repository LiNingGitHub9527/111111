<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_lps')) {
            return;
        }
        
        Schema::create('lps', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id');
            $table->integer('hotel_id');
            $table->integer('original_lp_id');
            $table->string('title', 40);
            $table->string('cover_image', 255);
            $table->integer('form_id');
            $table->string('url_param', 255)->nullable();
            $table->tinyInteger('public_status')->default(0);
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
        if (config('migrate.migrations.skip_lps')) {
            return;
        }
        
        Schema::dropIfExists('lps');
    }
}
