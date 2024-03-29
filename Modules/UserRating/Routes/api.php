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

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:apps,web-apps'], 'prefix' => 'user-rating'], function () {
    Route::post('create', 'ApiUserRatingController@store');
    Route::post('get-detail', 'ApiUserRatingController@getDetail');
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:apps,web-apps'], 'prefix' => 'outlet-service/user-rating'], function () {
    Route::post('create', 'ApiUserRatingController@store');
    Route::post('get-detail', 'ApiUserRatingController@getDetail');
    Route::post('get-list', 'ApiUserRatingController@getList');
    Route::post('get-rated', 'ApiUserRatingController@getRated');
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'user-rating'], function () {
    Route::post('/', ['middleware' => 'feature_control:356', 'uses' => 'ApiUserRatingController@index']);
    Route::post('detail', ['middleware' => 'feature_control:356', 'uses' => 'ApiUserRatingController@show']);
    Route::post('delete', ['middleware' => 'feature_control:357', 'uses' => 'ApiUserRatingController@destroy']);
    Route::post('report', ['middleware' => 'feature_control:356', 'uses' => 'ApiUserRatingController@report']);
    Route::post('report/outlet', ['middleware' => 'feature_control:356', 'uses' => 'ApiUserRatingController@reportOutlet']);
    Route::post('report/hairstylist', ['middleware' => 'feature_control:356', 'uses' => 'ApiUserRatingController@reportHairstylist']);
    Route::group(['prefix'=>'option'],function(){
    	Route::get('/',['middleware' => 'feature_control:358', 'uses' => 'ApiRatingOptionController@index']);
    	Route::post('update',['middleware' => 'feature_control:360', 'uses' => 'ApiRatingOptionController@update']);
    });
});
