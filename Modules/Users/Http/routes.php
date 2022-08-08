<?php

Route::group(['namespace' => 'Modules\Users\Http\Controllers'], function()
{
    Route::any('email/verify/{slug}', 'ApiUser@verifyEmail');
});

Route::group(['middleware' => ['auth_client','log_activities', 'user_agent', 'scopes:apps'], 'prefix' => 'api/v2/users', 'namespace' => 'Modules\Users\Http\Controllers'], function()
{
    Route::post('phone/check', 'ApiUserV2@phoneCheck');

    Route::post('pin/forgot', 'ApiUserV2@forgotPin');
});

Route::group(['prefix' => 'api', 'middleware' => ['log_activities', 'user_agent']], function(){
    Route::group(['middleware' => ['auth_client', 'scopes:apps'], 'namespace' => 'Modules\Users\Http\Controllers'], function()
    {
        Route::post('validation-phone', 'ApiUser@validationPhone');
    });

	Route::group(['middleware' => ['auth_client', 'scopes:apps'], 'prefix' => 'users', 'namespace' => 'Modules\Users\Http\Controllers'], function()
	{
        Route::post('pin/verify', 'ApiUser@verifyPin')->middleware('decrypt_pin');
        Route::post('pin/create', 'ApiUser@createPin');
        Route::post('pin/check', 'ApiUser@checkPin')->middleware('decrypt_pin');
        Route::post('pin/resend', 'ApiUser@resendPin');
        Route::post('phone/check', 'ApiUserV2@phoneCheck');
        Route::post('pin/forgot', 'ApiUserV2@forgotPin');
        Route::post('pin/change', 'ApiUserV2@changePin')->middleware(['decrypt_pin:pin_new','decrypt_pin:pin_old']);
        Route::post('pin/request', 'ApiUserV2@pinRequest');
        Route::group(['middleware' => ['auth:api']], function()
	    {
        	Route::post('profile/update', 'ApiUser@profileUpdate');
            Route::post('claim-point', 'ApiUserV2@claimPoint');
	    });
	});

    Route::group(['middleware' => ['auth_client', 'scopes:web-apps'], 'prefix' => 'webapp/users', 'namespace' => 'Modules\Users\Http\Controllers'], function()
    {
        Route::post('pin/verify', 'ApiUser@verifyPin')->middleware('decrypt_pin');
        Route::post('pin/check', 'ApiUser@checkPin')->middleware('decrypt_pin');
        Route::post('phone/check', 'ApiUserV2@phoneCheck');
        Route::post('pin/forgot', 'ApiUserV2@forgotPin');
        Route::post('pin/change', 'ApiUserV2@changePin')->middleware(['decrypt_pin:pin_new','decrypt_pin:pin_old']);
        Route::post('pin/request', 'ApiUserV2@pinRequest');
        Route::group(['middleware' => ['auth:api']], function()
	    {
        	Route::post('profile/update', 'ApiUser@profileUpdate');
	    });
    });

    Route::group(['middleware' => ['auth_client', 'scopes:be'], 'prefix' => 'users', 'namespace' => 'Modules\Users\Http\Controllers'], function()
    {
        Route::post('pin/check-backend', 'ApiUser@checkPinBackend');
        Route::post('remove-user-device', 'ApiUser@removeUserDevice');
    });
    Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:apps,web-apps'], 'prefix' => 'home', 'namespace' => 'Modules\Users\Http\Controllers'], function()
    {
        Route::post('/scan-qr','ApiHome@scanQR');
        Route::post('/membership','ApiHome@membership');
        Route::any('/banner','ApiHome@banner');
        Route::any('/featured-deals','ApiHome@featuredDeals');
        Route::any('/featured-subscription','ApiHome@featuredSubscription');
        Route::any('/featured-promo-campaign','ApiHome@featuredPromoCampaign');
        Route::any('/featured-news','ApiHome@featuredNews');
        Route::any('/featured-product','ApiHome@featuredProduct');
        Route::post('refresh-point-balance', 'ApiHome@refreshPointBalance');
        Route::get('social-media','ApiHome@socialMedia');
    });

    Route::group(['middleware' => ['auth:api', 'scopes:apps'], 'prefix' => 'users', 'namespace' => 'Modules\Users\Http\Controllers'], function()
    {
        Route::any('send/email/verify', 'ApiUser@sendVerifyEmail');
    });

    Route::group(['prefix' => 'home', 'namespace' => 'Modules\Users\Http\Controllers'], function()
    {
        Route::any('splash','ApiHome@splash');
        Route::any('notloggedin', 'ApiHome@homeNotLoggedIn');
    });

    Route::group(['prefix' => 'webapp', 'namespace' => 'Modules\Users\Http\Controllers'], function()
    {
        Route::any('splash','ApiHome@splashWebApps');
    });
});

Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:be'], 'namespace' => 'Modules\Users\Http\Controllers'], function()
{
	Route::get('user-delete/{phone}', ['middleware' => 'feature_control:6', 'uses' => 'ApiUser@deleteUser']);
	Route::post('user-delete/{phone}', ['middleware' => 'feature_control:6', 'uses' => 'ApiUser@deleteUserAction']);
});

