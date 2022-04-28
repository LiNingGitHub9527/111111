<?php

use Faker\Generator as Faker;
use App\Models\Hotel;

$factory->define(Hotel::class, function (Faker $faker) {

    $name = $faker->name . 'ホテル';
    $address = $faker->address;
    $tel = $faker->phoneNumber;
    $firstName = $faker->firstName;
    $lastName = $faker->lastName;
    $personInCharge = $lastName . $firstName;
    $email = $faker->unique()->safeEmail;
    $agreementDate = $faker->dateTime;
    $ratePlanId = rand(1, 4);
    $now = now();
    $bank_code = $faker->randomNumber;
    $branch_code = $faker->randomNumber;
    $deposit_type = 1;
    $account_number = $faker->randomNumber;;
    $recipient_name = "client{$faker->name}";
    return [
        'name' => $name,
        'address' => $address,
        'tel' => $tel,
        'person_in_charge' => $personInCharge,
        'email' => $email,
        'agreement_date' => $agreementDate,
        'rate_plan_id' => $ratePlanId,
        'checkin_start' => $now->format('Y-m-d 12:00:00'),
        'checkin_end' => $now->format('Y-m-d 23:59:00'),
        'checkout_end' => $now->format('Y-m-d 14:00:00'),
        'created_at' => $now,
        'updated_at' => $now,
        'bank_code' => $bank_code,
        'branch_code' => $branch_code,
        'deposit_type' => $deposit_type,
        'account_number' => $account_number,
        'recipient_name' => $recipient_name,
    ];
});
