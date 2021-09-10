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

    Route::group(['middleware' => ['auth_client', 'scopes:be'], 'prefix' => 'hairstylist/be'], function () {
        Route::any('candidate/list', 'ApiHairStylistController@canditateList');
        Route::any('hs/list', 'ApiHairStylistController@hsList');
    });
});