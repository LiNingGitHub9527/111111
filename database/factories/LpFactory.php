<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Lp;
use Faker\Generator as Faker;
use Ramsey\Uuid\Uuid;

$factory->define(Lp::class, function (Faker $faker) {
    $title = $faker->name . 'LP';
    $publicStatus = $faker->boolean;
    $urlParam = Uuid::uuid1(time());
    return [
        'title' => $title,
        'original_lp_id' => 0,
        'cover_image' => '',
        'public_status' => $publicStatus,
        'url_param' => $urlParam,
    ];
});
