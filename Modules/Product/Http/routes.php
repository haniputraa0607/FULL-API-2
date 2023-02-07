<?php

Route::group(['prefix' => 'api/product','middleware' => ['log_activities','auth:api', 'scopes:apps'], 'namespace' => 'Modules\Product\Http\Controllers'], function()
{
    /* product */
    Route::post('search', 'ApiCategoryController@search');
    Route::any('list', 'ApiProductController@listProduct');
    
    Route::post('detail', 'ApiProductController@detail');
    Route::any('sync', 'ApiSyncProductController@sync');
    Route::get('next/{id}', 'ApiProductController@getNextID');

    /* category */
    Route::group(['prefix' => 'category'], function() {

    	Route::any('list', 'ApiCategoryController@listCategory');
    	Route::any('list/tree', 'ApiCategoryController@listCategoryTree');
    });
//	Route::group(['prefix' => 'discount'], function() {
//        Route::post('create', 'ApiDiskonProductController@create');
//        Route::post('update', 'ApiDiskonProductController@update');
//		Route::post('delete', 'ApiDiskonProductController@delete');
//	});
});

Route::group(['prefix' => 'api/product','middleware' => ['log_activities','auth:api', 'scopes:be'], 'namespace' => 'Modules\Product\Http\Controllers'], function()
{
    Route::any('be/setting_service', 'ApiProductController@setting_service');
    Route::any('be/list', 'ApiProductController@listProduct');
    Route::any('be/icount/list', 'ApiProductController@listProductIcount');
    Route::any('be/sync', 'ApiProductController@syncIcount');
    Route::any('be/list/icount', 'ApiProductController@item_icount');
    Route::any('be/list/image', 'ApiProductController@listProductImage');
    Route::any('be/list/image/detail', 'ApiProductController@listProductImageDetail');
    Route::any('be/imageOverride', 'ApiProductController@imageOverride');
    Route::post('category/assign', 'ApiProductController@categoryAssign');
    Route::post('price/update', 'ApiProductController@priceUpdate');
    Route::post('detail/update', 'ApiProductController@updateProductDetail');
    Route::post('detail/update/price', 'ApiProductController@updatePriceDetail');
    Route::post('create', 'ApiProductController@create');
    Route::post('update', 'ApiProductController@update');
    Route::post('update/allow_sync', 'ApiProductController@updateAllowSync');
    Route::post('update/visibility/global', 'ApiProductController@updateVisibility');
    Route::post('update/visibility', 'ApiProductController@visibility');
    Route::post('position/assign', 'ApiProductController@positionProductAssign');//product position
    Route::post('delete', 'ApiProductController@delete');
    Route::post('icount/delete', 'ApiProductController@deleteIcount');
    Route::post('icount/getUnit', 'ApiProductController@unitDetailIcount');
    Route::post('icount/saveUnit', 'ApiProductController@saveUnitDetailIcount');
    Route::post('icount/saveNewUnit', 'ApiProductController@saveNewUnit');
    Route::post('icount/report-stock', 'ApiProductController@reportStock');
    Route::post('import', 'ApiProductController@import');
    Route::get('list/price/{id_outlet}', 'ApiProductController@listProductPriceByOutlet');
    Route::get('list/product-detail/{id_outlet}', 'ApiProductController@listProductDetailByOutlet');
    Route::post('export', 'ApiProductController@export');
    Route::post('import', 'ApiProductController@import');
    Route::post('ajax-product-brand', 'ApiProductController@ajaxProductBrand');
    Route::post('product-brand', 'ApiProductController@getProductByBrand');
    Route::get('list/ajax', 'ApiProductController@listProductAjaxSimple');
    Route::any('be/commission', 'ApiProductController@commission');
    Route::any('be/commission/create', 'ApiProductController@commission_create');
    Route::any('be/commission/delete', 'ApiProductController@deleteCommission');
    Route::any('be/commission/delete-product', 'ApiProductController@deleteProductCommission');
    Route::post('be/available-hs', 'ApiProductController@outletServiceAvailableHs');
    /* photo */
    Route::group(['prefix' => 'photo'], function() {
        Route::post('create', 'ApiProductController@uploadPhotoProduct');
        Route::post('update', 'ApiProductController@updatePhotoProduct');
        Route::post('createAjax', 'ApiProductController@uploadPhotoProductAjax');
        Route::post('overrideAjax', 'ApiProductController@overrideAjax');
        Route::post('delete', 'ApiProductController@deletePhotoProduct');
        Route::post('default', 'ApiProductController@photoDefault');
    });

    /* product modifier */
    Route::group(['prefix' => 'modifier'], function() {
        Route::any('/', 'ApiProductModifierController@index');
        Route::get('type', 'ApiProductModifierController@listType');
        Route::post('detail', 'ApiProductModifierController@show');
        Route::post('create', 'ApiProductModifierController@store');
        Route::post('update', 'ApiProductModifierController@update');
        Route::post('delete', 'ApiProductModifierController@destroy');
        Route::post('list-price', 'ApiProductModifierController@listPrice');
        Route::post('update-price', 'ApiProductModifierController@updatePrice');
        Route::post('list-detail', 'ApiProductModifierController@listDetail');
        Route::post('update-detail', 'ApiProductModifierController@updateDetail');
        Route::post('position-assign', 'ApiProductModifierController@positionAssign');
        Route::get('inventory-brand', 'ApiProductModifierController@inventoryBrand');
        Route::post('inventory-brand', 'ApiProductModifierController@inventoryBrandUpdate');
    });

    /* product modifier group */
    Route::group(['prefix' => 'modifier-group'], function() {
        Route::any('/', 'ApiProductModifierGroupController@index');
        Route::post('create', 'ApiProductModifierGroupController@store');
        Route::post('update', 'ApiProductModifierGroupController@update');
        Route::post('delete', 'ApiProductModifierGroupController@destroy');
        Route::post('list-price', 'ApiProductModifierGroupController@listPrice');
        Route::post('list-detail', 'ApiProductModifierGroupController@listDetail');
        Route::get('export', 'ApiProductModifierGroupController@export');
        Route::post('import', 'ApiProductModifierGroupController@import');
        Route::get('export-price', 'ApiProductModifierGroupController@exportPrice');
        Route::post('import-price', 'ApiProductModifierGroupController@importPrice');
        Route::post('position-assign', 'ApiProductModifierGroupController@positionAssign');
        Route::get('inventory-brand', 'ApiProductModifierGroupController@inventoryBrand');
        Route::post('inventory-brand', 'ApiProductModifierGroupController@inventoryBrandUpdate');
    });

    Route::group(['prefix' => 'category'], function() {
        Route::any('be/list', 'ApiCategoryController@listCategory');
        Route::post('position/assign', 'ApiCategoryController@positionCategoryAssign');
        Route::get('all', 'ApiCategoryController@getAllCategory');
        Route::post('create', 'ApiCategoryController@create');
        Route::post('update', 'ApiCategoryController@update');
        Route::post('delete', 'ApiCategoryController@delete');
    });

    Route::group(['prefix' => 'promo-category'], function() {
        Route::any('/', 'ApiPromoCategoryController@index')->middleware(['feature_control:236']);
        Route::post('assign', 'ApiPromoCategoryController@assign')->middleware(['feature_control:239']);
        Route::post('reorder', 'ApiPromoCategoryController@reorder')->middleware(['feature_control:239']);
        Route::post('create', 'ApiPromoCategoryController@store')->middleware(['feature_control:238']);
        Route::post('show', 'ApiPromoCategoryController@show')->middleware(['feature_control:237']);
        Route::post('update', 'ApiPromoCategoryController@update')->middleware(['feature_control:239']);
        Route::post('delete', 'ApiPromoCategoryController@destroy')->middleware(['feature_control:240']);
    });

    /* PRICES */
    Route::post('prices', 'ApiProductController@productPrices');
    Route::post('prices/all-product', 'ApiProductController@allProductPrices');
    Route::post('outlet-detail', 'ApiProductController@productDetail');
    Route::post('outlet-detail/all-product', 'ApiProductController@allProductDetail');

    /* tag */
    Route::group(['prefix' => 'tag'], function() {
        Route::any('list', 'ApiTagController@list');
        Route::post('create', 'ApiTagController@create');
        Route::post('update', 'ApiTagController@update');
        Route::post('delete', 'ApiTagController@delete');
    });

    /* product tag */
    Route::group(['prefix' => 'product-tag'], function() {
        Route::post('create', 'ApiTagController@createProductTag');
        Route::post('delete', 'ApiTagController@deleteProductTag');
    });

    /* tag */
    Route::group(['prefix' => 'product-group'], function() {
        Route::any('list', 'ApiProductGroupController@list')->middleware(['feature_control:385']);
        Route::get('active-list', 'ApiProductGroupController@activeList')->middleware(['feature_control:385']);
        Route::post('create', 'ApiProductGroupController@create')->middleware(['feature_control:384']);
        Route::post('update', 'ApiProductGroupController@update')->middleware(['feature_control:387']);
        Route::post('delete', 'ApiProductGroupController@delete')->middleware(['feature_control:388']);
        Route::any('detail/{id_product_group}', 'ApiProductGroupController@detail')->middleware(['feature_control:386']);
        Route::post('product-list', 'ApiProductGroupController@productList');
        Route::post('add-product', 'ApiProductGroupController@addProduct');
        Route::post('update-product', 'ApiProductGroupController@updateProduct');
        Route::post('remove-product', 'ApiProductGroupController@removeProduct');

	    Route::group(['prefix' => 'featured'], function()
	    {
	        Route::get('list', 'ApiProductGroupController@featuredList');
	        Route::post('create', 'ApiProductGroupController@featuredCreate');
	        Route::post('update', 'ApiProductGroupController@featuredUpdate');
	        Route::post('reorder', 'ApiProductGroupController@featuredReorder');
	        Route::post('delete', 'ApiProductGroupController@featuredDestroy');
	    });
    });

    Route::group(['prefix' => 'pivot'], function() {
        Route::post('/', 'ApiProductProductIcountController@index');
        Route::post('store', 'ApiProductProductIcountController@store');
        Route::post('update', 'ApiProductProductIcountController@update');
    });

});

