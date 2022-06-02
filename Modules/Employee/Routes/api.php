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

Route::group([ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be'], 'prefix' => 'employee'], function () {
    Route::post('list', 'ApiEmployeeController@employeeList');

    Route::group(['prefix' => 'office-hours'], function(){
        Route::get('/', 'ApiEmployeeController@officeHoursList');
        Route::post('create', 'ApiEmployeeController@officeHoursCreate');
        Route::post('detail', 'ApiEmployeeController@officeHoursDetail');
        Route::post('update', 'ApiEmployeeController@officeHoursUpdate');
        Route::post('delete', 'ApiEmployeeController@officeHoursDelete');
        Route::get('default', 'ApiEmployeeController@officeHoursDefault');
        Route::get('assign', 'ApiEmployeeController@officeHoursAssign');
        Route::post('assign', 'ApiEmployeeController@officeHoursAssign');
    });

    Route::group(['prefix' => 'announcement'], function(){
        Route::any('/', 'ApiEmployeeAnnouncementController@listAnnouncement');
        Route::post('create', 'ApiEmployeeAnnouncementController@createAnnouncement');
        Route::post('detail', 'ApiEmployeeAnnouncementController@detailAnnouncement');
        Route::post('delete', 'ApiEmployeeAnnouncementController@deleteAnnouncement');
    });

    Route::group(['prefix' => 'schedule'], function(){
        Route::any('list', 'ApiEmployeeScheduleController@list');
        Route::post('create', 'ApiEmployeeScheduleController@create');
        Route::post('detail/use-shift', 'ApiEmployeeScheduleController@detailShift');
        Route::post('detail/without-shift', 'ApiEmployeeScheduleController@detailNonShift');
        Route::post('update', 'ApiEmployeeScheduleController@update');
        Route::post('delete', 'ApiEmployeeScheduleController@deleteAnnouncement');
        Route::any('year-list', 'ApiEmployeeScheduleController@getScheduleYear');
    });
    Route::group(['prefix' => 'be/recruitment'], function(){
        Route::any('/', 'ApiBeEmployeeController@index');
        Route::get('bank', 'ApiBeEmployeeController@bank');
        Route::post('reject', 'ApiBeEmployeeController@reject');
        Route::post('detail', 'ApiBeEmployeeController@detail');
        Route::post('create', 'ApiBeEmployeeController@create');
        Route::post('candidate', 'ApiBeEmployeeController@candidate');
        Route::post('detail', 'ApiBeEmployeeController@candidateDetail');
        Route::post('update', 'ApiBeEmployeeController@update');
        Route::post('complement', 'ApiBeEmployeeController@complement');
    });
    Route::group(['prefix' => 'be/question'], function(){
        Route::post('category', 'ApiQuestionEmployeeController@category');
        Route::post('create', 'ApiQuestionEmployeeController@create');
    });
    Route::group(['prefix' => 'be/profile'], function(){
     Route::group(['prefix' => 'emergency'], function(){
        Route::post('/', 'ApiBeEmployeeProfileController@emergency_contact');
        Route::post('/create', 'ApiBeEmployeeProfileController@create_emergency_contact');
        Route::post('/detail', 'ApiBeEmployeeProfileController@detail_emergency_contact');
        Route::post('/update', 'ApiBeEmployeeProfileController@update_emergency_contact');
        Route::post('/delete', 'ApiBeEmployeeProfileController@delete_emergency_contact');
      });
     Route::group(['prefix' => 'perubahan-data'], function(){
        Route::post('/', 'ApiBeEmployeeProfileController@perubahan_data');
        Route::post('/update', 'ApiBeEmployeeProfileController@update_perubahan_data');
      });
     Route::group(['prefix' => 'faq'], function(){
        Route::post('/', 'ApiBeEmployeeProfileController@faq');
        Route::post('/create', 'ApiBeEmployeeProfileController@create_faq');
        Route::post('/detail', 'ApiBeEmployeeProfileController@detail_faq');
        Route::post('/update', 'ApiBeEmployeeProfileController@update_faq');
        Route::post('/delete', 'ApiBeEmployeeProfileController@delete_faq');
        Route::post('/popular', 'ApiBeEmployeeProfileController@create_faq_popular');
      });
     Route::group(['prefix' => 'privacy-policy'], function(){
        Route::post('/', 'ApiBeEmployeeProfileController@privacy_policy');
        Route::post('/update', 'ApiBeEmployeeProfileController@privacy_policy_update');
      });
    });
    Route::group(['prefix' => 'be/asset-inventory'], function(){
        Route::group(['prefix' => 'category'], function(){
            Route::get('/', 'ApiBeEmployeeAssetInventoryController@list_category');
            Route::post('/create', 'ApiBeEmployeeAssetInventoryController@create_category');
            Route::post('/delete', 'ApiBeEmployeeAssetInventoryController@delete_category');
        });
        Route::group(['prefix' => 'loan'], function(){
            Route::get('/pending', 'ApiBeEmployeeAssetInventoryController@list_loan_pending');
            Route::get('/list', 'ApiBeEmployeeAssetInventoryController@list_loan');
            Route::post('/approve', 'ApiBeEmployeeAssetInventoryController@approve_loan');
        });
        Route::group(['prefix' => 'return'], function(){
            Route::get('/pending', 'ApiBeEmployeeAssetInventoryController@list_return_pending');
            Route::get('/list', 'ApiBeEmployeeAssetInventoryController@list_return');
            Route::post('/approve', 'ApiBeEmployeeAssetInventoryController@approve_return');
        });
       
        Route::post('/create', 'ApiBeEmployeeAssetInventoryController@create');
        Route::post('/list', 'ApiBeEmployeeAssetInventoryController@list');
    });
    Route::any('attendance-setting','ApiEmployeeAttendanceController@setting');
    Route::group(['prefix' => 'attendance'], function () {
        Route::post('list','ApiEmployeeAttendanceController@list');
        Route::post('detail','ApiEmployeeAttendanceController@detail');
    });
    Route::group(['prefix' => 'attendance-pending'], function () {
        Route::post('list','ApiEmployeeAttendanceController@listPending');
        Route::post('detail','ApiEmployeeAttendanceController@detailPending');
        Route::post('update','ApiEmployeeAttendanceController@updatePending');
    });
    Route::group(['prefix' => 'attendance-request'], function () {
        Route::post('list','ApiEmployeeAttendanceController@listRequest');
        Route::post('detail','ApiEmployeeAttendanceController@detailRequest');
        Route::post('update','ApiEmployeeAttendanceController@updateRequest');
    });

    Route::group(['prefix' => 'attendance-outlet'], function () {
        Route::post('list','ApiEmployeeAttendaceOutletController@list');
        Route::post('detail','ApiEmployeeAttendaceOutletController@detail');
    });
    Route::group(['prefix' => 'attendance-outlet-pending'], function () {
        Route::post('list','ApiEmployeeAttendaceOutletController@listPending');
        Route::post('detail','ApiEmployeeAttendaceOutletController@detailPending');
        Route::post('update','ApiEmployeeAttendaceOutletController@updatePending');
    });
    Route::group(['prefix' => 'attendance-outlet-request'], function () {
        Route::post('list','ApiEmployeeAttendaceOutletController@listRequest');
        Route::post('detail','ApiEmployeeAttendaceOutletController@detailRequest');
        Route::post('update','ApiEmployeeAttendaceOutletController@updateRequest');
    });

    Route::post('shift','ApiEmployeeController@shift');

    Route::group(['prefix' => 'timeoff'], function () {
        Route::post('list', 'ApiEmployeeTimeOffOvertimeController@listTimeOff');
        Route::post('delete', 'ApiEmployeeTimeOffOvertimeController@deleteTimeOff');
        Route::post('detail', 'ApiEmployeeTimeOffOvertimeController@detailTimeOff');
        Route::post('update', 'ApiEmployeeTimeOffOvertimeController@updateTimeOff');
        Route::post('create', 'ApiEmployeeTimeOffOvertimeController@createTimeOff');
        Route::post('list-employee', 'ApiEmployeeTimeOffOvertimeController@listEmployee');
        Route::post('list-date', 'ApiEmployeeTimeOffOvertimeController@listDate');
    });

    Route::group(['prefix' => 'overtime'], function () {
        Route::post('list', 'ApiEmployeeTimeOffOvertimeController@listOvertime');
        Route::post('detail', 'ApiEmployeeTimeOffOvertimeController@detailOvertime');
        Route::post('update', 'ApiEmployeeTimeOffOvertimeController@updateOvertime');
        Route::post('create', 'ApiEmployeeTimeOffOvertimeController@createOvertime');
        Route::post('delete', 'ApiEmployeeTimeOffOvertimeController@deleteOvertime');
    });
    
    Route::group(['prefix' => 'be/reimbursement'], function () {
        Route::post('/','ApiBeEmployeeReimbursementController@list');
        Route::post('/detail','ApiBeEmployeeReimbursementController@detail');
        Route::post('/approved','ApiBeEmployeeReimbursementController@approved');
         });
});

