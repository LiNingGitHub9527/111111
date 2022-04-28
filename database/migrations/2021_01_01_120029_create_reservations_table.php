<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('migrate.migrations.skip_reservations')) {
            return;
        }

        Schema::create('reservations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id');
            $table->integer('hotel_id');
            $table->string('reservation_code')->nullable()->unique();
            $table->string('name', 42);
            $table->string('name_kana', 100)->nullable();
            $table->string('last_name', 20)->comment('氏名（姓）');
            $table->string('first_name', 20)->comment('氏名（名）');
            $table->string('last_name_kana', 20)->nullable()->comment('ヨミガナ（姓）');
            $table->string('first_name_kana', 20)->nullable()->comment('ヨミガナ（名）');

            $table->timestamp('checkin_start')->nullable();
            $table->timestamp('checkin_end')->nullable();
            $table->timestamp('checkout_end')->nullable();

            $table->string('email')->nullable();
            $table->string('tel', 20)->nullable();
            $table->tinyInteger('payment_method')->nullable()->default(0);

            $table->integer('accommodation_price')->nullable()->default(0)->comment('宿泊料金total');
            // $table->json('accommodation_price_detail')->nullable();
            $table->integer('commission_rate')->nullable()->default(0);
            $table->integer('commission_price')->nullable()->default(0)->comment('コミッション');
            $table->timestamp('reservation_date')->nullable()->comment('予約日');

            $table->string('stripe_customer_id')->nullable()->comment('Stripe顧客ID');
            $table->string('stripe_payment_id')->nullable()->comment('Stripe決済ID');
            $table->tinyInteger('payment_status')->nullable()->default(0)->comment('※事前決済の場合のみ| 0: 未決済, 1: 決済済み');

            $table->tinyInteger('reservation_status')->nullable()->default(0);
            $table->tinyInteger('tema_reservation_type')->nullable()->default(0);

            $table->datetime('checkin_time')->nullable()->comment('チェックイン時間');
            $table->datetime('checkout_time')->nullable()->comment('チェックアウト時間 ※デイユースの時のみ');
            $table->float('payment_commission_rate')->nullable();
            $table->integer('payment_commission_price')->nullable();
            $table->string('verify_token')->nullable();

            $table->tinyInteger('stay_type')->default(1)->comment('1: 宿泊, 2:デイユース');

            $table->integer('room_num')->nullable()->default(0);
            $table->integer('adult_num')->nullable()->default(0);
            $table->integer('child_num')->nullable()->default(0);
            $table->string('address', 255)->nullable();

            $table->dateTime('cancel_date_time')->nullable();
            $table->dateTime('change_date_time')->nullable();
            $table->integer('cancel_fee')->nullable();
            $table->string('lp_url_param')->nullable();
            $table->text('special_request')->nullable();

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
        if (config('migrate.migrations.skip_reservations')) {
            return;
        }
        
        Schema::dropIfExists('reservations');
    }
}
