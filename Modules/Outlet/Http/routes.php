<?php
Route::group(['prefix' => 'api/outlet', 'middleware' => ['log_activities', 'auth:api', 'user_agent', 'scopes:apps'], 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::any('list', 'ApiOutletController@listOutlet');
    Route::any('list/simple', 'ApiOutletController@listOutletSimple');
    Route::any('list/ordernow', 'ApiOutletController@listOutletOrderNow');
    Route::any('list/gofood', 'ApiOutletGofoodController@listOutletGofood');
    Route::any('filter', 'ApiOutletController@filter');
    Route::any('filter/gofood', 'ApiOutletController@filter');
    /* New API for filter outlet + product */
    Route::any('filter_product_outlet', 'ApiOutletController@filterProductOutlet');

    /*WEBVIEW*/
    Route::any('webview/{id}', 'ApiOutletWebview@detailWebview');
    Route::any('detail/mobile', 'ApiOutletWebview@detailOutlet');
    Route::any('webview/gofood/list', 'ApiOutletWebview@listOutletGofood');
    Route::any('webview/gofood/list/v2', 'ApiOutletWebview@listOutletGofood');

    Route::get('city', 'ApiOutletController@cityOutlet');
    // Route::any('filter', 'ApiOutletController@filter');
    Route::any('nearme/geolocation', 'ApiOutletController@nearMeGeolocation');
    Route::any('filter/geolocation', 'ApiOutletController@filterGeolocation');
    Route::any('sync', 'ApiSyncOutletController@sync');//SYNC

});

Route::group(['prefix' => 'api/outlet', 'middleware' => ['log_activities', 'auth_client'],'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::any('list/mobile', 'ApiOutletController@listOutlet');
    Route::any('/detail', 'ApiOutletController@detailTransaction');
    Route::any('filter/android', 'ApiOutletController@filter');
    Route::any('nearme', 'ApiOutletController@nearMe');
});

