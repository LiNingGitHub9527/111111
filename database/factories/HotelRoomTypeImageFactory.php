<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\HotelRoomTypeImage;
use Faker\Generator as Faker;

$factory->define(HotelRoomTypeImage::class, function (Faker $faker) {
    return [
        'image' => $faker->name . ".jpg",
    ];
});
