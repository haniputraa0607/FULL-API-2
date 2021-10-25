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

Route::group(['prefix' => 'partner'], function () {
    Route::group(['middleware' => ['auth_client', 'scopes:partners']], function () {
        Route::post('reset-password', 'ApiUserPartnerController@resetPassword');
    });
    Route::group(['middleware' => ['auth:partners', 'scopes:partners']], function () {
        Route::group(['prefix' => 'user'], function() {
            Route::post('update-first-pin', 'ApiUserPartnerController@updateFirstPin');
            Route::post('detail/for-login', 'ApiUserPartnerController@detail');
        });
        Route::group(['prefix' => 'promo'], function() {
            Route::post('detail/deals', 'ApiDeals@detail');
            Route::post('before/deals', 'ApiDeals@listDealBefore');
            Route::post('active/deals', 'ApiDeals@listDealActive');
            Route::post('outlet', 'ApiOutletController@outlet');
            Route::post('brand', 'ApiOutletController@brand');
            Route::post('before/promo-campaign', 'ApiPromoCampaign@listPromoCampaignBefore');
            Route::post('active/promo-campaign', 'ApiPromoCampaign@listPromoCampaignActive');
            Route::post('detail/promo-campaign', 'ApiPromoCampaign@detail');
            Route::post('before/subscription', 'ApiSubscriptionController@listSubscriptionBefore');  
            Route::post('active/subscription', 'ApiSubscriptionController@listSubscriptionActive');  
        });
    });
     Route::group(['middleware' => ['auth:api', 'scopes:be']], function () {
        Route::group(['prefix' => 'select-list'], function() {
            Route::post('lokasi', 'ApiOutletController@lokasi');
            Route::get('partner', 'ApiOutletController@partner');
        });
    });
});