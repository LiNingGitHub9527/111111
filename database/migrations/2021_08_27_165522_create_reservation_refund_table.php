<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationRefundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservation_refund')) {
            return;
        }
        Schema::create('reservation_refund', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('reservation_id');
            $table->tinyInteger('type');
            $table->tinyInteger('status')->nullable();
            $table->text('refund_information')->nullable()->comment('内容​');
            $table->integer('reservation_amount')->nullable()->default(0);
            $table->integer('refund_amount')->nullable()->default(0);
            $table->string('stripe_payment_id')->nullable()->comment('Stripe決済ID');
            $table->string('refund_id')->nullable()->comment('Stripe払い戻しID');
            $table->timestamp('handle_date')->nullable();

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
        if (config('migrate.migrations.skip_reservation_refund')) {
            return;
        }
        Schema::dropIfExists('reservation_refund');
    }
}
