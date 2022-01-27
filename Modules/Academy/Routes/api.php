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

Route::group([ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be'], 'prefix' => 'academy'], function () {
    Route::any('product', 'ApiProductAcademyController@index');
    Route::get('setting/installment', 'ApiAcademyController@settingInstallment');
    Route::post('setting/installment/save', 'ApiAcademyController@settingInstallmentSave');
    Route::get('setting/banner', 'ApiAcademyController@settingBanner');
    Route::post('setting/banner/save', 'ApiAcademyController@settingBannerSave');

    Route::post('transaction/user/schedule', 'ApiAcademyScheduleController@listUserAcademy');
    Route::post('transaction/user/schedule/detail', 'ApiAcademyScheduleController@detailScheduleUserAcademy');
    Route::post('transaction/user/schedule/detail/list', 'ApiAcademyScheduleController@listScheduleAcademy');
    Route::post('transaction/user/schedule/update', 'ApiAcademyScheduleController@updateScheduleUserAcademy');

    Route::post('transaction/user/schedule/day-off', 'ApiAcademyScheduleController@listDayOffUserAcademy');
    Route::post('transaction/user/schedule/day-off/action', 'ApiAcademyScheduleController@actionDayOffUserAcademy');
});

Route::group([ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:apps'], 'prefix' => 'academy'], function () {
    Route::any('outlet/nearme', 'ApiAcademyController@getListNearOutlet');
    Route::post('outlet/detail', 'ApiAcademyController@detailOutlet');
    Route::get('banner', 'ApiAcademyController@academyBanner');
    Route::post('product/list', 'ApiAcademyController@academyListProduct');
    Route::post('product/detail', 'ApiAcademyController@academyDetailProduct');

    Route::any('my-course', 'ApiAcademyController@listMyCourse');
    Route::post('my-course/detail', 'ApiAcademyController@detailMyCourse');
    Route::post('my-course/schedule', 'ApiAcademyController@scheduleMyCourse');
    Route::post('my-course/schedule/detail', 'ApiAcademyController@scheduleDetailMyCourse');
    Route::post('my-course/create/day-off', 'ApiAcademyController@createDayOff');
    Route::post('my-course/installment/detail', 'ApiAcademyController@installmentDetail');
    Route::post('my-course/installment/pay', 'ApiAcademyController@installmentPay');
});