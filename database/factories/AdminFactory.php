<?php

use Faker\Generator as Faker;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$factory->define(Admin::class, function (Faker $faker) {
    static $password;

    $firstName = $faker->firstName;
    $lastName = $faker->lastName;
    $fullName = $lastName . $firstName;
    $email = $faker->unique()->safeEmail;
    if (empty($password)) {
        $password = bcrypt('pa@123456');
    }
    $apiToken = sha1(time() . Str::random(60));
    $apiTokenExpiresAt = now()->addMonths(2);
    $now = now();
    return [
        'name' => $fullName,
        'email' => $email,
        'password' => $password,
        'api_token' => $apiToken,
        'api_token_expires_at' => $apiTokenExpiresAt,
        'created_at' => $now,
        'updated_at' => $now,
    ];
});
