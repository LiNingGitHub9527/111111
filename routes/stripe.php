<?php
Route::group(['namespace' => 'StripeApi', 'as' => 'stripe.', 'prefix' => 'stripe'], function ($router) {

    // オーソリ期限切れの通知を受けるHook
    $router->post('charge/expired', 'StripeHookController@chargeExpired')->name('stripe_charge_expired');
});
