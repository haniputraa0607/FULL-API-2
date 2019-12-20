<?php

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {
    Route::get('/rule', 'ApiTransaction@transactionRule');
    Route::post('/rule/update', 'ApiTransaction@transactionRuleUpdate');
    Route::get('/courier', 'ApiTransaction@internalCourier');
    Route::post('/point', 'ApiTransaction@pointUser');
    Route::post('/point/filter', 'ApiTransaction@pointUserFilter');
    Route::post('/balance', 'ApiTransaction@balanceUser');
    Route::post('/balance/filter', 'ApiTransaction@balanceUserFilter');
    Route::post('/admin', 'ApiNotification@adminOutletNotification');
    Route::post('/setting', 'ApiSettingTransaction@settingTrx');

    Route::get('/', 'ApiTransaction@transactionList');
    Route::any('/filter', 'ApiTransaction@transactionFilter');
    Route::post('/detail', 'ApiTransaction@transactionDetail');
    Route::post('/point/detail', 'ApiTransaction@transactionPointDetail');
    Route::post('/balance/detail', 'ApiTransaction@transactionBalanceDetail');

    // Route::post('history', 'ApiHistoryController@historyAll');
    Route::post('history-trx/{mode?}', 'ApiHistoryController@historyTrx');
    Route::post('history-ongoing/{mode?}', 'ApiHistoryController@historyTrxOnGoing');
    // Route::post('history-point', 'ApiHistoryController@historyPoint');
    Route::post('history-balance/{mode?}', 'ApiHistoryController@historyBalance');

    Route::post('/shipping', 'ApiTransaction@getShippingFee');
    Route::get('/address', 'ApiTransaction@getAddress');
    Route::post('/address/add', 'ApiTransaction@addAddress');
    Route::post('/address/update', 'ApiTransaction@updateAddress');
    Route::post('/address/delete', 'ApiTransaction@deleteAddress');
    Route::post('/void', 'ApiTransaction@transactionVoid');

    Route::post('/check', 'ApiOnlineTransaction@checkTransaction');
    Route::post('/new', 'ApiOnlineTransaction@newTransaction');
    Route::post('/confirm', 'ApiConfirm@confirmTransaction');
    Route::post('/prod/confirm', 'ApiTransactionProductionController@confirmTransaction2');
    Route::get('/{key}', 'ApiTransaction@transactionList');
});

Route::group(['middleware' => 'auth_client', 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {
    Route::post('/province', 'ApiTransaction@getProvince');
    Route::post('/city', 'ApiTransaction@getCity');
    Route::post('/subdistrict', 'ApiTransaction@getSubdistrict');
    Route::post('/courier', 'ApiTransaction@getCourier');
    Route::any('/grand-total', 'ApiSettingTransactionV2@grandTotal');

    Route::post('/new-transaction', 'ApiTransaction@transaction');

    Route::post('/shipping/gosend', 'ApiTransaction@shippingCostGoSend');
});

Route::group(['prefix' => 'api/transaction', 'middleware' => 'log_activities', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {
    Route::any('/finish', 'ApiTransaction@transactionFinish');
    Route::any('/cancel', 'ApiTransaction@transactionCancel');
    Route::any('/error', 'ApiTransaction@transactionError');
    Route::any('/notif', 'ApiNotification@receiveNotification');
});

Route::group(['prefix' => 'api/transaction', 'middleware' => ['log_activities', 'auth:api'], 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {
    Route::post('/detail/webview/point', 'ApiWebviewController@webviewPoint');
    Route::post('/detail/webview/balance', 'ApiWebviewController@webviewBalance');
    Route::post('/detail/webview/{mode?}', 'ApiWebviewController@webview');

    Route::post('/detail/webview/success', 'ApiWebviewController@trxSuccess');
});

Route::group(['middleware' => 'auth:api', 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {
    Route::post('/gen', 'ApiDumpController@generateNumber');
});

Route::group(['middleware' => 'auth_client', 'prefix' => 'api/manual-payment', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {
    Route::get('/', 'ApiTransactionPaymentManual@listPaymentMethod');
    Route::get('/list', 'ApiTransactionPaymentManual@list');
    Route::post('/method', 'ApiTransactionPaymentManual@paymentMethod');
});

Route::group(['prefix' => 'api/cron/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {
    Route::any('/pickup/completed', 'ApiCronTrxController@completeTransactionPickup');
    Route::any('/expire', 'ApiCronTrxController@cron');
    Route::any('/schedule', 'ApiCronTrxController@checkSchedule');
});

Route::group(['prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {
    Route::get('/data/decript/{data}', function ($data) {
        return response()->json(App\Lib\MyHelper::decrypt2019($data));
    });
    Route::get('/data/encrypt/{data}', function ($data) {
        // return response()->json(App\Lib\MyHelper::decrypt2019($data));

        return response()->json(App\Lib\MyHelper::encrypt2019($data));
    });
});

Route::group(['prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function() {
    Route::any('/web/view/detail', 'ApiWebviewController@detail');
    Route::any('/web/view/detail/check', 'ApiWebviewController@check');
    Route::any('/web/view/detail/point', 'ApiWebviewController@detailPoint');
    Route::any('/web/view/detail/balance', 'ApiWebviewController@detailBalance');
    Route::any('/web/view/trx', 'ApiWebviewController@success');
    Route::any('/web/view/outletapp', 'ApiWebviewController@receiptOutletapp');
});

Route::group(['middleware' => ['auth:api-be', 'log_activities'], 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {
    Route::post('/outlet', 'ApiNotification@adminOutlet');
    Route::post('/admin/confirm', 'ApiNotification@adminOutletComfirm');
    Route::get('setting/cashback', 'ApiSettingCashbackController@list');
    Route::post('setting/cashback/update', 'ApiSettingCashbackController@update');
    Route::post('/dump', 'ApiDumpController@dumpData');
    Route::group(['prefix' => 'manualpayment'], function () {
        Route::get('/bank', 'ApiTransactionPaymentManual@bankList');
        Route::post('/bank/delete', 'ApiTransactionPaymentManual@bankDelete');
        Route::post('/bank/create', 'ApiTransactionPaymentManual@bankCreate');
        Route::get('/bankmethod', 'ApiTransactionPaymentManual@bankmethodList');
        Route::post('/bankmethod/delete', 'ApiTransactionPaymentManual@bankmethodDelete');
        Route::post('/bankmethod/create', 'ApiTransactionPaymentManual@bankmethodCreate');
        Route::get('/list', 'ApiTransaction@manualPaymentList');
        Route::post('/edit', 'ApiTransaction@manualPaymentEdit');
        Route::post('/update', 'ApiTransaction@manualPaymentUpdate');
        Route::post('/create', 'ApiTransaction@manualPaymentCreate');
        Route::post('/detail', 'ApiTransaction@manualPaymentDetail');
        Route::post('/delete', 'ApiTransaction@manualPaymentDelete');

        Route::group(['prefix' => 'data'], function () {
            Route::get('/{type}', 'ApiTransactionPaymentManual@manualPaymentList');
            Route::post('/detail', 'ApiTransactionPaymentManual@detailManualPaymentUnpay');
            Route::post('/confirm', 'ApiTransactionPaymentManual@manualPaymentConfirm');
            Route::post('/filter/{type}', 'ApiTransactionPaymentManual@transactionPaymentManualFilter');
        });

        Route::post('/method/save', 'ApiTransaction@manualPaymentMethod');
        Route::post('/method/delete', 'ApiTransaction@manualPaymentMethodDelete');
        Route::post('/be/new', 'ApiOnlineTransaction@newTransaction');
    });
});