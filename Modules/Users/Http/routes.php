<?php

Route::group(['prefix' => 'api', 'middleware' => 'log_activities'], function(){
	Route::group(['middleware' => ['auth_client','log_activities'], 'prefix' => 'users', 'namespace' => 'Modules\Users\Http\Controllers'], function()
	{
        Route::post('phone/check', 'ApiUser@check');
        Route::post('pin/check', 'ApiUser@checkPin');
	    Route::post('pin/check-backend', 'ApiUser@checkPinBackend');
        Route::post('pin/resend', 'ApiUser@resendPin');
        Route::post('pin/forgot', 'ApiUser@forgotPin');
        Route::post('pin/verify', 'ApiUser@verifyPin');
        Route::post('pin/create', 'ApiUser@createPin');
        Route::post('pin/change', 'ApiUser@changePin');
        Route::post('profile/update', 'ApiUser@profileUpdate');
	});

    Route::group(['middleware' => 'auth:api', 'prefix' => 'home', 'namespace' => 'Modules\Users\Http\Controllers'], function()
    {
        Route::post('/membership','ApiHome@membership');
        Route::any('/banner','ApiHome@banner');
        Route::any('/featured-deals','ApiHome@featuredDeals');
        Route::any('/featured-subscription','ApiHome@featuredSubscription');
        Route::post('refresh-point-balance', 'ApiHome@refreshPointBalance');
    });

    Route::group(['prefix' => 'home', 'namespace' => 'Modules\Users\Http\Controllers'], function()
    {
        Route::any('splash','ApiHome@splash');
        Route::any('notloggedin', 'ApiHome@homeNotLoggedIn');
    });
});

Route::group(['middleware' => 'auth:api-be', 'namespace' => 'Modules\Users\Http\Controllers'], function()
{
	Route::get('user-delete/{phone}', 'ApiUser@deleteUser');
	Route::post('user-delete/{phone}', 'ApiUser@deleteUserAction');
});

Route::group(['prefix' => 'api/cron', 'namespace' => 'Modules\Users\Http\Controllers'], function()
{
	Route::any('/reset-trx-day', 'ApiUser@resetCountTransaction');
});

Route::group(['middleware' => ['auth:api-be','log_activities'], 'prefix' => 'api/users', 'namespace' => 'Modules\Users\Http\Controllers'], function(){
    Route::post('pin/check/be', 'ApiUser@checkPin');
    Route::get('list/{var}', 'ApiUser@listVar');
    Route::post('new', 'ApiUser@newUser');
    Route::post('update/profile', 'ApiUser@updateProfile');
    Route::post('update/pin', 'ApiUser@updatePin');
    Route::post('update/status', 'ApiUser@updateStatus');
    Route::post('update/feature', 'ApiUser@updateFeature');
    Route::post('profile', 'ApiUser@profile');

    Route::any('summary', 'ApiUser@summaryUsers');
    Route::post('check', 'ApiUser@check');
    Route::post('fitur', 'ApiUser@fitur');

    Route::post('granted-feature', 'ApiUser@getFeatureControl');
    Route::get('rank/list', 'ApiUser@listRank');
    Route::post('create', 'ApiUser@createUserFromAdmin');

    Route::post('list', 'ApiUser@list');
    Route::post('adminoutlet/detail', 'ApiUser@detailAdminOutlet');
    Route::post('adminoutlet/list', 'ApiUser@listAdminOutlet');
    Route::post('adminoutlet/create', 'ApiUser@createAdminOutlet');
    Route::post('adminoutlet/delete', 'ApiUser@deleteAdminOutlet');
    Route::post('activity', 'ApiUser@activity');
    Route::post('detail', 'ApiUser@show');
    Route::post('favorite', 'ApiUser@favorite');
    Route::post('log', 'ApiUser@log');
    Route::get('log/detail/{id}/{log_type}', 'ApiUser@detailLog');
    Route::post('delete', 'ApiUser@delete');
    Route::post('update', 'ApiUser@updateProfileByAdmin');
    Route::post('update/photo', 'ApiUser@updateProfilePhotoByAdmin');
    Route::post('update/password', 'ApiUser@updateProfilePasswordByAdmin');
    Route::post('update/level', 'ApiUser@updateProfileLevelByAdmin');
    Route::post('update/outlet', 'ApiUser@updateDoctorOutletByAdmin');
    Route::post('update/permission', 'ApiUser@updateProfilePermissionByAdmin');
    Route::post('update/suspend', 'ApiUser@updateSuspendByAdmin');
    Route::post('update/outlet', 'ApiUser@updateUserOutletByAdmin');
    Route::post('phone/verified', 'ApiUser@phoneVerified');
    Route::post('phone/unverified', 'ApiUser@phoneUnverified');
    Route::post('email/verified', 'ApiUser@emailVerified');
    Route::post('email/unverified', 'ApiUser@emailUnverified');
    Route::post('inbox', 'ApiUser@inboxUser');
    Route::post('outlet', 'ApiUser@outletUser');
    Route::any('notification', 'ApiUser@getUserNotification');
    Route::get('get-all', 'ApiUser@getAllName');
    Route::any('get-detail', 'ApiUser@getDetailUser');

    // get user profile
    Route::get('get', 'ApiUser@getUserDetail');
    // skip completes user profile
    Route::get('complete-profile/later', 'ApiWebviewUser@completeProfileLater');
    // submit complete user profile
    Route::post('complete-profile', 'ApiWebviewUser@completeProfile');
    // get complete user profile success message
    Route::get('complete-profile/success-message', 'ApiWebviewUser@getSuccessMessage');

});