Route::group(['prefix' => 'api/outlet-service/product','middleware' => ['log_activities','auth:api', 'scopes:apps'], 'namespace' => 'Modules\Product\Http\Controllers'], function()
{
    Route::post('list', 'ApiProductController@outletServiceListProduct');
    Route::post('detail', 'ApiProductController@detail');
    Route::post('detail-service', 'ApiProductController@outletServiceDetailProductService');
    Route::post('available-hs', 'ApiProductController@outletServiceAvailableHs');
    Route::post('available-hsV2', 'ApiProductController@outletServiceAvailableHsV2');
});

Route::group(['prefix' => 'api/webapp/outlet-service/product','middleware' => ['log_activities','auth_client', 'scopes:web-apps'], 'namespace' => 'Modules\Product\Http\Controllers'], function()
{
    Route::post('list', 'ApiProductController@outletServiceListProduct');
    Route::post('detail-service', 'ApiProductController@outletServiceDetailProductService');
    Route::post('available-hs', 'ApiProductController@outletServiceAvailableHs');
    Route::post('detail', 'ApiProductController@detail');
});

Route::group(['prefix' => 'api/shop/product','middleware' => ['log_activities','auth:api', 'scopes:apps'], 'namespace' => 'Modules\Product\Http\Controllers'], function()
{
    Route::post('list', 'ApiProductController@shopListProduct');
    Route::post('detail', 'ApiProductController@shopDetailProduct');
});

