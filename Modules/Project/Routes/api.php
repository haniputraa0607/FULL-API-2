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


   Route::group(['middleware' => ['auth:api','log_activities', 'user_agent'],'prefix' => 'project'], function() {
    //Project
    Route::any('/initProject/{partner}/{location}', ['middleware'=>['feature_control:403','scopes:be'], 'uses' => 'ApiProjectController@initProject']);
    Route::any('/list', ['middleware'=>['feature_control:402','scopes:be'], 'uses' => 'ApiProjectController@index']);
    Route::any('/cron', ['middleware'=>['feature_control:402','scopes:be'], 'uses' => 'ApiProjectController@cron']);
    Route::post('/create', ['middleware'=>['feature_control:403','scopes:be'], 'uses' => 'ApiProjectController@create']);
    Route::post('/excel', ['middleware'=>['feature_control:404','scopes:be'], 'uses' => 'ApiProjectController@excel']);
    Route::any('/detail', ['middleware'=>['feature_control:404','scopes:be'], 'uses' => 'ApiProjectController@detail']);
    Route::any('/update', ['middleware'=>['feature_control:405','scopes:be'], 'uses' => 'ApiProjectController@update']);
    Route::any('/delete', ['middleware'=>['feature_control:406','scopes:be'], 'uses' => 'ApiProjectController@destroy']);
    //Survey Location  
    Route::post('/create/survey_location', ['middleware'=>['feature_control:403','scopes:be'], 'uses' => 'ApiSurveyLocationController@create']);
    Route::post('/delete/survey_location', ['middleware'=>['feature_control:406','scopes:be'], 'uses' => 'ApiSurveyLocationController@destroy']);
    Route::post('/next/survey_location', ['middleware'=>['feature_control:405','scopes:be'], 'uses' => 'ApiSurveyLocationController@nextStep']);
    Route::post('/detail/survey_location', ['middleware'=>['feature_control:404','scopes:be'], 'uses' => 'ApiSurveyLocationController@detail']);
    //desain
     Route::post('/create/desain', ['middleware'=>['feature_control:403','scopes:be'], 'uses' => 'ApiDesainController@create']);
     Route::post('/delete/desain', ['middleware'=>['feature_control:406','scopes:be'], 'uses' => 'ApiDesainController@destroy']);
     Route::post('/list/desain', ['middleware'=>['feature_control:402','scopes:be'], 'uses' => 'ApiDesainController@index']);
     Route::post('/next/desain', ['middleware'=>['feature_control:405','scopes:be'], 'uses' => 'ApiDesainController@nextStep']);
    //contract
    Route::post('/create/contract', ['middleware'=>['feature_control:403','scopes:be'], 'uses' => 'ApiContractController@create']);
    Route::post('/delete/contract', ['middleware'=>['feature_control:406','scopes:be'], 'uses' => 'ApiContractController@destroy']);
    Route::post('/next/contract', ['middleware'=>['feature_control:405','scopes:be'], 'uses' => 'ApiContractController@nextStep']);
    Route::post('/detail/contract', ['middleware'=>['feature_control:404','scopes:be'], 'uses' => 'ApiContractController@detail']);
    Route::get('/detail/no_spk', ['middleware'=>['feature_control:404','scopes:be'], 'uses' => 'ApiContractController@no_spk']);
    Route::get('/detail/no_loi', ['middleware'=>['feature_control:404','scopes:be'], 'uses' => 'ApiContractController@no_loi']);
    
    //fitout
     Route::post('/create/fitout', ['middleware'=>['feature_control:403','scopes:be'], 'uses' => 'ApiFitOutController@create']);
     Route::post('/delete/fitout', ['middleware'=>['feature_control:406','scopes:be'], 'uses' => 'ApiFitOutController@destroy']);
     Route::post('/list/fitout', ['middleware'=>['feature_control:402','scopes:be'], 'uses' => 'ApiFitOutController@index']);
     Route::post('/next/fitout', ['middleware'=>['feature_control:405','scopes:be'], 'uses' => 'ApiFitOutController@nextStep']);
    
     //select
      Route::group(['prefix' => 'select-list'], function() {
            Route::get('lokasi', 'ApiSelectController@lokasi');
            Route::get('partner', 'ApiSelectController@partner');
        });
        
   //handover
    Route::post('/create/handover', ['middleware'=>['feature_control:403','scopes:be'], 'uses' => 'ApiHandoverController@create']);
    Route::post('/delete/handover', ['middleware'=>['feature_control:406','scopes:be'], 'uses' => 'ApiHandoverController@destroy']);
    Route::post('/next/handover', ['middleware'=>['feature_control:405','scopes:be'], 'uses' => 'ApiHandoverController@nextStep']);
    Route::post('/detail/handover', ['middleware'=>['feature_control:404','scopes:be'], 'uses' => 'ApiHandoverController@detail']);
   
});