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

Route::middleware('auth:api')->get('/businessdevelopment', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth:api','log_activities', 'user_agent'],'prefix' => 'partners'], function() {
    Route::any('/', ['middleware'=>['feature_control:338','scopes:be'],'uses' => 'ApiPartnersController@index']);
    Route::post('/create', ['middleware'=>'scopes:landing-page', 'uses' => 'ApiPartnersController@store']);
    Route::post('/delete', ['middleware'=>['feature_control:341','scopes:be'],'uses' => 'ApiPartnersController@destroy']);
    Route::post('/edit', ['middleware'=>['feature_control:339','scopes:be'],'uses' => 'ApiPartnersController@edit']);
    Route::post('/update', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@update']);
    Route::post('/create-follow-up', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@followUp']);
    Route::post('/pdf', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@pdf']);
    Route::group(['prefix' => '/locations'], function() {
        Route::any('/', ['middleware'=>['feature_control:342','scopes:be'],'uses' => 'ApiLocationsController@index']);
        Route::post('/create', ['middleware'=>'scopes:franchise-user','uses' => 'ApiLocationsController@store']);
        Route::post('/delete', ['middleware'=>['feature_control:345','scopes:be'],'uses' => 'ApiLocationsController@destroy']);
        Route::post('/edit', ['middleware'=>['feature_control:343','scopes:be'],'uses' => 'ApiLocationsController@edit']);
        Route::post('/update', ['middleware'=>['feature_control:344','scopes:be'],'uses' => 'ApiLocationsController@update']);
        Route::get('/brands', ['middleware'=>['feature_control:344','scopes:be'],'uses' => 'ApiLocationsController@brandsList']);
    });
    Route::group(['prefix' => '/bankaccount'], function() {
        Route::post('/detail', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiBankAccountsController@detail']);
        Route::post('/update', ['middleware'=>['feature_control:352','scopes:be'],'uses' => 'ApiBankAccountsController@update']);
    });
    Route::group(['prefix' => '/request-update'], function() {
        Route::any('/', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@listPartnersLogs']);
        Route::post('/delete', ['middleware'=>['feature_control:341','scopes:be'],'uses' => 'ApiPartnersController@deletePartnersLogs']);
        Route::post('/detail', ['middleware'=>['feature_control:339','scopes:be'],'uses' => 'ApiPartnersController@detailPartnersLogs']);
    });
    Route::group(['prefix' => '/confirmation-letter'], function() {
        Route::post('/create', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@createConfirLetter']);
    });
    Route::post('/form-survey', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@formSurvey']);
});

Route::group(['middleware' => ['auth:partners','log_activities','user_agent','scopes:partners'],'prefix' => 'partner'], function() {
    Route::get('/detailpartner', ['uses' => 'ApiPartnersController@detailByPartner']);
    Route::post('/updatepartner', ['uses' => 'ApiPartnersController@updateByPartner']);
    Route::post('/updatepassword', ['uses' => 'ApiPartnersController@passwordByPartner']);
    Route::post('/checkpassword', ['uses' => 'ApiPartnersController@checkPassword']);
    Route::get('/detailBank', ['uses' => 'ApiBankAccountsController@detailBankPartner']);
    Route::post('/updateBank', ['uses' => 'ApiBankAccountsController@updateBankPartner']);
    Route::any('/list-bank', ['uses' => 'ApiBankAccountsController@listBank']);
    Route::get('/status', ['uses' => 'ApiPartnersController@statusPartner']);
});