<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Add3columnsRelatedRoomTypePriceToFormTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_forms')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->tinyInteger('is_all_room_price_setting')->nullable()->after('cancel_policy_id')->comment("0: 全ての部屋タイプの割引を一括登録しない, 1: する");
            $table->json('all_room_price_setting')->nullable()->after('cancel_policy_id')->comment("全部屋タイプの料金設定");
            $table->json('special_room_price_settings')->nullable()->after('cancel_policy_id')->comment("選択された一部の部屋タイプの料金設定");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('migrate.migrations.skip_forms')) {
            return;
        }

        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('is_all_room_price_setting');
            $table->dropColumn('all_room_price_setting');
            $table->dropColumn('special_room_price_settings');
        });
    }
}
