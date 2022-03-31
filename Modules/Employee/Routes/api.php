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

Route::group([ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be'], 'prefix' => 'employee'], function () {
    Route::group(['prefix' => 'office-hours'], function(){
        Route::get('/', 'ApiEmployeeController@officeHoursList');
        Route::post('create', 'ApiEmployeeController@officeHoursCreate');
        Route::post('detail', 'ApiEmployeeController@officeHoursDetail');
        Route::post('update', 'ApiEmployeeController@officeHoursUpdate');
        Route::post('delete', 'ApiEmployeeController@officeHoursDelete');
    });

    Route::group(['prefix' => 'assign-office-hours'], function(){
        Route::get('/', 'ApiEmployeeController@assignOfficeHoursList');
        Route::post('create', 'ApiEmployeeController@assignOfficeHoursCreate');
        Route::post('detail', 'ApiEmployeeController@assignOfficeHoursDetail');
        Route::post('update', 'ApiEmployeeController@assignOfficeHoursUpdate');
        Route::post('delete', 'ApiEmployeeController@assignOfficeHoursDelete');
    });
});