Route::group([ 'middleware' => ['log_activities', 'auth:api','auth_client','scopes:landing-page'], 'prefix' => 'employee'], function () {
    Route::group(['prefix' => 'recruitment'], function(){
        Route::post('create', 'ApiRegisterEmployeeController@create');
    });
});

Route::group([ 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:employees'], 'prefix' => 'employee'], function () {
    Route::group(['prefix' => 'recruitment'], function(){
        Route::post('detail', 'ApiRegisterEmployeeController@detail');
        Route::post('update', 'ApiRegisterEmployeeController@update');
        Route::post('submit', 'ApiRegisterEmployeeController@submit');
        Route::post('submit', 'ApiRegisterEmployeeController@submit');
        Route::get('question', 'ApiQuestionEmployeeController@list');
    });
});

Route::group([ 'middleware' => ['log_activities_employee_apps','auth:api','user_agent', 'scopes:employee-apps'], 'prefix' => 'employee'], function () {
    Route::get('announcement','ApiEmployeeAnnouncementController@announcementList');

    Route::group(['prefix' => 'attendance'], function () {
        Route::get('live','ApiEmployeeAttendanceController@liveAttendance');
        Route::post('live','ApiEmployeeAttendanceController@storeLiveAttendance');
        Route::any('histories','ApiEmployeeAttendanceController@histories');
    });
    Route::group(['prefix' => 'reimbursement'], function () {
        Route::post('create','ApiEmployeeReimbursementController@create');
        Route::post('detail','ApiEmployeeReimbursementController@detail');
        Route::post('update','ApiEmployeeReimbursementController@update');
        Route::get('name_reimbursement','ApiEmployeeReimbursementController@name_reimbursement');
        Route::post('saldo_reimbursement','ApiEmployeeReimbursementController@saldo_reimbursement');
        Route::post('pending','ApiEmployeeReimbursementController@pending');
        Route::post('history','ApiEmployeeReimbursementController@history');
    });
    Route::group(['prefix' => 'office'], function () {
        Route::get('/total-employee','ApiEmployeeProfileController@total_employee');
        Route::get('/list-employee','ApiEmployeeProfileController@list_employee');
        Route::get('/cuti-employee','ApiEmployeeProfileController@cuti_employee');
        Route::post('/detail-employee','ApiEmployeeProfileController@detail_employee');
    });
    Route::group(['prefix' => 'profile'], function () {
        Route::get('info','ApiEmployeeProfileController@info');
        Route::get('payroll','ApiEmployeeProfileController@payroll');
        Route::get('ketenagakerjaan','ApiEmployeeProfileController@ketenagakerjaan');
        Route::get('emergency-contact','ApiEmployeeProfileController@emergency_contact');
        Route::post('update_pin','ApiEmployeeProfileController@update_pin');
        Route::group(['prefix' => 'file'], function () {
            Route::get('','ApiEmployeeProfileController@file');
            Route::get('category','ApiEmployeeProfileController@category_file');
            Route::post('create','ApiEmployeeProfileController@create_file');
            Route::post('detail','ApiEmployeeProfileController@detail_file');
            Route::post('update','ApiEmployeeProfileController@update_file');
            Route::post('delete','ApiEmployeeProfileController@delete_file');
        });
        Route::group(['prefix' => 'perubahan-data'], function () {
            Route::get('category','ApiEmployeeProfileController@category_perubahan_data');
            Route::post('create','ApiEmployeeProfileController@create_perubahan_data');
        });
        Route::group(['prefix' => 'faq'], function(){
            Route::post('/', 'ApiEmployeeProfileController@faq');
            Route::post('/terpopuler', 'ApiEmployeeProfileController@faq_terpopuler');
        });
        Route::group(['prefix' => 'privacy-policy'], function(){
            Route::get('/', 'ApiEmployeeProfileController@privacy_policy');
        });
        Route::post('reminder','ApiEmployeeProfileController@reminderAttendance');
        Route::get('reminder-cron','ApiEmployeeProfileController@cronReminder');
    });
    Route::post('update-device','ApiEmployeeAppController@saveDeviceUser');
    Route::get('logged-user','ApiEmployeeAppController@loggedUser');

    Route::group(['prefix' => 'time-off'], function () {
        Route::post('/','ApiEmployeeTimeOffOvertimeController@listTimeOffEmployee');
        Route::get('create','ApiEmployeeTimeOffOvertimeController@createTimeOffEmployee');
        Route::post('create','ApiEmployeeTimeOffOvertimeController@storeTimeOffEmployee');
    });

    Route::group(['prefix' => 'overtime'], function () {
        Route::post('/','ApiEmployeeTimeOffOvertimeController@listOvertimeEmployee');
        Route::get('create','ApiEmployeeTimeOffOvertimeController@createOvertimeEmployee');
        Route::post('check','ApiEmployeeTimeOffOvertimeController@checkOvertimeEmployee');
        Route::post('create','ApiEmployeeTimeOffOvertimeController@storeOvertimeEmployee');
    });

    Route::post('calender','ApiEmployeeController@calender');
    Route::group(['prefix' => 'attendance-outlet'], function () {
        Route::post('list-outlet','ApiEmployeeAttendaceOutletController@listOutlet');
        Route::post('live_1','ApiEmployeeAttendaceOutletController@liveAttendance');
        Route::post('live_2','ApiEmployeeAttendaceOutletController@storeLiveAttendance');
        Route::any('histories','ApiEmployeeAttendaceOutletController@histories');
    });
    Route::group(['prefix' => 'asset-inventory'], function () {
        Route::post('category','ApiEmployeeAssetInventoryController@category_asset');
        Route::post('available','ApiEmployeeAssetInventoryController@available_asset');
        Route::post('history','ApiEmployeeAssetInventoryController@history');
        Route::group(['prefix' => 'loan'], function () {
            Route::post('create','ApiEmployeeAssetInventoryController@create_loan');
            Route::post('list','ApiEmployeeAssetInventoryController@loan_asset');
            Route::post('detail','ApiEmployeeAssetInventoryController@detail_loan');
        });
        Route::group(['prefix' => 'return'], function () {
            Route::post('loan','ApiEmployeeAssetInventoryController@loan_list_return');
            Route::post('create','ApiEmployeeAssetInventoryController@create_return');
        });
    });

    Route::group(['prefix' => 'attendance-request'], function () {
        Route::post('check','ApiEmployeeAttendanceController@checkDateRequest');
        Route::post('request','ApiEmployeeAttendanceController@storeRequest');
        Route::any('histories','ApiEmployeeAttendanceController@historiesRequest');
    });
    Route::group(['prefix' => 'attendance-outlet-request'], function () {
        Route::post('check','ApiEmployeeAttendaceOutletController@checkDateRequest');
        Route::post('request','ApiEmployeeAttendaceOutletController@storeRequest');
        Route::any('histories','ApiEmployeeAttendaceOutletController@historiesRequest');
    });

    Route::group(['prefix' => 'inbox'], function () {
        Route::post('/', 'ApiEmployeeInboxController@listInbox');
        Route::post('approval', 'ApiEmployeeInboxController@listReqApproval');
    });

});

Route::group([ 'middleware' => ['auth_client', 'scopes:employee-apps'], 'prefix' => 'employee'], function () {
    Route::get('splash','ApiEmployeeAppController@splash');
});
