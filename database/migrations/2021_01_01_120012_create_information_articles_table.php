<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInformationArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('information_articles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 100);
            $table->text('lead');
            $table->string('img', 255);

            $table->text('heading1');
            $table->string('img1', 255);
            $table->text('paragraph1');

            $table->text('heading2');
            $table->string('img2', 255);
            $table->text('paragraph2');

            $table->text('heading3');
            $table->string('img3', 255);
            $table->text('paragraph3');

            $table->text('heading4');
            $table->string('img4', 255);
            $table->text('paragraph4');

            $table->text('heading5');
            $table->string('img5', 255);
            $table->text('paragraph5');

            $table->tinyInteger('public_status');
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
        Schema::dropIfExists('information_articles');
    }
}
