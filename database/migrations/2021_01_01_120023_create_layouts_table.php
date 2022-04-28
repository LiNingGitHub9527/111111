<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLayoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_layouts')) {
            return;
        }

        Schema::create('layouts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 40);
            $table->integer('component_id');
            $table->text('html');
            $table->text('render_html');
            $table->string('css_file_name', 100)->nullable();
            $table->string('js_file_name', 100)->nullable();
            $table->string('css_file', 255)->nullable();
            $table->string('js_file', 255)->nullable();
            $table->tinyInteger('public_status')->default(0);
            $table->string('preview_image', 255)->nullable();
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
        if (config('migrate.migrations.skip_layouts')) {
            return;
        }
        Schema::dropIfExists('original_layouts');
        Schema::dropIfExists('layouts');
    }
}
