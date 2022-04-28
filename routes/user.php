<?php
Route::group(['namespace' => 'User', 'as' => 'user.'], function ($router) {
    // 検索画面、宿泊
    $router->get('page/search_panel/{url_param?}', 'BookingController@searchPanel')->name('booking_search_panel');
    // 検索画面、デイユース
    $router->post('dayuse/checkin_time/get', 'DayuseBookingController@ajaxGetSelectCheckinTime')->name('booking_ajax_get_select_checkin_time');
    $router->post('dayuse/stay_time/get', 'DayuseBookingController@ajaxGetSelectStaytime')->name('booking_ajax_get_select_stay_time');

    Route::group(['prefix' => 'booking'], function ($router) {
        // 宿泊・デイユース共通
        $router->post('search/plan/show', 'BookingController@ajaxPlanDetail')->name('booking_ajax_plan_detail');
        $router->post('search/room_type', 'BookingController@ajaxGetPlanRoomTypes')->name('booking_ajax_plan_room_type');
        $router->post('search/room_type/detail', 'BookingController@ajaxGetRoomTypeDetail')->name('booking_ajax_plan_room_type_detail');
        $router->post('search/room_type/selected', 'BookingController@ajaxGetRoomSelected')->name('booking_ajax_plan_room_type_selected');
        $router->post('search/room_type/cancel', 'BookingController@selectedRoomCancel')->name('booking_ajax_plan_room_type_select_cancel');
        $router->get('info/input', 'BookingController@inputBookingInfo')->name('booking_info_input');

        // 宿泊
        $router->get('plan/preview', 'BookingController@planPreview')->name('plan_preview');
        $router->post('search', 'BookingController@bookingSearch')->name('booking_stay_search');
        $router->post('confirm', 'BookingController@saveStayReservationData')->name('booking_confirm_booking');
        $router->post('update', 'BookingController@updateStayReservationData')->name('booking_update_booking');
        $router->post('prepay', 'BookingController@ajaxPrePay')->name('booking_ajax_do_prepay');

        // デイユース
        $router->post('dayuse/search', 'DayuseBookingController@bookingSearch')->name('booking_dayuse_search');
        $router->post('dayuse/confirm', 'DayuseBookingController@saveDayuseReservationData')->name('booking_confirm_dayuse_booking');
        $router->post('dayuse/update', 'DayuseBookingController@updateDayuseReservationData')->name('booking_update_dayuse_booking');

        // 予約確認・変更
        $router->get('show/{token}', 'BookingController@bookingShow')->name('booking_show');
        $router->post('cancel_policy/check', 'BookingController@checkCancelCondition')->name('booking_ajax_check_is_cancelable');
        $router->post('cancel_confirm', 'BookingController@cancelConfirm')->name('booking_ajax_reserve_cancel_confirm');
        $router->post('change_confirm', 'BookingController@ajaxChangeReserve')->name('booking_ajax_reserve_change_confirm');
    });

    $router->get('lp/{urlParam}', 'LpController@index')->name('lp');
});

Route::group(['namespace' => 'PmsApi'], function ($router) {
    Route::group(['namespace' => 'User'], function ($router) {
        $router->get('line/input/info/{hash?}', 'LineReservationController@lineInputDisplay')->name('pms_line_api_input_info');
    });
});
