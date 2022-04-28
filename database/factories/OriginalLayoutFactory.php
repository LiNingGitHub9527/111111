<?php

use Faker\Generator as Faker;
use App\Models\Layout;

$factory->define(Layout::class, function (Faker $faker) {
    $name = $faker->unique()->name . 'レイアウト';
    $public_status = $faker->boolean;
    $now = now();
    return [
        'name' => $name,
        'public_status' => $public_status,
        'created_at' => $now,
        'updated_at' => $now,
    ];
});
