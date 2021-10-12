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

    	Route::group(['prefix' => 'schedule'], function () {
        	Route::post('list', 'ApiHairStylistScheduleController@list');
        	Route::post('detail', 'ApiHairStylistScheduleController@detail');
        	Route::post('update', 'ApiHairStylistScheduleController@update');
        	Route::get('outlet', 'ApiHairStylistScheduleController@outlet');
        	Route::get('year-list', 'ApiHairStylistScheduleController@getScheduleYear');
    	});
    });
});

Route::group(['middleware' => ['log_activities', 'user_agent'], 'prefix' => 'mitra'], function () {
    Route::get('splash','ApiMitra@splash');

    Route::group(['middleware' => ['auth:mitra', 'scopes:mitra-apps']], function () {
    	Route::get('announcement','ApiMitra@announcementList');
    	Route::get('home','ApiMitra@home');

    	Route::group(['prefix' => 'schedule'], function () {
        	Route::post('/', 'ApiMitra@schedule');
        	Route::post('create', 'ApiMitra@createSchedule');
    	});

		Route::group(['prefix' => 'outlet-service'], function () {
        	Route::post('customer/queue', 'ApiMitraOutletService@customerQueue');
        	Route::post('customer/detail', 'ApiMitraOutletService@customerQueueDetail');
        	Route::post('start', 'ApiMitraOutletService@startService');
        	Route::post('stop', 'ApiMitraOutletService@stopService');
        	Route::post('extend', 'ApiMitraOutletService@extendService');
        	Route::post('complete', 'ApiMitraOutletService@completeService');
        	Route::get('box', 'ApiMitraOutletService@availableBox');
            Route::post('payment-cash/detail', 'ApiMitraOutletService@paymentCashDetail');
            Route::post('payment-cash/completed', 'ApiMitraOutletService@paymentCashCompleted');
    	});

    	Route::group(['prefix' => 'inbox'], function () {
        	Route::get('/{mode?}', 'ApiMitraInbox@listInbox');
        	Route::post('marked', 'ApiMitraInbox@markedInbox');
		    Route::post('unmark', 'ApiMitraInbox@unmarkInbox');
		    Route::post('unread', 'ApiMitraInbox@unread');
    	});

		Route::group(['prefix' => 'rating'], function () {
        	Route::get('summary', 'ApiMitra@ratingSummary');
        	Route::get('comment', 'ApiMitra@ratingComment');
    	});
	});
});