<?php

Route::group(['middleware' => ['auth:api-be'], 'prefix' => 'api/brand', 'namespace' => 'Modules\Brand\Http\Controllers'], function () {
    Route::any('/', 'ApiBrandController@index');
    Route::any('list', 'ApiBrandController@listBrand');
    Route::post('store', 'ApiBrandController@store');
    Route::post('show', 'ApiBrandController@show');
    Route::post('reorder', 'ApiBrandController@reOrder');
    Route::any('inactive-image', 'ApiBrandController@inactiveImage');

    Route::post('delete', 'ApiBrandController@destroy');
    Route::group(['prefix' => 'delete'], function () {
        Route::post('outlet', 'ApiBrandController@destroyOutlet');
        Route::post('product', 'ApiBrandController@destroyProduct');
        Route::post('deals', 'ApiBrandController@destroyDeals');
    });
    Route::post('outlet/list', 'ApiBrandController@outletList');
    Route::post('product/list', 'ApiBrandController@productList');

    Route::post('switch_status', 'ApiBrandController@switchStatus');
    Route::post('switch_visibility', 'ApiBrandController@switchVisibility');

    Route::post('outlet/store', 'ApiBrandController@outletStore');
    Route::post('product/store', 'ApiBrandController@productStore');

    Route::post('sync', 'ApiSyncBrandController@syncBrand');
});