Route::group(['prefix' => 'api/outlet', 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be'], 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::any('be/list/outlet', ['middleware' => 'feature_control:24', 'uses' =>'ApiOutletController@outlet'])->name('outlet_be_list');
    Route::any('be/list', ['middleware' => 'feature_control:24', 'uses' =>'ApiOutletController@listOutlet'])->name('outlet_be');
    Route::any('be/list/product-detail', ['middleware' => 'feature_control:24', 'uses' =>'ApiOutletController@listOutletProductDetail']);
    Route::any('be/list/product-special-price', ['middleware' => 'feature_control:24', 'uses' =>'ApiOutletController@listOutletProductSpecialPrice']);
    Route::any('be/filter', ['middleware' => 'feature_control:24', 'uses' =>'ApiOutletController@filter']);
    Route::any('list/code', ['middleware' => 'feature_control:24', 'uses' =>'ApiOutletController@getAllCodeOutlet']);
    Route::any('ajax_handler','ApiOutletController@ajaxHandler');
    Route::post('different_price','ApiOutletController@differentPrice');
    Route::post('different_price/update','ApiOutletController@updateDifferentPrice');
    Route::any('be/list/simple', 'ApiOutletController@listOutletSimple');

    /* photo */
    Route::group(['prefix' => 'photo'], function() {
        Route::post('create', ['middleware' => 'feature_control:29', 'uses' =>'ApiOutletController@upload']);
        Route::post('update', ['middleware' => 'feature_control:30', 'uses' =>'ApiOutletController@updatePhoto']);
        Route::post('delete', ['middleware' => 'feature_control:30', 'uses' =>'ApiOutletController@deleteUpload']);
    });

    /* holiday */
    Route::group(['prefix' => 'holiday'], function() {
        Route::any('list', ['middleware' => 'feature_control:34', 'uses' =>'ApiOutletController@listHoliday']);
        Route::post('create', ['middleware' => 'feature_control:36', 'uses' =>'ApiOutletController@createHoliday']);
        Route::post('update', ['middleware' => 'feature_control:37', 'uses' =>'ApiOutletController@updateHoliday']);
        Route::post('delete', ['middleware' => 'feature_control:38', 'uses' =>'ApiOutletController@deleteHoliday']);
    });

    // admin outlet
    Route::group(['prefix' => 'admin'], function() {
        Route::post('create', ['middleware' => 'feature_control:40', 'uses' =>'ApiOutletController@createAdminOutlet']);
        Route::post('detail', ['middleware' => 'feature_control:39', 'uses' =>'ApiOutletController@detailAdminOutlet']);
        Route::post('update', ['middleware' => 'feature_control:41', 'uses' =>'ApiOutletController@updateAdminOutlet']);
        Route::post('delete', ['middleware' => 'feature_control:42', 'uses' =>'ApiOutletController@deleteAdminOutlet']);
    });

    // stock
    // admin outlet
    Route::group(['prefix' => 'stock'], function() {
        Route::post('stock-icount', ['middleware' => 'feature_control:447', 'uses' =>'ApiOutletController@getStockIcount']);
        Route::post('report', ['middleware' => 'feature_control:447', 'uses' =>'ApiOutletController@reportStock']);
        Route::post('adjust', ['middleware' => 'feature_control:447', 'uses' =>'ApiOutletController@adjustStock']);
        Route::post('refresh', ['middleware' => 'feature_control:447', 'uses' =>'ApiOutletController@refreshStock']);
    });
    Route::post('unit-conversion/detail', ['middleware' => 'feature_control:447', 'uses' =>'ApiOutletController@detailUnitConversion']);
    Route::post('stock-adjustment/detail', ['middleware' => 'feature_control:447', 'uses' =>'ApiOutletController@detailStockAdjustment']);
    Route::post('export-product-icount-log', ['middleware' => 'feature_control:447', 'uses' =>'ApiOutletController@exportProductIcount']);
    Route::post('refresh-product', 'ApiOutletController@refreshProduct');

    Route::post('import-brand', 'ApiOutletController@importBrand');
    Route::post('import-delivery', 'ApiOutletController@importDelivery');
    Route::any('delivery-outlet-ajax', 'ApiOutletController@deliveryOutletAjax');
    Route::post('delivery-outlet/bycode', 'ApiOutletController@deliveryOutletByCode');
    Route::post('delivery-outlet/update', 'ApiOutletController@deliveryOutletUpdate');
    Route::post('delivery-outlet/all/update', 'ApiOutletController@deliveryOutletAllUpdate');
    Route::get('list-delivery/count-outlet', 'ApiOutletController@listDeliveryWithCountOutlet');
    Route::post('create', ['middleware' => 'feature_control:26', 'uses' =>'ApiOutletController@create']);
    Route::post('update', ['middleware' => 'feature_control:27', 'uses' =>'ApiOutletController@update']);
    Route::post('batch-update', 'ApiOutletController@batchUpdate');
    Route::post('update/status', 'ApiOutletController@updateStatus');
    Route::post('update/pin', 'ApiOutletController@updatePin');
    Route::post('delete', 'ApiOutletController@delete');
    Route::post('export', 'ApiOutletController@export');
    Route::post('export-city', 'ApiOutletController@exportCity');
    Route::post('import', 'ApiOutletController@import');
    Route::post('max-order', 'ApiOutletController@listMaxOrder');
    Route::post('max-order/update', 'ApiOutletController@updateMaxOrder');
    Route::any('schedule/save', 'ApiOutletController@scheduleSave');
    Route::get('export-pin', ['middleware' => 'feature_control:261', 'uses' =>'ApiOutletController@exportPin']);
    Route::get('send-pin', ['middleware' => 'feature_control:261', 'uses' =>'ApiOutletController@sendPin']);
    Route::post('box/save', 'ApiOutletController@boxSave');
    Route::post('shift-time/save', 'ApiOutletController@shiftTimeSave');
    Route::get('list-convert', 'ApiOutletController@listOutletConvert');
    Route::post('convert-outlet', 'ApiOutletController@convertToOffice');

    /*user franchise*/
    Route::any('list/user-franchise', 'ApiOutletController@listUserFranchise');
    Route::any('detail/user-franchise', 'ApiOutletController@detailUserFranchise');
    Route::post('user-franchise/set-password-default', 'ApiOutletController@setPasswordDefaultUserFranchise');

    Route::post('schedule/restore', 'ApiOutletController@restoreSchedule');

    /*group filter*/
    Route::group(['prefix' => 'group-filter'], function() {
        Route::post('store', ['middleware' => 'feature_control:296', 'uses' =>'ApiOutletGroupFilterController@store']);
        Route::get('/', ['middleware' => 'feature_control:294,297,298', 'uses' =>'ApiOutletGroupFilterController@list']);
        Route::post('detail', ['middleware' => 'feature_control:295,297', 'uses' =>'ApiOutletGroupFilterController@detail']);
        Route::post('update', ['middleware' => 'feature_control:297', 'uses' =>'ApiOutletGroupFilterController@update']);
        Route::post('delete', ['middleware' => 'feature_control:297', 'uses' =>'ApiOutletGroupFilterController@destroy']);
    });
});

