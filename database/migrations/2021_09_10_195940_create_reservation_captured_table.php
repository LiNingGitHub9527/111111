<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationCapturedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservation_captured')) {
            return;
        }
        Schema::create('reservation_captured', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reservation_id');
            $table->tinyInteger('payment_status')->nullable();
            $table->text('payment_information')->nullable();
            $table->tinyInteger('captured_status')->nullable();
            $table->integer('payment_amount')->nullable()->default(0);
            $table->integer('amount_captured')->nullable()->default(0);
            $table->string('stripe_payment_id')->nullable()->comment('Stripe決済ID');
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
        if (config('migrate.migrations.skip_reservation_captured')) {
            return;
        }
        Schema::dropIfExists('reservation_captured');
    }
}
