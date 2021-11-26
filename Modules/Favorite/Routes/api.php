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

Route::middleware(['auth:api', 'scopes:apps','log_activities'])->prefix('/favorite')->group(function () {
    Route::any('/', 'ApiFavoriteController@index');
    Route::any('list', 'ApiFavoriteController@list');
    Route::post('create', 'ApiFavoriteController@store');
    Route::post('delete', 'ApiFavoriteController@destroy');
});

Route::middleware(['auth:api', 'scopes:apps','log_activities'])->prefix('favorite-hs')->group(function () {
    Route::post('create', 'ApiFavoriteController@storeFavoriteHS');
    Route::post('delete', 'ApiFavoriteController@destroyFavoriteHS');
});

Route::middleware(['auth:api', 'scopes:web-apps','log_activities'])->prefix('webapp/favorite-hs')->group(function () {
    Route::post('create', 'ApiFavoriteController@storeFavoriteHS');
    Route::post('delete', 'ApiFavoriteController@destroyFavoriteHS');
});