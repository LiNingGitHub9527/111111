<?php


Route::group(['prefix' => 'api', 'namespace' => 'Api', 'as' => 'api.'], function () {

    Route::get('{id}/pdfdownload/test', 'Admin\SaleController@pdfDownload');

    // admin routes
    Route::group(['prefix' => 'admin', 'namespace' => 'Admin'], function ($router) {

        $router->post('login', 'Auth\LoginController@login')->name('login');

        $router->group(['middleware' => 'admin_api'], function ($router) {
            Route::prefix('dashboard')->group(function ($router) {
                $router->get('list', 'DashboardController@list')->name('dashboard.list');
                $router->get('sales', 'DashboardController@sales')->name('dashboard.sales');
            });

            Route::prefix('client')->group(function ($router) {
                $router->get('list', 'ClientController@list')->name('client.list');
                $router->get('detail/{id}', 'ClientController@detail')->name('client.detail');
                $router->post('update', 'ClientController@update')->name('client.update');
                $router->post('create', 'ClientController@create')->name('client.create');
                $router->post('delete/{id}', 'ClientController@delete')->name('client.delete');
                $router->get('search', 'ClientController@search')->name('client.search');
                $router->get('{id}/hotel/list', 'HotelController@list')->name('hotel.list');
                $router->post('send-email/{id}', 'ClientController@send_email')->name('client.send-email');
            });

            Route::prefix('captured')->group(function ($router) {
                $router->get('list', 'CapturedController@list')->name('captured.list');
            });

            Route::prefix('hotel')->group(function ($router) {
                $router->get('detail/{id}', 'HotelController@detail')->name('hotel.detail');
                $router->post('update', 'HotelController@update')->name('hotel.update');
                $router->post('create', 'HotelController@create')->name('hotel.create');
                $router->post('delete/{id}', 'HotelController@delete')->name('hotel.delete');
                $router->get('initdata/{cid}', 'HotelController@initData')->name('hotel.initdata');
                $router->get('search', 'HotelController@search')->name('hotel.search');
                $router->get('{id}/reservation/list', 'ReservationController@list')->name('reservation.list');
            });


            Route::prefix('sale')->group(function ($router) {
                $router->get('list', 'SaleController@list')->name('sale.list');
                $router->get('csvdownload', 'SaleController@csvDownload')->name('sale.csvdownload');
                $router->get('{id}/pdfdownload', 'SaleController@pdfDownload')->name('sale.pdfdownload');
                $router->post('mail/bulkSend', 'SaleController@bulkSend')->name('sale.mail.bulksend');
                $router->post('{id}/send-mail', 'SaleController@sendMail')->name('sale.mail.send');
            });

            Route::prefix('fee')->group(function ($router) {
                $router->get('list', 'FeeController@list')->name('fee.list');
                $router->get('csvdownload', 'FeeController@csvDownload')->name('fee.csvdownload');
            });

            Route::prefix('reservation')->group(function ($router) {
                $router->get('csvdownload', 'ReservationController@csvDownload')->name('reservation.csvdownload');
                $router->get('search', 'ReservationController@search')->name('reservation.search');
                $router->get('detail/{id}', 'ReservationController@detail')->name('reservation.detail');
                $router->post('changeStatus/{id}', 'ReservationController@changeStatus')->name('reservation.changeStatus');
                $router->post('check/{id}', 'ReservationController@check')->name('reservation.check');
                $router->post('checkFreeCancel/{id}', 'ReservationController@checkFreeCancel')->name('reservation.checkFreeCancel');
                $router->post('approvalStatus/{id}', 'ReservationController@approvalStatus')->name('reservation.approvalStatus');
            });

            Route::prefix('rateplan')->group(function ($router) {
                $router->get('list', 'RatePlanController@list')->name('rateplan.list');
                $router->get('options', 'RatePlanController@options')->name('rateplan.options');
                $router->get('detail/{id}', 'RatePlanController@detail')->name('rateplan.detail');
                $router->post('update', 'RatePlanController@update')->name('rateplan.update');
                $router->post('create', 'RatePlanController@create')->name('rateplan.create');
                $router->post('delete/{id}', 'RatePlanController@delete')->name('rateplan.delete');
                $router->get('search', 'RatePlanController@search')->name('rateplan.search');
                $router->post('check/{id}', 'RatePlanController@check')->name('rateplan.check');
            });

            Route::prefix('component')->group(function ($router) {
                $router->get('list', 'ComponentController@list')->name('component.list');
                $router->get('detail/{id}', 'ComponentController@detail')->name('component.detail');
                $router->post('update', 'ComponentController@update')->name('component.update');
                $router->post('create', 'ComponentController@create')->name('component.create');
                $router->post('delete/{id}', 'ComponentController@delete')->name('component.delete');
                $router->get('search', 'ComponentController@search')->name('component.search');
                $router->post('check/{id}', 'ComponentController@check')->name('component.check');
                $router->get('getHotels', 'ComponentController@getHotels')->name('component.getHotels');
            });

            Route::prefix('image')->group(function ($router) {
                $router->get('list', 'ImageController@list')->name('image.list');
                $router->post('update', 'ImageController@update')->name('image.update');
                $router->post('delete/{id}', 'ImageController@delete')->name('image.delete');
                $router->get('search', 'ImageController@search')->name('image.search');
                $router->post('upload', 'ImageController@upload')->name('image.upload');
            });

            Route::prefix('profile')->group(function ($router) {
                $router->get('account', 'ProfileController@account')->name('profile.account');
                $router->post('changepwd', 'ProfileController@changepwd')->name('profile.changepwd');
                $router->post('update', 'ProfileController@update')->name('profile.update');
            });

            Route::prefix('layout')->group(function ($router) {
                $router->get('list', 'LayoutController@list')->name('layout.list');
                $router->get('detail/{id}', 'LayoutController@detail')->name('layout.detail');
                $router->post('update', 'LayoutController@update')->name('layout.update');
                $router->post('create', 'LayoutController@create')->name('layout.create');
                $router->post('delete/{id}', 'LayoutController@delete')->name('layout.delete');
                $router->get('initdata', 'LayoutController@initData')->name('layout.initdata');
                $router->get('search', 'LayoutController@search')->name('layout.search');
                $router->post('check/{id}', 'LayoutController@check')->name('layout.check');
            });

            Route::prefix('originalLp')->group(function ($router) {
                $router->get('list', 'OriginalLpController@list')->name('originalLp.list');
                $router->get('detail/{id}', 'OriginalLpController@detail')->name('originalLp.detail');
                $router->post('update', 'OriginalLpController@update')->name('originalLp.update');
                $router->post('create', 'OriginalLpController@create')->name('originalLp.create');
                $router->post('delete/{id}', 'OriginalLpController@delete')->name('originalLp.delete');
                $router->get('search', 'OriginalLpController@search')->name('originalLp.search');
            });

            Route::prefix('editor')->group(function ($router) {
                $router->get('components', 'EditorController@components')->name('editor.components');
                $router->get('component/{id}/layouts', 'EditorController@layouts')->name('editor.layouts');
                $router->get('layout/{id}', 'EditorController@layout')->name('editor.layout');
                $router->get('lp/{id}', 'EditorController@lp')->name('editor.lp');
            });

            Route::prefix('category')->group(function ($router) {
                $router->get('list', 'LpCategoryController@list')->name('category.list');
                $router->get('options', 'LpCategoryController@options')->name('category.options');
                $router->get('detail/{id}', 'LpCategoryController@detail')->name('category.detail');
                $router->post('update', 'LpCategoryController@update')->name('category.update');
                $router->post('create', 'LpCategoryController@create')->name('category.create');
                $router->post('delete/{id}', 'LpCategoryController@delete')->name('category.delete');
                $router->get('search', 'LpCategoryController@search')->name('category.search');
            });

            Route::prefix('mail-template')->group(function ($router) {
                $router->get('detail/{type}', 'MailTemplateController@detail')->name('mail-template.detail');
                $router->post('save', 'MailTemplateController@save')->name('mail-template.save');
            });
        });
    });

    // client routes
    Route::group(['prefix' => 'client', 'namespace' => 'Client'], function ($router) {

        Route::prefix('roomstock')->group(function ($router) {
            $router->get('list', 'RoomStockController@list')->name('roomStock.list');
        });

        Route::prefix('roomrate')->group(function ($router) {
            $router->get('list', 'RoomRateController@list')->name('roomRate.list');
        });

        $router->post('login', 'Auth\LoginController@login')->name('login');
        $router->post('forgot/send', 'Auth\ForgotController@send')->name('forgot.send');
        $router->post('forgot/verify', 'Auth\ForgotController@verify')->name('forgot.verify');
        $router->post('forgot/change', 'Auth\ForgotController@change')->name('forgot.change');

        $router->group(['middleware' => 'client_api'], function ($router) {
            Route::prefix('profile')->group(function ($router) {
                $router->get('account', 'ProfileController@account')->name('profile.account');
                $router->post('changepwd', 'ProfileController@changepwd')->name('profile.changepwd');
                $router->post('update', 'ProfileController@update')->name('profile.update');
            });

            Route::prefix('hotel')->group(function ($router) {
                $router->get('list', 'HotelController@list')->name('hotel.list');

                $router->get('detail/{id}', 'HotelController@detail')->name('hotel.detail');
                $router->post('save', 'HotelController@save')->name('hotel.save');
                $router->post('delete/{id}', 'HotelController@delete')->name('hotel.delete');

                $router->get('{id}/home/list', 'HomeController@list')->name('home.list');
                $router->get('{id}/home/init', 'HomeController@init')->name('home.init');
                $router->get('{id}/home/pms', 'HomeController@pms')->name('home.pms');

                $router->get('{id}/reservation/list', 'ReservationController@list')->name('reservation.list');

                $router->get('{id}/form/list', 'FormController@list')->name('form.list');
                $router->get('{id}/form/init', 'FormController@init')->name('form.init');
                $router->get('{id}/form/options', 'FormController@options')->name('form.options');

                $router->get('{id}/formItem/list', 'FormItemController@list')->name('formItem.list');
                $router->get('{id}/formItem/init', 'FormItemController@init')->name('formItem.init');

                $router->get('{id}/plan/list', 'PlanController@list')->name('plan.list');
                $router->get('{id}/plan/init', 'PlanController@init')->name('plan.init');

                $router->get('{id}/cancelPolicy/list', 'CancelPolicyController@list')->name('cancelPolicy.list');
                $router->get('{id}/cancelPolicy/init', 'CancelPolicyController@init')->name('cancelPolicy.init');

                $router->get('{id}/hotelHard/init', 'HotelHardController@init')->name('hotelHard.init');

                $router->get('{id}/room/list', 'HotelRoomTypeController@list')->name('room.list');
                $router->get('{id}/room/init', 'HotelRoomTypeController@init')->name('room.init');

                $router->get('{id}/lp/list', 'LpController@list')->name('lp.list');
                $router->get('{id}/originalLp/list', 'OriginalLpController@list')->name('originalLp.list');

                $router->get('{hotelId}/reservation_schedule/room_type', 'ReservationScheduleController@get_room_type')->name('schedule.get_room_type');
                $router->get('{hotelId}/reservation_schedule/list', 'ReservationScheduleController@list')->name('schedule.list');
                $router->post('{hotelId}/reservation_schedule', 'ReservationScheduleController@create')->name('schedule.create');
                $router->put('{hotelId}/reservation_schedule/{id}', 'ReservationScheduleController@edit')->name('schedule.edit');
                $router->get('{hotelId}/reservation_schedule/detail/{id}', 'ReservationScheduleController@detail')->name('schedule.detail');
                $router->delete('{hotelId}/reservation_schedule/delete/{id}', 'ReservationScheduleController@delete')->name('schedule.delete');
                $router->delete('{hotelId}/reservation_schedule/delete_group/{id}', 'ReservationScheduleController@delete_group')->name('schedule.delete_group');
                $router->post('{hotelId}/reservation_schedule/close', 'ReservationScheduleController@close')->name('schedule.close');
                $router->post('{hotelId}/reservation_schedule/room_num', 'ReservationScheduleController@roomNum')->name('schedule.room_num');
            });

            Route::prefix('reservation')->group(function ($router) {
                $router->get('csvdownload', 'ReservationController@csvDownload')->name('reservation.csvdownload');
                $router->get('search', 'ReservationController@search')->name('reservation.search');
                $router->get('detail/{id}', 'ReservationController@detail')->name('reservation.detail');
                $router->post('changeStatus/{id}', 'ReservationController@changeStatus')->name('reservation.changeStatus');
                $router->post('approvalStatus/{id}', 'ReservationController@approvalStatus')->name('reservation.approvalStatus');
                $router->post('check/{id}', 'ReservationController@check')->name('reservation.check');
                $router->post('checkFreeCancel/{id}', 'ReservationController@checkFreeCancel')->name('reservation.checkFreeCancel');
            });

            Route::prefix('form')->group(function ($router) {
                $router->get('search', 'FormController@search')->name('form.search');
                $router->get('detail/{id}', 'FormController@detail')->name('form.detail');
                $router->post('save', 'FormController@save')->name('form.save');
                $router->post('delete/{id}', 'FormController@delete')->name('form.delete');
                $router->post('check/{id}', 'FormController@check')->name('form.check');
            });

            Route::prefix('formItem')->group(function ($router) {
                $router->get('search', 'FormItemController@search')->name('formItem.search');
                $router->get('detail/{id}', 'FormItemController@detail')->name('formItem.detail');
                $router->post('save', 'FormItemController@save')->name('formItem.save');
                $router->post('delete/{id}', 'FormItemController@delete')->name('formItem.delete');
            });

            Route::prefix('plan')->group(function ($router) {
                $router->get('search', 'PlanController@search')->name('plan.search');
                $router->get('detail/{id}', 'PlanController@detail')->name('plan.detail');
                $router->post('save', 'PlanController@save')->name('plan.save');
                $router->post('delete/{id}', 'PlanController@delete')->name('plan.delete');
                $router->post('check/{id}', 'PlanController@check')->name('plan.check');
                $router->post('sort', 'PlanController@sort')->name('plan.sort');
                $router->post('previewValidation', 'PlanController@previewValidation')->name('plan.previewValidation');
            });

            Route::prefix('cancelPolicy')->group(function ($router) {
                $router->get('search', 'CancelPolicyController@search')->name('cancelPolicy.search');
                $router->get('detail/{id}', 'CancelPolicyController@detail')->name('cancelPolicy.detail');
                $router->post('save', 'CancelPolicyController@save')->name('cancelPolicy.save');
                $router->post('delete/{id}', 'CancelPolicyController@delete')->name('cancelPolicy.delete');
                $router->post('check/{id}', 'CancelPolicyController@check')->name('cancelPolicy.check');
            });

            Route::prefix('hotelHard')->group(function ($router) {
                $router->post('save', 'HotelHardController@save')->name('hotelHard.save');
            });

            Route::prefix('room')->group(function ($router) {
                $router->get('search', 'HotelRoomTypeController@search')->name('room.search');
                $router->get('detail/{id}', 'HotelRoomTypeController@detail')->name('room.detail');
                $router->post('save', 'HotelRoomTypeController@save')->name('room.save');
                $router->post('delete/{id}', 'HotelRoomTypeController@delete')->name('room.delete');
                $router->post('check/{id}', 'HotelRoomTypeController@check')->name('room.check');
                $router->post('sort', 'HotelRoomTypeController@sort')->name('room.sort');
            });

            Route::prefix('image')->group(function ($router) {
                $router->get('list', 'ImageController@list')->name('image.list');
                $router->post('update', 'ImageController@update')->name('image.update');
                $router->post('delete/{id}', 'ImageController@delete')->name('image.delete');
                $router->get('search', 'ImageController@search')->name('image.search');
                $router->post('upload/{id}', 'ImageController@upload')->name('image.upload');
            });

            Route::prefix('originalLp')->group(function ($router) {
                $router->get('search', 'OriginalLpController@search')->name('originalLp.search');
                $router->get('layouts/{id}', 'OriginalLpController@layouts')->name('originalLp.layouts');
            });

            Route::prefix('lp')->group(function ($router) {
                $router->get('search', 'LpController@search')->name('lp.search');
                $router->post('delete/{id}', 'LpController@delete')->name('lp.delete');
                $router->get('layouts/{id}', 'LpController@layouts')->name('lp.layouts');
                $router->post('update', 'LpController@update')->name('lp.update');
                $router->post('create', 'LpController@create')->name('lp.create');
            });

            Route::prefix('editor')->group(function ($router) {
                $router->get('components', 'EditorController@components')->name('editor.components');
                $router->get('component/{id}/layouts', 'EditorController@layouts')->name('editor.layouts');
                $router->get('layout/{id}', 'EditorController@layout')->name('editor.layout');
                $router->get('lp/{id}', 'EditorController@lp')->name('editor.lp');
                $router->get('originalLp/{id}', 'EditorController@originalLp')->name('editor.originalLp');
            });

            Route::prefix('category')->group(function ($router) {
                $router->get('options', 'LpCategoryController@options')->name('category.options');
            });
        });
    });

    // pms routes
    Route::group(['prefix' => 'pms', 'namespace' => 'Pms'], function ($router) {
        
        $router->group(['middleware' => 'pms_api'], function ($router) {
            $router->post('signed-route', 'GenerateSignedRouteController@generateSignedUrl');
            $router->post('signed-route/other/search/render', 'GenerateSignedRouteController@otherAdminSearchRender');
            $router->get('login', 'Auth\LoginController@login')->name('login');
            $router->post('hotel', 'HotelController@save')->name('hotel');
        });
    });
});
