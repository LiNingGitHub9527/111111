<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Client;
use App\Models\ClientApiToken;
use Faker\Generator as Faker;

$factory->define(ClientApiToken::class, function (Faker $faker) {
    $token = sha1(time() . Str::random(60)) . '_' . 0;
    $now = now();
    return [
        'client_id' => factory(Client::class),
        'api_token' => $token,
        'api_token_expires_at' => $now->addDays(365),
        'created_at' => $now,
        'updated_at' => $now,
    ];
});
