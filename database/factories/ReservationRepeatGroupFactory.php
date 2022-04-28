<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ReservationRepeatGroup;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(ReservationRepeatGroup::class, function (Faker $faker) {
    $now = Carbon::now();
    return [
        'start_hour' => $faker->numberBetween(9, 15),
        'start_minute' => $faker->numberBetween(0, 59),
        'end_hour' => $faker->numberBetween(16, 27),
        'end_minute' => $faker->numberBetween(0, 59),
        'repeat_interval_type' => $faker->numberBetween(1, 2),
        'repeat_start_date' => $now,
        'repeat_end_date' => $now->addDay(),
    ];
});
