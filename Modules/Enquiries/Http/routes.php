<?php

Route::group(['middleware' => ['auth:api','user_agent','log_activities', 'scopes:be'], 'prefix' => 'api/enquiries', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{

    Route::any('list', ['middleware' => 'feature_control:83', 'uses' =>'ApiEnquiries@index']);
    Route::any('detail', ['middleware' => 'feature_control:84', 'uses' =>'ApiEnquiries@indexDetail']);
    Route::post('reply', ['middleware' => 'feature_control:84', 'uses' =>'ApiEnquiries@reply']);
    Route::post('update', ['middleware' => 'feature_control:84', 'uses' =>'ApiEnquiries@update']);
    Route::post('delete', ['middleware' => 'feature_control:84', 'uses' =>'ApiEnquiries@delete']);
    Route::any('setting-subject', 'ApiEnquiries@settingSubject');

    Route::post('help-desk/create', 'ApiEnquiries@createV2');
    Route::post('help-desk/subject', 'ApiEnquiries@listEnquirySubject');
});

Route::group(['middleware' => ['auth:api','user_agent','log_activities', 'scopes:apps'], 'prefix' => 'api/enquiries', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{
    Route::post('create', 'ApiEnquiries@createV2');
    Route::post('list-subject', 'ApiEnquiries@listEnquirySubject');
    Route::post('list-category', 'ApiEnquiries@listEnquiryCategory');
    Route::post('list-transaction', 'ApiEnquiries@listTransaction');
    Route::post('detail', 'ApiEnquiries@detail');
});

Route::group(['middleware' => ['auth:mitra','user_agent','log_activities', 'scopes:mitra-apps'], 'prefix' => 'api/mitra/enquiries', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{
    Route::post('create', 'ApiEnquiries@createV2');
    Route::post('list-subject', 'ApiEnquiries@listEnquirySubject');
    Route::post('list-category', 'ApiEnquiries@listEnquiryCategory');
    Route::get('list-outlet', 'ApiEnquiries@ListOutlet');
    Route::post('list-transaction', 'ApiEnquiries@listTransactionMitra');
    Route::post('detail', 'ApiEnquiries@detail');
});

Route::group(['middleware' => ['auth:api','user_agent','log_activities_employee_apps', 'scopes:employee-apps'], 'prefix' => 'api/employee/enquiries', 'namespace' => 'Modules\Enquiries\Http\Controllers'], function()
{
    Route::post('create', 'ApiEnquiries@createV2');
    Route::post('list-subject', 'ApiEnquiries@listEnquirySubject');
    Route::post('list-category', 'ApiEnquiries@listEnquiryCategory');
    Route::get('list-outlet', 'ApiEnquiries@ListOutlet');
    Route::post('list-transaction', 'ApiEnquiries@listTransactionMitra');
    Route::post('detail', 'ApiEnquiries@detail');
});