Route::group(['prefix' => 'api/req-product','middleware' => ['log_activities','auth:api', 'scopes:be'], 'namespace' => 'Modules\Product\Http\Controllers'], function(){
    Route::post('create', 'ApiRequestProductController@create');
    Route::post('delete', 'ApiRequestProductController@destroy');
    Route::post('detail', 'ApiRequestProductController@detail');
    Route::post('update', 'ApiRequestProductController@update');
    Route::post('/', 'ApiRequestProductController@index');
    Route::any('all', 'ApiRequestProductController@all');
    Route::any('list-catalog', 'ApiRequestProductController@listCatalog');
    Route::group(['prefix' => 'asset'], function() {
        Route::post('delete', 'ApiRequestProductController@destroyAsset');
        Route::post('detail', 'ApiRequestProductController@detailAsset');
        Route::post('update', 'ApiRequestProductController@updateAsset');

    });
});

Route::group(['prefix' => 'api/icount/req-product', 'namespace' => 'Modules\Product\Http\Controllers'], function() {
    Route::post('/callback','ApiRequestProductController@callbackRequest')->middleware('auth_pos2:PurchaseRequestID,status');
});

Route::group(['prefix' => 'api/dev-product','middleware' => ['log_activities','auth:api', 'scopes:be'], 'namespace' => 'Modules\Product\Http\Controllers'], function(){
    Route::post('create', 'ApiRequestProductController@createDev');
    Route::post('delete', 'ApiRequestProductController@destroyDev');
    Route::post('detail', 'ApiRequestProductController@detailDev');
    Route::post('update', 'ApiRequestProductController@updateDev');
    Route::post('/', 'ApiRequestProductController@indexDev');
    Route::post('all', 'ApiRequestProductController@all');
});

Route::group(['prefix' => 'api/mitra/req-product','middleware' => ['log_activities','user_agent','auth:mitra', 'scopes:mitra-apps'], 'namespace' => 'Modules\Product\Http\Controllers'], function(){
    // Route::post('{history}', 'ApiMitraRequestProductController@index');
    Route::post('detail/{type?}', 'ApiMitraRequestProductController@show');
    Route::post('confirm', 'ApiMitraRequestProductController@confirm');
    Route::post('/{type?}', 'ApiMitraRequestProductController@index');
});

Route::group(['prefix' => 'api/product-catalog','middleware' => ['log_activities','auth:api', 'scopes:be'], 'namespace' => 'Modules\Product\Http\Controllers'], function(){
    Route::post('create', 'ApiProductCatalogController@create');
    Route::post('delete', 'ApiProductCatalogController@destroy');
    Route::post('detail', 'ApiProductCatalogController@show');
    Route::post('update', 'ApiProductCatalogController@update');
    Route::post('/', 'ApiProductCatalogController@index');
    Route::any('all', 'ApiProductCatalogController@all');
});