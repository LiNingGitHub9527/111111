<?php
Route::group(['namespace' => 'User', 'as' => 'user.'], function ($router) {
    Route::group(['namespace' => 'Other', 'as' => 'other.'], function ($router) {
        // 部屋タイプ・日付 予約枠選択
        $router->get('page/reservation/search_panel', 'BookingOtherController@renderSearchPanel')->name('render_search_panel');

        Route::prefix('booking')->group(function ($router) {
            Route::prefix('reservation')->group(function ($router) {
                $router->get('reservation_block', 'BookingOtherController@getReservationBlock')->name('get_reservation_block');
                // 予約情報入力画面(セッションへのデータ保存を行い、「info/input/render」へリダイレクトする)
                $router->post('info/input', 'BookingOtherController@inputBookingInfo')->name('booking_info_input');
                // 予約情報入力画面(画面表示のviewを返却する)
                $router->get('info/input/render', 'BookingOtherController@renderInputBookingInfo')->name('render_booking_info_input');
                $router->get('info/input/admin/render', 'BookingOtherController@renderAdminInputBookingInfo')->name('render_admin_booking_info_input');
                // 予約情報の入力値確認
                $router->post('confirm', 'BookingOtherController@saveReservationData')->name('booking_confirm_booking');
                // 予約情報の更新
                $router->post('update', 'BookingOtherController@updateReservationData')->name('booking_update_booking');
                // 予約確認・変更
                $router->get('show/{token}', 'BookingOtherController@bookingShow')->name('booking_show');
                $router->post('cancel_policy/check', 'BookingOtherController@checkCancelCondition')->name('booking_cancel_policy_check_ajax');
                $router->post('cancel_confirm', 'BookingOtherController@otherCancelConfirm')->name('booking_cancel_confirm_ajax');
                $router->post('change_confirm', 'BookingOtherController@ajaxChangeReserve')->name('booking_ajax_reserve_change_confirm');
                // 部屋タイプの詳細取得
                $router->get('search/room_type/detail', 'BookingOtherController@ajaxGetRoomTypeDetail')->name('booking_ajax_plan_room_type_detail');
            });
        });
    });
});
