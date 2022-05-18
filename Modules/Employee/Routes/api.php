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
        Route::post('detail', 'ApiBeEmployeeController@detail');
        Route::post('create', 'ApiBeEmployeeController@create');
        Route::post('candidate', 'ApiBeEmployeeController@candidate');
        Route::post('detail', 'ApiBeEmployeeController@candidateDetail');
        Route::post('update', 'ApiBeEmployeeController@update');
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
      });
     Route::group(['prefix' => 'privacy-policy'], function(){
        Route::post('/', 'ApiBeEmployeeProfileController@privacy_policy');
        Route::post('/update', 'ApiBeEmployeeProfileController@privacy_policy_update');
      });
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
            Route::post('/detail', 'ApiEmployeeProfileController@detail_faq');
        });
        Route::group(['prefix' => 'privacy-policy'], function(){
            Route::get('/', 'ApiEmployeeProfileController@privacy_policy');
      });
    });
    Route::post('update-device','ApiEmployeeController@saveDeviceUser');

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
});