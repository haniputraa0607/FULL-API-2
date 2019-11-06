<?php

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/setting-fraud', 'namespace' => 'Modules\SettingFraud\Http\Controllers'], function()
{
    Route::any('/', 'ApiSettingFraud@listSettingFraud');
    Route::post('/update', 'ApiSettingFraud@updateSettingFraud');
});
