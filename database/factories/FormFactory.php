<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use \App\Models\Form;

$factory->define(Form::class, function (Faker $faker) {

    $name = $faker->name . 'フォーム';
    $isDeadline = random_int(0, 1);
    $isSalePeriod = random_int(0, 1);
    $isPlan = random_int(0, 1);
    $isRoomType = random_int(0, 1);
    $isSpecialPrice = random_int(0, 1);
    if ($isSpecialPrice == 1) {
        $isHandInput = random_int(0, 1);
    } else {
        $isHandInput = 0;
    }
    if ($isHandInput == 1) {
        $isAllPlan = 0;
    } else {
        $isAllPlan = random_int(0, 1);
    }

    $handInputRoomPrices = [];
    $allPlanPrice = [];
    $specialPlanPrices = [];

    if ($isHandInput == 1) {
        $handInputRoomPrices = 1;
    }

    if ($isAllPlan == 1) {
        $allPlanPrice = 1;
    } else {
        $specialPlanPrices = 1;
    }

    $now = now();
    return [
        'name' => $name,
        'is_deadline' => $isDeadline,
        'is_sale_period' => $isSalePeriod,
        'is_plan' => $isPlan,
        'is_room_type' => $isRoomType,
        'is_special_price' => $isSpecialPrice,
        'is_hand_input' => $isHandInput,
        'hand_input_room_prices' => $handInputRoomPrices,
        'is_all_plan' => $isAllPlan,
        'all_plan_price' => $allPlanPrice,
        'special_plan_prices' => $specialPlanPrices,
        'created_at' => $now,
        'updated_at' => $now,
        'all_special_plan_prices' => [],
        'custom_form_item_ids' => [],
        'form_parts_ids' => [],
        'plan_ids' => [],
        'room_type_ids' => [],
    ];
});
