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

Route::group(['prefix' => 'partners'], function() {
    Route::any('/', ['uses' => 'ApiPartnersController@index']);
    Route::post('/delete', ['uses' => 'ApiPartnersController@destroy']);
    Route::post('/edit', ['uses' => 'ApiPartnersController@edit']);
    Route::post('/update', ['uses' => 'ApiPartnersController@update']);
    Route::group(['prefix' => '/locations'], function() {
        Route::any('/', ['uses' => 'ApiLocationsController@index']);
        Route::post('/delete', ['uses' => 'ApiLocationsController@destroy']);
        Route::post('/edit', ['uses' => 'ApiLocationsController@edit']);
        Route::post('/update', ['uses' => 'ApiLocationsController@update']);
    });
});
