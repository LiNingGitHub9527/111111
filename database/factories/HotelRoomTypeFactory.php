<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\HotelRoomType;
use Faker\Generator as Faker;

$factory->define(HotelRoomType::class, function (Faker $faker) {
    $now = now();
    return [
        'name' => $faker->name,
        'room_num' => rand(1, 4),
        'adult_num' => rand(1, 4),
        'child_num' => rand(1, 4),
        'room_size' => rand(1, 4),
        'sort_num' => rand(1, 4),
        'sale_condition' => rand(0, 1),
        'created_at' => $now,
        'updated_at' => $now,
    ];
});