Route::group(['prefix' => 'api/cron', 'namespace' => 'Modules\Users\Http\Controllers'], function()
{
	Route::any('/reset-trx-day', 'ApiUser@resetCountTransaction');
});

Route::group(['middleware' => ['auth:api','log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/users', 'namespace' => 'Modules\Users\Http\Controllers'], function(){
    Route::post('pin/check/be', 'ApiUser@checkPinBackend');
    Route::post('list/address', 'ApiUser@listAddress');
    Route::get('list/{var}', 'ApiUser@listVar');
    Route::post('new', ['middleware' => 'feature_control:4', 'uses' => 'ApiUser@newUser']);
    Route::post('update/profile', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updateProfile']);
    Route::post('update/pin', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updatePin']);
    Route::post('update/status', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updateStatus']);
    Route::post('update/feature', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updateFeature']);
    Route::post('profile', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@profile']);

    Route::any('summary', 'ApiUser@summaryUsers');
    Route::post('check', 'ApiUser@check');
    Route::post('fitur', 'ApiUser@fitur');

    Route::post('granted-feature', 'ApiUser@getFeatureControl');
    Route::get('rank/list', 'ApiUser@listRank');
    Route::post('create', 'ApiUser@createUserFromAdmin');

    Route::post('list', ['middleware' => 'feature_control:2', 'uses' => 'ApiUser@list']);
    Route::post('adminoutlet/detail', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@detailAdminOutlet']);
    Route::post('adminoutlet/list', ['middleware' => 'feature_control:2', 'uses' => 'ApiUser@listAdminOutlet']);
    Route::post('adminoutlet/create', ['middleware' => 'feature_control:4', 'uses' => 'ApiUser@createAdminOutlet']);
    Route::post('adminoutlet/delete', ['middleware' => 'feature_control:6', 'uses' => 'ApiUser@deleteAdminOutlet']);
    Route::post('activity', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@activity']);
    Route::post('detail', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@show']);
    Route::post('favorite', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@favorite']);
    Route::post('log', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@log']);
    Route::get('log/detail/{id}/{log_type}', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@detailLog']);
    Route::post('delete', ['middleware' => 'feature_control:6', 'uses' => 'ApiUser@delete']);
    Route::post('delete/log', ['middleware' => 'feature_control:6', 'uses' => 'ApiUser@deleteLog']);
    Route::post('update', ['uses' => 'ApiUser@updateProfileByAdmin']);
    Route::post('update/photo', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updateProfilePhotoByAdmin']);
    Route::post('update/password', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updateProfilePasswordByAdmin']);
    Route::post('update/level', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updateProfileLevelByAdmin']);
    Route::post('update/outlet', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updateDoctorOutletByAdmin']);
    Route::post('update/permission', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updateProfilePermissionByAdmin']);
    Route::post('update/suspend', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updateSuspendByAdmin']);
    Route::post('update/outlet', ['middleware' => 'feature_control:5', 'uses' => 'ApiUser@updateUserOutletByAdmin']);
    Route::post('phone/verified', 'ApiUser@phoneVerified');
    Route::post('phone/unverified', 'ApiUser@phoneUnverified');
    Route::post('email/verified', 'ApiUser@emailVerified');
    Route::post('email/unverified', 'ApiUser@emailUnverified');
    Route::post('inbox', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@inboxUser']);
    Route::post('outlet', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@outletUser']);
    Route::any('notification', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@getUserNotification']);
    Route::get('get-all', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@getAllName']);
    Route::any('get-detail', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@getDetailUser']);
    Route::any('getExtraToken', 'ApiUser@getExtraToken');

    // get user profile
    Route::get('get', ['middleware' => 'feature_control:3', 'uses' => 'ApiUser@getUserDetail']);
    // skip completes user profile
    Route::get('complete-profile/later', ['middleware' => 'feature_control:3', 'uses' => 'ApiWebviewUser@completeProfileLater']);
    // submit complete user profile
    Route::post('complete-profile', ['middleware' => 'feature_control:3', 'uses' => 'ApiWebviewUser@completeProfile']);
    // get complete user profile success message
    Route::get('complete-profile/success-message', ['middleware' => 'feature_control:3', 'uses' => 'ApiWebviewUser@getSuccessMessage']);

    Route::group(['prefix' => 'department'], function()
    {
        Route::any('/', ['middleware' => 'feature_control:328', 'uses' => 'ApiDepartment@index']);
	    Route::post('store', ['middleware' => 'feature_control:329', 'uses' => 'ApiDepartment@store']);
	    Route::post('edit', ['middleware' => 'feature_control:330', 'uses' => 'ApiDepartment@edit']);
	    Route::post('update', ['middleware' => 'feature_control:331', 'uses' => 'ApiDepartment@update']);
	    Route::post('delete', ['middleware' => 'feature_control:332', 'uses' => 'ApiDepartment@destroy']);
	    Route::post('sync', ['middleware' => 'feature_control:329', 'uses' => 'ApiDepartment@syncIcount']);
    });

    Route::group(['prefix' => 'job-level'], function()
    {
        Route::any('/', ['middleware' => 'feature_control:323', 'uses' => 'ApiJobLevelController@index']);
        Route::post('store', ['middleware' => 'feature_control:324', 'uses' => 'ApiJobLevelController@store']);
        Route::post('edit', ['middleware' => 'feature_control:325,326', 'uses' => 'ApiJobLevelController@edit']);
        Route::post('update', ['middleware' => 'feature_control:326', 'uses' => 'ApiJobLevelController@update']);
        Route::post('delete', ['middleware' => 'feature_control:327', 'uses' => 'ApiJobLevelController@destroy']);
        Route::post('position', ['middleware' => 'feature_control:323,326', 'uses' => 'ApiJobLevelController@position']);
    });

    Route::group(['prefix' => 'role'], function()
    {
        Route::any('/', ['middleware' => 'feature_control:333', 'uses' => 'ApiRoleController@index']);
        Route::get('list-all', ['uses' => 'ApiRoleController@listAll']);
        Route::post('store', ['middleware' => 'feature_control:334', 'uses' => 'ApiRoleController@store']);
        Route::post('edit', ['middleware' => 'feature_control:335,336', 'uses' => 'ApiRoleController@edit']);
        Route::post('update', ['middleware' => 'feature_control:336', 'uses' => 'ApiRoleController@update']);
        Route::post('delete', ['middleware' => 'feature_control:337', 'uses' => 'ApiRoleController@destroy']);
    });
});

Route::group(['middleware' => ['auth_client', 'scopes:employee-apps'], 'prefix' => 'api/employee', 'namespace' => 'Modules\Users\Http\Controllers'], function()
{
    Route::post('phone/check', 'ApiUserV2@phoneCheckEmployee');
    Route::post('pin/forgot', 'ApiUserV2@forgotPin');
    Route::post('pin/change', 'ApiUserV2@changePinEmployee')->middleware(['decrypt_pin:old_password','decrypt_pin:new_password','decrypt_pin:confirm_new_password','auth:api']);
    Route::post('pin/verify', 'ApiUser@verifyPin')->middleware('decrypt_pin');
});