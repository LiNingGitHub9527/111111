<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ReservationCancelPolicy;
use Faker\Generator as Faker;

$factory->define(ReservationCancelPolicy::class, function (Faker $faker) {
    return [
        'is_free_cancel' => random_int(0, 1),
        'free_day' => random_int(1, 14),
        'free_time' => random_int(0, 23),
        'cancel_charge_rate' => random_int(10, 50),
        'no_show_charge_rate' => random_int(50, 100),
    ];
});
