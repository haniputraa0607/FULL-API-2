<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['log_activities', 'user_agent'], 'prefix' => 'recruitment'], function () {

    Route::group(['middleware' => ['auth_client', 'scopes:landing-page'], 'prefix' => 'hairstylist'], function () {
        Route::post('create', 'ApiHairStylistController@create');
    });

    Route::group(['middleware' => ['auth:api', 'scopes:be'], 'prefix' => 'hairstylist/be'], function () {
        Route::any('candidate/list', 'ApiHairStylistController@canditateList');
        Route::any('list', 'ApiHairStylistController@hsList');
        Route::post('detail', 'ApiHairStylistController@detail');
        Route::post('update', 'ApiHairStylistController@update');
        Route::post('update-status', 'ApiHairStylistController@updateStatus');
        Route::post('update-box', 'ApiHairStylistController@updateBox');
        Route::post('detail/document', 'ApiHairStylistController@detailDocument');
        Route::post('delete', 'ApiHairStylistController@delete');

    	Route::group(['prefix' => 'schedule'], function () {
        	Route::post('list', 'ApiHairStylistScheduleController@list');
        	Route::post('detail', 'ApiHairStylistScheduleController@detail');
        	Route::post('update', 'ApiHairStylistScheduleController@update');
        	Route::get('outlet', 'ApiHairStylistScheduleController@outlet');
        	Route::get('year-list', 'ApiHairStylistScheduleController@getScheduleYear');
    	});

    	Route::group(['prefix' => 'announcement'], function () {
        	Route::post('list', ['middleware' => 'feature_control:368,371', 'uses' =>'ApiAnnouncement@listAnnouncement']);
		    Route::post('detail', ['middleware' => 'feature_control:369', 'uses' =>'ApiAnnouncement@detailAnnouncement']);
		    Route::post('create', ['middleware' => 'feature_control:370', 'uses' =>'ApiAnnouncement@createAnnouncement']);
		    Route::post('delete', ['middleware' => 'feature_control:372', 'uses' =>'ApiAnnouncement@deleteAnnouncement']);
    	});
    	Route::group(['prefix' => 'group'], function () {
                    Route::any('/', ['middleware' => 'feature_control:393','uses' =>'ApiHairStylistGroupController@index']);
                    Route::post('create', ['middleware' => 'feature_control:394','uses' =>'ApiHairStylistGroupController@create']);
		    Route::post('update', ['middleware' => 'feature_control:395','uses' =>'ApiHairStylistGroupController@update']);
		    Route::post('detail', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@detail']);
		    Route::post('create_commission', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@create_commission']);
		    Route::post('update_commission', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@update_commission']);
		    Route::post('detail_commission', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@detail_commission']);
		    Route::post('product', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@product']);
                    Route::post('hs', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@hs']);
		    Route::post('invite_hs', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@invite_hs']);
		    Route::post('commission', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@commission']);
		    Route::post('list_hs', ['middleware' => 'feature_control:396','uses' =>'ApiHairStylistGroupController@list_hs']);
    	});
    });
});

Route::group(['middleware' => ['log_activities', 'user_agent'], 'prefix' => 'mitra'], function () {
    Route::get('splash','ApiMitra@splash');

    Route::group(['middleware' => ['auth:mitra', 'scopes:mitra-apps']], function () {
    	Route::get('announcement','ApiMitra@announcementList');
    	Route::any('home','ApiMitra@home');

    	Route::group(['prefix' => 'schedule'], function () {
        	Route::post('/', 'ApiMitra@schedule');
        	Route::post('create', 'ApiMitra@createSchedule');
    	});

		Route::group(['prefix' => 'outlet-service'], function () {
        	Route::get('detail', 'ApiMitraOutletService@outletServiceDetail');
            Route::post('customer/history', 'ApiMitraOutletService@customerHistory');
        	Route::post('customer/queue', 'ApiMitraOutletService@customerQueue');
        	Route::post('customer/detail', 'ApiMitraOutletService@customerQueueDetail');
        	Route::post('start', 'ApiMitraOutletService@startService');
        	Route::post('stop', 'ApiMitraOutletService@stopService');
        	Route::post('extend', 'ApiMitraOutletService@extendService');
        	Route::post('complete', 'ApiMitraOutletService@completeService');
        	Route::get('box', 'ApiMitraOutletService@availableBox');
            Route::post('payment-cash/detail', 'ApiMitraOutletService@paymentCashDetail');
            Route::post('payment-cash/completed', 'ApiMitraOutletService@paymentCashCompleted');
            Route::post('box', 'ApiMitraOutletService@selectBox');
    	});

    	Route::group(['prefix' => 'shop-service'], function () {
        	Route::post('detail', 'ApiMitraShopService@detailShopService');
        	Route::post('confirm', 'ApiMitraShopService@confirmShopService');
        	Route::post('history', 'ApiMitraShopService@historyShopService');
    	});

    	Route::group(['prefix' => 'inbox'], function () {
        	Route::post('marked', 'ApiMitraInbox@markedInbox');
		    Route::post('unmark', 'ApiMitraInbox@unmarkInbox');
		    Route::post('unread', 'ApiMitraInbox@unread');
        	Route::post('/{mode?}', 'ApiMitraInbox@listInbox');
    	});

		Route::group(['prefix' => 'rating'], function () {
        	Route::get('summary', 'ApiMitra@ratingSummary');
        	Route::get('comment', 'ApiMitra@ratingComment');
    	});

        Route::group(['prefix' => 'home-service'], function () {
            Route::post('update-location', 'ApiMitraHomeService@setHSLocation');
            Route::post('update-status', 'ApiMitraHomeService@activeInactiveHomeService');
            Route::post('list-order', 'ApiMitraHomeService@listOrder');
            Route::post('detail-order', 'ApiMitraHomeService@detailOrder');
            Route::post('detail-service', 'ApiMitraHomeService@detailOrderService');
            Route::post('action', 'ApiMitraHomeService@action');
        });

        Route::get('balance-detail', 'ApiMitra@balanceDetail');
        Route::get('balance-history', 'ApiMitra@balanceHistory');
        Route::post('cash/transfer/detail', 'ApiMitra@transferCashDetail');
        Route::post('cash/transfer/create', 'ApiMitra@transferCashCreate');
        Route::post('cash/transfer/history', 'ApiMitra@transferCashHistory');

        //Cash flow for SPV
        Route::post('income/detail', 'ApiMitra@incomeDetail');
        Route::post('acceptance/detail', 'ApiMitra@acceptanceDetail');
        Route::post('acceptance/confirm', 'ApiMitra@acceptanceConfirm');

        Route::post('cash/outlet/income/create', 'ApiMitra@outletIncomeCreate');
        Route::post('cash/outlet/transfer', 'ApiMitra@cashOutletTransfer');
        Route::post('cash/outlet/history', 'ApiMitra@cashOutletHistory');

        Route::post('expense/outlet/create', 'ApiMitra@expenseOutletCreate');
        Route::post('expense/outlet/history', 'ApiMitra@expenseOutletHistory');
	});

    Route::group(['middleware' => ['auth:api'],'prefix' => 'request'], function () {
        Route::any('/', ['middleware'=>['feature_control:379','scopes:be'],'uses' => 'ApiRequestHairStylistController@index']);
        Route::any('/outlet', ['middleware'=>['feature_control:379','scopes:be'],'uses' => 'ApiRequestHairStylistController@listOutlet']);
        Route::post('/create', ['middleware'=>['feature_control:378','scopes:be'],'uses' => 'ApiRequestHairStylistController@store']);
        Route::post('/delete', ['middleware'=>['feature_control:378','scopes:be'],'uses' => 'ApiRequestHairStylistController@destroy']);
        Route::post('/detail', ['middleware'=>['feature_control:378','scopes:be'],'uses' => 'ApiRequestHairStylistController@show']);
        Route::post('/update', ['middleware'=>['feature_control:378','scopes:be'],'uses' => 'ApiRequestHairStylistController@update']);
        Route::post('/list-hs', ['middleware'=>['feature_control:378','scopes:be'],'uses' => 'ApiRequestHairStylistController@listHairStylistsOutlet']);
    });


});