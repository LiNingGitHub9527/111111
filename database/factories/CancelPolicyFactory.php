<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\CancelPolicy;
use Faker\Generator as Faker;

$factory->define(CancelPolicy::class, function (Faker $faker) {
    return [
        'name' => $faker->name() . 'キャンセルポリシー',
        'is_free_cancel' => 1,
        'free_day' => 7,
        'free_time' => 0,
        'cancel_charge_rate' => 50,
        'no_show_charge_rate' => 100,
    ];
});
