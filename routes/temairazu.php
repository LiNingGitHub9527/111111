<?php

Route::group(['namespace' => 'Temairazu', 'as' => 'temairazu.'], function () {

    //ログイン認証
    Route::post('tema000', 'TemairazuController@tema000')->name('tema000');

    //部屋情報取得
    Route::post('tema005', 'TemairazuController@tema005')->name('tema005');

    //プラン情報取得
    Route::post('tema010', 'TemairazuController@tema010')->name('tema010');

    //在庫登録
    Route::post('tema030', 'TemairazuController@tema030')->name('tema030');

    //料金登録
    Route::post('tema036', 'TemairazuController@tema036')->name('tema036');

    //在庫取得
    Route::post('tema130', 'TemairazuController@tema130')->name('tema130');

    //料金取得
    Route::post('tema135', 'TemairazuController@tema135')->name('tema135');

    //予約情報取得
    Route::post('tema201', 'TemairazuController@tema201')->name('tema201');

    //予約通知 テスト
    //cid: client id
    //rid: reservation id
    Route::post('tema/notification/{cid}/{rid}', 'TemairazuController@notification')->name('notification');
});
