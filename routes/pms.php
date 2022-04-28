<?php

Route::group(['prefix' => 'pms', 'namespace' => 'PmsApi', 'as' => 'pmsApi.'], function () {

    // user routes
    Route::group(['prefix' => 'user', 'namespace' => 'User'], function ($router) {
        Route::group(['prefix' => 'line'], function ($router) {
            $router->post('base_info/send', 'LineReservationController@getReserveBaseInfo')->name('pms_line_api_get_base_info');
            $router->post('adultnum/send', 'LineReservationController@sendSelectAdultNum')->name('pms_line_api_send_adult_num');
            $router->post('childnum/send', 'LineReservationController@sendSelectChildNum')->name('pms_line_api_send_child_num');
            $router->post('plan/send', 'LineReservationController@sendSelectPlan')->name('pms_line_api_send_plan');
            $router->post('plan/detail/send', 'LineReservationController@getPlanDetail')->name('pms_line_api_send_plan_detail');
            $router->post('room_type/send', 'LineReservationController@sendSelectRoomType')->name('pms_line_api_send_room_type');
            $router->post('room_type/detail/send', 'LineReservationController@getRoomTypeDetail')->name('pms_line_api_send_room_type_detail');
            $router->post('reserve/save', 'LineReservationController@saveStayReservationData')->name('pms_line_api_save_stay_reservation');

            Route::group(['prefix' => 'dayuse'], function ($router) {
                $router->post('base_info/send', 'LineDayuseReserveController@getReserveBaseInfo')->name('pms_line_api_dayuse_base_info');
                $router->post('checkin_time/send', 'LineDayuseReserveController@sendSelectCheckinTime')->name('pms_line_api_dayuse_checkin_time');
                $router->post('stay_time/send', 'LineDayuseReserveController@sendSelectStayTime')->name('pms_line_api_dayuse_stay_time');
                $router->post('adult_num/send', 'LineDayuseReserveController@sendSelectAdultNum')->name('pms_line_api_dayuse_adult_num');
                $router->post('child_num/send', 'LineDayuseReserveController@sendSelectChildNum')->name('pms_line_api_dayuse_child_num');
                $router->post('plan/send', 'LineDayuseReserveController@sendSelectPlan')->name('pms_line_api_dayuse_plan');
                $router->post('room_type/send', 'LineDayuseReserveController@sendSelectRoomType')->name('pms_line_api_dayuse_room_type');
                $router->post('reserve/save', 'LineDayuseReserveController@saveDayuseReserveData')->name('pms_line_api_dayuse_room_type');
            });
        });
    });
});