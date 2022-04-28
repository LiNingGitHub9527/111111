<?php

use Faker\Generator as Faker;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$factory->define(Client::class, function (Faker $faker) {
    static $password;

    $companyName = $faker->company;
    $address = $faker->address;
    $tel = $faker->phoneNumber;
    $firstName = $faker->firstName;
    $lastName = $faker->lastName;
    $personInCharge = $lastName . $firstName;
    $email = $faker->unique()->safeEmail;
    if (empty($password)) {
        $password = bcrypt('pa@123456');
    }
    $now = now();
    return [
        'company_name' => $companyName,
        'address' => $address,
        'tel' => $tel,
        'person_in_charge' => $personInCharge,
        'email' => $email,
        'password' => $password,
        'created_at' => $now,
        'updated_at' => $now,
    ];
});