/*outlet service*/
Route::group(['prefix' => 'api/outlet-service', 'middleware' => ['scopes:apps', 'log_activities', 'auth:api', 'user_agent'], 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::any('nearme', 'ApiOutletServiseController@getListNearOutlet');
    Route::any('nearmeV2', 'ApiOutletServiseController@getListNearOutletV2');
    Route::any('search', 'ApiOutletServiseController@getListSearch');
    Route::post('detail', 'ApiOutletServiseController@detailOutlet');
});

Route::group(['prefix' => 'api/webapp/outlet-service', 'middleware' => ['scopes:web-apps', 'log_activities', 'auth_client', 'user_agent'], 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::post('detail', 'ApiOutletServiseController@detailOutlet');
});

Route::group(['prefix' => 'api/outlet-display', 'middleware' => ['scopes:outlet-display', 'auth_client'], 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::post('/queue', 'OutletDisplayController@queue');
    Route::post('/status', 'OutletDisplayController@status');
});

Route::group(['prefix' => 'api/pos-order/outlet-service', 'middleware' => ['scopes:pos-order', 'log_activities', 'auth_client', 'user_agent']], function()
{
    Route::group(['namespace' => 'Modules\Users\Http\Controllers'], function()
    {
        Route::post('phone/check', 'ApiUserV2@phoneCheck');
        Route::post('pin/request', 'ApiUserV2@pinRequest');
        Route::post('pin/check', 'ApiUser@checkPin')->middleware('decrypt_pin');
        Route::post('pin/verify', 'ApiUser@verifyPin')->middleware('decrypt_pin');

    });
    Route::group(['namespace' => 'Modules\Outlet\Http\Controllers'], function()
    {
        Route::post('/home', 'ApiPosOrderController@home');
        Route::post('/list', 'ApiPosOrderController@listQueue');
        Route::post('/check', 'ApiPosOrderController@checkTransaction');
        Route::post('/available-payment', 'ApiPosOrderController@availablePayment');
        Route::post('/new', 'ApiPosOrderController@newTransaction');
        Route::post('/confirm', 'ApiPosOrderController@confirmTransaction');
        Route::post('/done', 'ApiPosOrderController@doneTransaction');
        Route::post('/detail-transaction', 'ApiPosOrderController@detailTransaction');
        Route::post('/list-transaction', 'ApiPosOrderController@listTransaction');
        Route::post('/list-transactionV2', 'ApiPosOrderController@listTransactionV2');
        Route::post('/list-trans-product', 'ApiPosOrderController@listTrxProduct');

    });
    Route::group(['namespace' => 'Modules\PromoCampaign\Http\Controllers'], function()
    {
        Route::post('/list-promo', 'ApiPromoCampaign@onGoignPromoPosOrder');
        Route::post('/use-promo', 'ApiPromoCampaign@usePromoPosOrder');
        Route::post('/cancel-promo', 'ApiPromoCampaign@cancelPromoPosOrder');

    });

});

Route::group(['prefix' => 'api/pos-order/outlet-service', 'middleware' => ['scopes:pos-order', 'log_activities', 'auth:api', 'user_agent'], 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::post('/check-user', 'ApiPosOrderController@checkTransaction');
});