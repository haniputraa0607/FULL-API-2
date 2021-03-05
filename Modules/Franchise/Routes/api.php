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

Route::group(['prefix' => 'franchise'], function () {
    Route::group(['middleware' => ['auth_client', 'scopes:franchise-client']], function () {
        Route::post('reset-password', 'ApiUserFranchiseController@resetPassword');
    });
    Route::group(['middleware' => ['auth:franchise', 'scopes:franchise-super-admin']], function () {
        Route::group(['prefix' => 'user'], function() {
            Route::any('/', 'ApiUserFranchiseController@index');
            Route::post('store', 'ApiUserFranchiseController@store');
            Route::post('detail', 'ApiUserFranchiseController@detail');
            Route::post('update', 'ApiUserFranchiseController@update');
            Route::post('delete', 'ApiUserFranchiseController@destroy');

            Route::post('autoresponse', 'ApiUserFranchiseController@autoresponse');
            Route::post('autoresponse/new-user/update', 'ApiUserFranchiseController@updateAutoresponse');
            Route::post('import', 'ApiUserFranchiseController@import');
        });
        Route::get('outlets', 'ApiUserFranchiseController@allOutlet');
        Route::post('profile', 'ApiUserFranchiseController@updateProfile');
    });

    Route::group(['middleware' => ['auth:franchise', 'scopes:franchise-client']], function () {
        Route::group(['prefix' => 'user'], function() {
            Route::post('update-first-pin', 'ApiUserFranchiseController@updateFirstPin');
            Route::post('detail/for-login', 'ApiUserFranchiseController@detail');
        });
    });

    Route::group(['middleware' => ['auth:franchise', 'scopes:franchise-user']], function () {
        Route::group(['prefix' => 'user'], function() {
            Route::post('detail-admin', 'ApiUserFranchiseController@detail');
        });
        Route::post('profile-admin', 'ApiUserFranchiseController@updateProfile');

        Route::group(['prefix' => 'transaction'], function () {
		    Route::any('filter', 'ApiTransactionFranchiseController@transactionFilter');
		    Route::post('detail','ApiTransactionFranchiseController@transactionDetail');

		    Route::get('export','ApiTransactionFranchiseController@listExport');
	        Route::post('export','ApiTransactionFranchiseController@newExport');
	        Route::delete('export/{export_queue}','ApiTransactionFranchiseController@destroyExport');
	        Route::any('export/action', 'ApiTransactionFranchiseController@actionExport');
		});

		Route::group(['prefix' => 'product'], function() {
            Route::post('list','ApiTransactionFranchiseController@listProduct');
		    Route::post('category/list','ApiTransactionFranchiseController@listProductCategory');
        });

        Route::group(['prefix' => 'report-payment'], function() {
            Route::post('summary', 'ApiReportPaymentController@summaryPaymentMethod');
            Route::post('summary/detail', 'ApiReportPaymentController@summaryDetailPaymentMethod');
            Route::post('summary/chart', 'ApiReportPaymentController@summaryChart');
            Route::post('list', 'ApiReportPaymentController@listPayment');
            Route::get('payments', 'ApiReportPaymentController@payments');
        });

        Route::group(['prefix' => 'report-disburse'], function() {
            Route::post('summary', 'ApiReportDisburseController@summary');
            Route::post('list-transaction', 'ApiReportDisburseController@listTransaction');
        });
        Route::group(['prefix' => 'report-disburse'], function() {
            Route::post('summary', 'ApiReportDisburseController@summary');
            Route::post('list', 'ApiReportDisburseController@listDisburse');
            Route::post('detail', 'ApiReportDisburseController@detailDisburse');
            Route::post('list-transaction', 'ApiReportDisburseController@listTransaction');
            Route::get('list-bank', 'ApiReportDisburseController@listBank');
        });

        Route::group(['prefix' => 'report-sales'], function() {
            Route::post('summary', 'ApiReportSalesController@summary');
        });

        Route::group(['prefix' => 'outlet'], function () {
            Route::get('detail','ApiOutletFranchiseController@detail');
            Route::post('update','ApiOutletFranchiseController@update');
            Route::post('update-schedule','ApiOutletFranchiseController@updateSchedule');
        });

        Route::get('select-list/{table}','ApiReportTransactionController@listForSelect');

        Route::group(['prefix' => 'report-transaction'], function() {
            Route::post('product', 'ApiReportTransactionController@product');
            Route::post('modifier', 'ApiReportTransactionController@modifier');
        });
    });

});
