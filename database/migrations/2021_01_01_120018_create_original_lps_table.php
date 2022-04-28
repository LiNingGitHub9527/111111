<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOriginalLpsTable extends Migration
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
        
        Schema::create('original_lps', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 40);
            $table->string('cover_image', 255)->nullable();
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
        if (config('migrate.migrations.skip_original_lps')) {
            return;
        }
        
        Schema::dropIfExists('original_lps');
    }
}
