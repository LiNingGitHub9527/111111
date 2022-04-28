<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\OriginalHotelHardItem;
use Faker\Generator as Faker;

$factory->define(OriginalHotelHardItem::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
    ];
});
