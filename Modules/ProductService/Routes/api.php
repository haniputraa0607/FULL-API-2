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

Route::group([ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be'], 'prefix' => 'product-service'], function () {
    Route::any('/', 'ApiProductServiceController@index');
    Route::get('product-use/list', 'ApiProductServiceController@productUseList');
    Route::post('product-use/update', 'ApiProductServiceController@productUseUpdate');
});

Route::group(['middleware' => ['log_activities','auth:api', 'user_agent', 'scopes:apps'], 'prefix' => 'home-service/product'], function()
{
    Route::get('list', 'ApiProductServiceController@homeServiceListProduct');
    Route::post('detail-service', 'ApiProductServiceController@homeServiceDetailProductService');
    Route::post('available-datetime', 'ApiProductServiceController@availableDateTime');
    Route::post('available-hs/favorite', 'ApiProductServiceController@homeServiceAvailableHsFavorite');
});

Route::group(['middleware' => ['log_activities','auth:api', 'user_agent', 'scopes:apps'], 'prefix' => 'home-service'], function()
{
    Route::get('available-datetime', 'ApiProductServiceController@availableDateTime');
});