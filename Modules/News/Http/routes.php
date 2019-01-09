<?php

Route::group(['prefix' => 'api/news', 'namespace' => 'Modules\News\Http\Controllers'], function()
{
	Route::group(['middleware' => 'auth_client'], function() {
    	Route::any('list', 'ApiNews@listNews');
	
        // get news for custom form webview
        Route::post('get', 'ApiNews@getNewsById');
        // submit custom form webview
        Route::post('custom-form', 'ApiNews@customForm');
        // upload file in custom form webview
        Route::post('custom-form/file', 'ApiNews@customFormUploadFile');
    });
    
	/* AUTH */
	Route::group(['middleware' => 'auth:api'], function() {
    	Route::post('create', 'ApiNews@create');
    	Route::post('create/relation', 'ApiNews@createRelation');
    	Route::post('delete/relation', 'ApiNews@deleteRelation');
    	Route::post('update', 'ApiNews@update');
        Route::post('delete', 'ApiNews@delete');
        // get news form data
		Route::post('form-data', 'ApiNews@formData');
	});
    
});

Route::group(['prefix' => 'api/news', 'namespace' => 'Modules\News\Http\Controllers'], function()
{
        Route::any('list/web', 'ApiNews@listNews');
        Route::any('webview', 'ApiNews@webview');
});