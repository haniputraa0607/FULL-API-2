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
Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:apps'], 'prefix' => 'api/bd', 'namespace' => 'Modules\BusinessDevelopment\Http\Controllers'], function()
{
    Route::any('/', 'ApiTutorial@introHomeFrontend');

});