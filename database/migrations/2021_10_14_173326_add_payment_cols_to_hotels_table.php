<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentColsToHotelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string("bank_code");
            $table->string("branch_code");
            $table->tinyInteger("deposit_type")->nullable()->comment('1：普通、2：当座、4：貯蓄');
            $table->string("account_number");
            $table->string("recipient_name");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['bank_code', 'branch_code', 'deposit_type', 'account_number', 'recipient_name']);
        });
    }
}
