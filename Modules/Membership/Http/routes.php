<?php
Route::group(['middleware' => ['auth:api', 'log_activities_apps'], 'prefix' => 'api/membership', 'namespace' => 'Modules\Membership\Http\Controllers'], function()
{
    Route::post('list', 'ApiMembership@listMembership');
	Route::post('create', 'ApiMembership@create');
    Route::post('update', 'ApiMembership@update');
    Route::post('delete', 'ApiMembership@delete');
    Route::any('/detail/webview', 'ApiMembershipWebview@webview');
    Route::get('update/transaction', 'ApiMembership@updateSubtotalTrxUser');
});