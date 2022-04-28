<?php

use Faker\Generator as Faker;
use App\Models\Reservation;

$factory->define(Reservation::class, function (Faker $faker) {

    $firstName = $faker->firstName;
    $lastName = $faker->lastName;
    $name = $lastName . $firstName;
    $kanaName = $faker->kanaName;
    $email = $faker->email;
    $tel = $faker->phoneNumber;
    $accommodation_price = rand(5000, 15000);
    $commission_rate = rand(10, 20);
    $commission_price = (int)($accommodation_price*$commission_rate/100);

    $ratePlanId = rand(1, 4);
    $now = now();
    return [
        'name' => $name,
        'name_kana' => $kanaName,
        'last_name' => $lastName,
        'first_name' => $firstName,
        'tel' => $tel,
        'email' => $email,
        'accommodation_price' => $accommodation_price,
        'commission_rate' => $commission_rate,
        'commission_price' => $commission_price,
        'created_at' => $now,
        'updated_at' => $now,
    ];
});
