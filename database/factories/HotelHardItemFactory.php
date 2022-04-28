<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\HotelHardItem;
use Faker\Generator as Faker;

$factory->define(HotelHardItem::class, function (Faker $faker) {
    return [
        'is_all_room' => 1,
        'room_type_ids' => [],
    ];
});
