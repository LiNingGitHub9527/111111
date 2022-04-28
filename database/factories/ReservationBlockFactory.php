<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ReservationBlock;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(ReservationBlock::class, function (Faker $faker) {
    $now = Carbon::now();
    return [
        'is_available' => 1,
        'reserved_num' => 0,
        'room_num' => $faker->numberBetween(1, 10),
        'person_capacity' => $faker->numberBetween(1, 10),
        'price' => $faker->numberBetween(1000, 10000),
        'date' => $now,
        'start_hour' => $faker->numberBetween(9, 15),
        'start_minute' => $faker->numberBetween(0, 59),
        'end_hour' => $faker->numberBetween(16, 27),
        'end_minute' => $faker->numberBetween(0, 59),
        'is_updated' => 1,
    ];
});
