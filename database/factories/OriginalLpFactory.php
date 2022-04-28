<?php

use Faker\Generator as Faker;
use App\Models\OriginalLp;

$factory->define(OriginalLp::class, function (Faker $faker) {
    $title = $faker->name . 'LP';
    $public_status = $faker->boolean;
    $now = now();
    return [
        'title' => $title,
        'public_status' => $public_status,
        'created_at' => $now,
        'updated_at' => $now,
    ];
});
