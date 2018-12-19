<?php
Route::group(['middleware' => 'auth:api', 'prefix' => 'api/shipment', 'namespace' => 'Modules\Shipment\Http\Controllers'], function()
{
    Route::post('list', 'ApiShipment@listShipment');
	Route::post('create', 'ApiShipment@create');
    Route::post('update', 'ApiShipment@update');
    Route::post('delete', 'ApiShipment@delete');
});