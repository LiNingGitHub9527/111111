<?php

use Faker\Generator as Faker;
use App\Models\Component;

$factory->define(Component::class, function (Faker $faker) {
    $name = $faker->unique()->name . 'コンポーネント';
    $type = rand(1, 2);
    $public_status = $faker->boolean;
    $now = now();
    $data = new stdClass();
    if ($type == 2) {
        $data = [
            'desktop' => [
                'width' => random_int(300, 400),
                'height' => random_int(300, 400)
            ],
            'tablet' => [
                'width' => random_int(200, 300),
                'height' => random_int(200, 300)
            ],
            'mobile' => [
                'width' => random_int(100, 200),
                'height' => random_int(100, 200)
            ]
        ];
    }
    return [
        'name' => $name,
        'type' => $type,
        'data' => $data,
        'public_status' => $public_status,
        'created_at' => $now,
        'updated_at' => $now,
    ];
});
