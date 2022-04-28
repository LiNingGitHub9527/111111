<?php

return [
    'pay_type' => [
        0 => '現地決済・事前決済いずれも可',
        1 => '現地決済のみ',
        2 => '事前決済のみ'
    ],
    'card_type' => [
        1 => 'VISA',
        2 => 'MasterCard',
        3 => 'American Express',
    ],
    'stripe_api_key' => [
        'test' => env('STRIPE_API_TEST_KEY'),
        'production' => env('STRIPE_API_TEST_KEY'),
    ],
    'payment_status' => [
        'unpay' => 0,
        'pay' => 1,
        'authory' => 2,
    ],
    'webhook_expired' => [
        'secret_key' => env('WEBHOOK_EXPIRED_SECRET_KEY'),
    ],

    //Commissiong Percentage
    'commission_rate' =>  env('COMMISSION_RATE', 0.03),
    'payment_commission_rate' => env('PAYMENT_COMMISSION_RATE', 0.036)
];
