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

Route::group(['middleware' => ['auth:api','log_activities', 'user_agent'],'prefix' => 'chartofaccount'], function() {
   Route::get('/', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiChartOfAccountController@index']);
   Route::get('/sync', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiChartOfAccountController@sync']);
});