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
    Route::any('/initProject/{partner}/{location}', ['middleware'=>'scopes:be', 'uses' => 'ApiProjectController@initProject']);
    Route::any('/list', ['middleware'=>'scopes:be', 'uses' => 'ApiProjectController@index']);
    Route::post('/create', ['middleware'=>'scopes:be', 'uses' => 'ApiProjectController@create']);
    Route::any('/detail', ['middleware'=>'scopes:be', 'uses' => 'ApiProjectController@detail']);
    Route::any('/delete', ['middleware'=>'scopes:be', 'uses' => 'ApiProjectController@destroy']);
    //Survey Location  
    Route::post('/create/survey_location', ['middleware'=>'scopes:be', 'uses' => 'ApiSurveyLocationController@create']);
    Route::post('/delete/survey_location', ['middleware'=>'scopes:be', 'uses' => 'ApiSurveyLocationController@destroy']);
    Route::post('/next/survey_location', ['middleware'=>'scopes:be', 'uses' => 'ApiSurveyLocationController@nextStep']);
    Route::post('/detail/survey_location', ['middleware'=>'scopes:be', 'uses' => 'ApiSurveyLocationController@detail']);
    //desain
     Route::post('/create/desain', ['middleware'=>'scopes:be', 'uses' => 'ApiDesainController@create']);
     Route::post('/delete/desain', ['middleware'=>'scopes:be', 'uses' => 'ApiDesainController@destroy']);
     Route::post('/list/desain', ['middleware'=>'scopes:be', 'uses' => 'ApiDesainController@index']);
     Route::post('/next/desain', ['middleware'=>'scopes:be', 'uses' => 'ApiDesainController@nextStep']);
    //contract
    Route::post('/create/contract', ['middleware'=>'scopes:be', 'uses' => 'ApiContractController@create']);
    Route::post('/delete/contract', ['middleware'=>'scopes:be', 'uses' => 'ApiContractController@destroy']);
    Route::post('/next/contract', ['middleware'=>'scopes:be', 'uses' => 'ApiContractController@nextStep']);
    Route::post('/detail/contract', ['middleware'=>'scopes:be', 'uses' => 'ApiContractController@detail']);
    //fitout
     Route::post('/create/fitout', ['middleware'=>'scopes:be', 'uses' => 'ApiFitOutController@create']);
     Route::post('/delete/fitout', ['middleware'=>'scopes:be', 'uses' => 'ApiFitOutController@destroy']);
     Route::post('/list/fitout', ['middleware'=>'scopes:be', 'uses' => 'ApiFitOutController@index']);
     Route::post('/next/fitout', ['middleware'=>'scopes:be', 'uses' => 'ApiFitOutController@nextStep']);
    
     //select
      Route::group(['prefix' => 'select-list'], function() {
            Route::get('lokasi', 'ApiSelectController@lokasi');
            Route::get('partner', 'ApiSelectController@partner');
        });
});