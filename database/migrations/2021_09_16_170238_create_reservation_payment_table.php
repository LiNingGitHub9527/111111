<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservation_payment')) {
            return;
        }

        Schema::create('reservation_payment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reservation_id')->default(0);
            $table->tinyInteger('type')->nullable()->comment('1 Authory, 2 cancel_uthory, 3 Immediate collection, 4 captured, 5 partialRefund');
            $table->tinyInteger('status')->nullable();
            $table->text('message')->nullable();
            $table->integer('amount')->nullable()->default(0);
            $table->string('stripe_payment_id')->nullable()->comment('Stripe決済ID');
            $table->string('refund_id')->nullable();
            $table->timestamp('handle_time')->nullable();
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
        if (config('migrate.migrations.skip_reservation_payment')) {
            return;
        }

        Schema::dropIfExists('reservation_payment');
    }
}
