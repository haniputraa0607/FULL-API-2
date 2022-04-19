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
        Route::get('default', 'ApiEmployeeController@officeHoursDefault');
        Route::get('assign', 'ApiEmployeeController@officeHoursAssign');
        Route::post('assign', 'ApiEmployeeController@officeHoursAssign');
    });

    Route::group(['prefix' => 'announcement'], function(){
        Route::any('/', 'ApiEmployeeAnnouncementController@listAnnouncement');
        Route::post('create', 'ApiEmployeeAnnouncementController@createAnnouncement');
        Route::post('detail', 'ApiEmployeeAnnouncementController@detailAnnouncement');
        Route::post('delete', 'ApiEmployeeAnnouncementController@deleteAnnouncement');
    });
    Route::group(['prefix' => 'be/recruitment'], function(){
        Route::post('create', 'ApiBeEmployeeController@create');
    });
    Route::group(['prefix' => 'be/question'], function(){
        Route::post('category', 'ApiQuestionEmployeeController@category');
        Route::post('create', 'ApiQuestionEmployeeController@create');
    });
});

Route::group([ 'middleware' => ['log_activities', 'auth:api','auth_client','scopes:landing-page'], 'prefix' => 'employee'], function () {
    Route::group(['prefix' => 'recruitment'], function(){
        Route::post('create', 'ApiRegisterEmployeeController@create');
    });
});

Route::group([ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:employees'], 'prefix' => 'employee'], function () {
    Route::group(['prefix' => 'recruitment'], function(){
        Route::post('detail', 'ApiRegisterEmployeeController@detail');
        Route::post('update', 'ApiRegisterEmployeeController@update');
        Route::post('submit', 'ApiRegisterEmployeeController@submit');
        Route::post('submit', 'ApiRegisterEmployeeController@submit');
        Route::get('question', 'ApiQuestionEmployeeController@list');
    });
});

Route::group([ 'middleware' => ['auth:api','user_agent', 'scopes:employee-apps'], 'prefix' => 'employee'], function () {
    Route::get('announcement','ApiEmployeeAnnouncementController@announcementList');
});