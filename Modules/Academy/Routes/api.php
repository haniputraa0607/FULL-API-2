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
});

Route::group([ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:apps'], 'prefix' => 'academy'], function () {
    Route::any('outlet/nearme', 'ApiAcademyController@getListNearOutlet');
    Route::post('outlet/detail', 'ApiAcademyController@detailOutlet');
    Route::get('banner', 'ApiAcademyController@academyBanner');
    Route::post('product/list', 'ApiAcademyController@academyListProduct');
    Route::post('product/detail', 'ApiAcademyController@academyDetailProduct');
});