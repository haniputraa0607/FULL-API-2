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
        Route::post('delete-custom-link', 'ApiBeEmployeeController@deleteCustomLink');
        Route::post('add-custom-link', 'ApiBeEmployeeController@addCustomLink');
        Route::post('update', 'ApiBeEmployeeController@update');
        Route::post('complement', 'ApiBeEmployeeController@complement');
        Route::post('evaluation', 'ApiBeEmployeeController@employeeEvaluation');
        Route::post('evaluation/delete', 'ApiBeEmployeeController@employeeEvaluationDelete');
        Route::post('manager', 'ApiBeEmployeeController@manager');
        Route::post('cron', 'ApiBeEmployeeController@cronInputFormEval');
        Route::post('create-business-partner', 'ApiBeEmployeeController@createBusinessPartner');
    });
    Route::group(['prefix' => 'be/question'], function(){
        Route::post('category', 'ApiQuestionEmployeeController@category');
        Route::post('create', 'ApiQuestionEmployeeController@create');
    });
    Route::group(['prefix' => 'be/income'], function(){
        Route::post('/cron_end', 'ApiIncome@cron_end');
        Route::post('/', 'ApiBeIncomeController@index');
        Route::post('/outlet', 'ApiBeIncomeController@outlet');
        Route::post('/detail', 'ApiBeIncomeController@detail');
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
        Route::post('/detail', 'ApiBeEmployeeProfileController@detail_perubahan_data');
        Route::post('/list', 'ApiBeEmployeeProfileController@perubahan_data_list');
        Route::post('/update', 'ApiBeEmployeeProfileController@update_perubahan_data');
        Route::get('/users-column', 'ApiBeEmployeeProfileController@getColumn');
        Route::get('/category', 'ApiBeEmployeeProfileController@categoryUpdateData');
        Route::post('/category/create', 'ApiBeEmployeeProfileController@createCategoryUpdateData');
      });
     Route::group(['prefix' => 'faq'], function(){
        Route::post('/', 'ApiBeEmployeeProfileController@faq');
        Route::post('/create', 'ApiBeEmployeeProfileController@create_faq');
        Route::post('/detail', 'ApiBeEmployeeProfileController@detail_faq');
        Route::post('/update', 'ApiBeEmployeeProfileController@update_faq');
        Route::post('/delete', 'ApiBeEmployeeProfileController@delete_faq');
        Route::post('/popular', 'ApiBeEmployeeProfileController@faq_popular');
        Route::post('/popular/create', 'ApiBeEmployeeProfileController@create_faq_popular');
        Route::post('/popular/delete', 'ApiBeEmployeeProfileController@delete_faq_popular');
      });
     Route::group(['prefix' => 'privacy-policy'], function(){
        Route::get('/', 'ApiBeEmployeeProfileController@privacy_policy');
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
            Route::post('/detail', 'ApiBeEmployeeAssetInventoryController@detail_loan');
        });
        Route::group(['prefix' => 'return'], function(){
            Route::get('/pending', 'ApiBeEmployeeAssetInventoryController@list_return_pending');
            Route::get('/list', 'ApiBeEmployeeAssetInventoryController@list_return');
            Route::post('/approve', 'ApiBeEmployeeAssetInventoryController@approve_return');
            Route::post('/detail', 'ApiBeEmployeeAssetInventoryController@detail_return');
        });
       
        Route::post('/create', 'ApiBeEmployeeAssetInventoryController@create');
        Route::post('/delete', 'ApiBeEmployeeAssetInventoryController@delete');
        Route::post('/list', 'ApiBeEmployeeAssetInventoryController@list');
    });
    Route::any('attendance-setting','ApiEmployeeAttendanceController@setting');
    Route::group(['prefix' => 'attendance'], function () {
        Route::post('list','ApiEmployeeAttendanceController@list');
        Route::post('detail','ApiEmployeeAttendanceController@detail');
        Route::post('delete','ApiEmployeeAttendanceController@delete');
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
        Route::post('delete','ApiEmployeeAttendaceOutletController@delete');
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
        Route::post('reject', 'ApiEmployeeTimeOffOvertimeController@rejectTimeOff');
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
        Route::post('reject', 'ApiEmployeeTimeOffOvertimeController@rejectOvertime');
    });

    Route::group(['prefix' => 'change-shift'], function () {	
        Route::post('list', 'ApiEmployeeChangeShiftController@listChangeShift');	
        Route::post('delete', 'ApiEmployeeChangeShiftController@deleteChangeShift');	
        Route::post('detail', 'ApiEmployeeChangeShiftController@detailChangeShift');	
        Route::post('update', 'ApiEmployeeChangeShiftController@updateChangeShift');	
        Route::post('list-date', 'ApiEmployeeChangeShiftController@listDate');	
        Route::post('list-shift', 'ApiEmployeeChangeShiftController@listShift');	
    });	
    Route::group(['prefix' => 'design-request'], function () {	
        Route::post('list', 'ApiDesignRequestController@listDesignRequest');	
        Route::post('create', 'ApiDesignRequestController@storeDesignRequest');	
        Route::post('update', 'ApiDesignRequestController@updateDesignRequest');	
        Route::post('detail', 'ApiDesignRequestController@detailDesignRequest');	
        Route::post('delete', 'ApiEmployeeTimeOffOvertimeController@deleteOvertime');	
    });
    
    Route::group(['prefix' => 'be/reimbursement'], function () {
        Route::post('/','ApiBeEmployeeReimbursementController@index');
        Route::post('/manager','ApiBeEmployeeReimbursementController@manager');
        Route::post('/update','ApiBeEmployeeReimbursementController@update');
         Route::post('/reject','ApiBeEmployeeReimbursementController@reject');
        Route::post('/manager/detail','ApiBeEmployeeReimbursementController@detail');
        Route::post('/list','ApiBeEmployeeReimbursementController@list');
        Route::post('/detail','ApiBeEmployeeReimbursementController@detail');
        Route::post('/approved','ApiBeEmployeeReimbursementController@approved');
        Route::post('/dropdown','ApiBeEmployeeReimbursementController@dropdown');
        Route::post('/list_dropdown','ApiBeEmployeeReimbursementController@list_dropdown');
        Route::post('/dropdown/create','ApiBeEmployeeReimbursementController@create_dropdown');
        Route::post('/dropdown/update','ApiBeEmployeeReimbursementController@update_dropdown');
        Route::post('/dropdown/detail','ApiBeEmployeeReimbursementController@detail_dropdown');
        Route::post('/dropdown/delete','ApiBeEmployeeReimbursementController@delete_dropdown');
         });
    Route::group(['prefix' => 'be/cash-advance'], function () {
        Route::post('/','ApiBeEmployeeCashAdvanceController@index');
        Route::post('/manager','ApiBeEmployeeCashAdvanceController@manager');
        Route::post('/update','ApiBeEmployeeCashAdvanceController@update');
        Route::post('/manager/detail','ApiBeEmployeeCashAdvanceController@detail');
        Route::post('/list','ApiBeEmployeeCashAdvanceController@list');
        Route::post('/detail','ApiBeEmployeeCashAdvanceController@detail');
        Route::post('/approved','ApiBeEmployeeCashAdvanceController@approved');
        Route::post('/reject','ApiBeEmployeeCashAdvanceController@reject');
        Route::post('/icount','ApiBeEmployeeCashAdvanceController@icount');
        Route::post('/dropdown','ApiBeEmployeeCashAdvanceController@dropdown');
        Route::post('/list_dropdown','ApiBeEmployeeCashAdvanceController@list_dropdown');
        Route::post('/dropdown/create','ApiBeEmployeeCashAdvanceController@create_dropdown');
        Route::post('/dropdown/delete','ApiBeEmployeeCashAdvanceController@delete_dropdown');
         });
    Route::group(['prefix' => 'role'], function () {
            Route::any('/', ['middleware' => 'feature_control:393','uses' =>'ApiRoleController@index']);
            Route::post('detail', ['middleware' => 'feature_control:396','uses' =>'ApiRoleController@detail']);
            
            //incentive
            Route::any('list-default-incentive', ['middleware' => 'feature_control:396','uses' =>'ApiRoleController@list_default_incentive']);
            Route::any('list-default-salary-cut', ['middleware' => 'feature_control:396','uses' =>'ApiRoleController@list_default_salary_cut']);
            Route::post('/basic-salary', ['middleware' => 'feature_control:393','uses' =>'ApiRoleController@basic_salary']);
            Route::post('/basic-salary-create', ['middleware' => 'feature_control:393','uses' =>'ApiRoleController@basic_salary_create']);
            
            Route::post('/reimbursement', ['middleware' => 'feature_control:393','uses' =>'ApiRoleController@reimbursement']);
            Route::post('/reimbursement-create', ['middleware' => 'feature_control:393','uses' =>'ApiRoleController@reimbursement_create']);
            
       });
    Route::group(['prefix' => 'role/overtime'], function () {
           Route::post('create', ['middleware' => 'feature_control:394','uses' =>'ApiOvertimeController@create']);
           Route::post('update', ['middleware' => 'feature_control:395','uses' =>'ApiOvertimeController@update']);
           Route::post('detail', ['middleware' => 'feature_control:395','uses' =>'ApiOvertimeController@detail']);
           Route::post('delete', ['middleware' => 'feature_control:395','uses' =>'ApiOvertimeController@delete']);

           Route::post('/', ['middleware' => 'feature_control:395','uses' =>'ApiOvertimeController@index']);

           Route::post('default/', ['middleware' => 'feature_control:426','uses' =>'ApiOvertimeController@index_default']);
           Route::post('default/create', ['middleware' => 'feature_control:426','uses' =>'ApiOvertimeController@create_default']);
           Route::post('default/update', ['middleware' => 'feature_control:426','uses' =>'ApiOvertimeController@update_default']);
           Route::post('default/detail', ['middleware' => 'feature_control:426','uses' =>'ApiOvertimeController@detail_default']);
           Route::post('default/delete', ['middleware' => 'feature_control:426','uses' =>'ApiOvertimeController@delete_default']);
       });
    Route::group(['prefix' => 'role/fixed-incentive'], function () {
                Route::post('create', ['middleware' => 'feature_control:394','uses' =>'ApiFixedIncentiveController@create']);
                Route::post('update', ['middleware' => 'feature_control:395','uses' =>'ApiFixedIncentiveController@update']);
                Route::post('detail', ['middleware' => 'feature_control:395','uses' =>'ApiFixedIncentiveController@detail']);
                Route::post('delete', ['middleware' => 'feature_control:395','uses' =>'ApiFixedIncentiveController@delete']);
               
                Route::post('/', ['middleware' => 'feature_control:395','uses' =>'ApiFixedIncentiveController@index']);
                
                Route::post('default/', ['middleware' => 'feature_control:426','uses' =>'ApiFixedIncentiveController@index_default']);
                Route::post('default/create', ['middleware' => 'feature_control:426','uses' =>'ApiFixedIncentiveController@create_default']);
                Route::post('default/update', ['middleware' => 'feature_control:426','uses' =>'ApiFixedIncentiveController@update_default']);
                Route::post('default/detail', ['middleware' => 'feature_control:426','uses' =>'ApiFixedIncentiveController@detail_default']);
                Route::post('default/delete', ['middleware' => 'feature_control:426','uses' =>'ApiFixedIncentiveController@delete_default']);
                Route::post('default/detail/list', ['middleware' => 'feature_control:426','uses' =>'ApiFixedIncentiveController@index_default_detail']);
                Route::post('default/type1', ['middleware' => 'feature_control:426','uses' =>'ApiFixedIncentiveController@type1']);
                Route::post('default/type2', ['middleware' => 'feature_control:426','uses' =>'ApiFixedIncentiveController@type2']);
                Route::post('default/detail/delete', ['middleware' => 'feature_control:426','uses' =>'ApiFixedIncentiveController@delete_detail']);
            });
    Route::group(['prefix' => 'role/incentive'], function () {
                Route::post('/', ['middleware' => 'feature_control:395','uses' =>'ApiIncentiveController@index']);
                Route::post('create', ['middleware' => 'feature_control:394','uses' =>'ApiIncentiveController@create']);
                Route::post('update', ['middleware' => 'feature_control:395','uses' =>'ApiIncentiveController@update']);
                Route::post('detail', ['middleware' => 'feature_control:395','uses' =>'ApiIncentiveController@detail']);
                Route::post('delete', ['middleware' => 'feature_control:395','uses' =>'ApiIncentiveController@delete']);
                Route::post('list_incentive', ['middleware' => 'feature_control:395','uses' =>'ApiIncentiveController@list_incentive']);
                Route::post('list-rumus-incentive', ['middleware' => 'feature_control:395','uses' =>'ApiIncentiveController@list_rumus_incentive']);
                Route::post('default/', ['middleware' => 'feature_control:425','uses' =>'ApiIncentiveController@index_default']);
                Route::post('default/create', ['middleware' => 'feature_control:425','uses' =>'ApiIncentiveController@create_default']);
                Route::post('default/update', ['middleware' => 'feature_control:425','uses' =>'ApiIncentiveController@update_default']);
                Route::post('default/detail', ['middleware' => 'feature_control:425','uses' =>'ApiIncentiveController@detail_default']);
                Route::post('default/delete', ['middleware' => 'feature_control:425','uses' =>'ApiIncentiveController@delete_default']);
            });
    Route::group(['prefix' => 'role/salary-cut'], function () {
                Route::post('/', ['middleware' => 'feature_control:395','uses' =>'ApiSalaryCutController@index']);
                Route::post('create', ['middleware' => 'feature_control:394','uses' =>'ApiSalaryCutController@create']);
                Route::post('update', ['middleware' => 'feature_control:395','uses' =>'ApiSalaryCutController@update']);
                Route::post('detail', ['middleware' => 'feature_control:395','uses' =>'ApiSalaryCutController@detail']);
                Route::post('delete', ['middleware' => 'feature_control:395','uses' =>'ApiSalaryCutController@delete']);
                Route::post('list_salary_cut', ['middleware' => 'feature_control:395','uses' =>'ApiSalaryCutController@list_salary_cut']);
                Route::post('list-rumus-salary_cut', ['middleware' => 'feature_control:395','uses' =>'ApiSalaryCutController@list_rumus_salary_cut']);
                Route::post('default/', ['middleware' => 'feature_control:425','uses' =>'ApiSalaryCutController@index_default']);
                Route::post('default/create', ['middleware' => 'feature_control:425','uses' =>'ApiSalaryCutController@create_default']);
                Route::post('default/update', ['middleware' => 'feature_control:425','uses' =>'ApiSalaryCutController@update_default']);
                Route::post('default/detail', ['middleware' => 'feature_control:425','uses' =>'ApiSalaryCutController@detail_default']);
                Route::post('default/delete', ['middleware' => 'feature_control:425','uses' =>'ApiSalaryCutController@delete_default']);
            });
    Route::group(['prefix' => 'loan'], function () {
        Route::post('category/create', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiLoanController@createCategory']);
        Route::post('category/list', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiLoanController@listCategory']);
        Route::post('category/delete', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiLoanController@deleteCategory']);
        Route::post('hs', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiLoanController@hs']);
        Route::post('create', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiLoanController@create']);
        Route::post('/', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiLoanController@index']);
        Route::post('/sales', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiLoanController@index_sales_payment']);
        Route::post('/sales/detail', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiLoanController@detail_sales_payment']);
        Route::post('/sales/create', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiLoanController@create_sales_payment']);
    });

    Route::group(['prefix' => 'request'], function()
    {
        Route::post('list', ['middleware' => 'feature_control:537', 'uses' => 'ApiRequestEmployeeController@index']);
        Route::post('create', ['middleware' => 'feature_control:537', 'uses' => 'ApiRequestEmployeeController@store']);
        Route::post('detail', ['middleware' => 'feature_control:539', 'uses' => 'ApiRequestEmployeeController@detail']);
        Route::post('update', ['middleware' => 'feature_control:540', 'uses' => 'ApiRequestEmployeeController@update']);
        Route::post('delete', ['middleware' => 'feature_control:540', 'uses' => 'ApiRequestEmployeeController@delete']);
        Route::post('finish', ['middleware' => 'feature_control:540', 'uses' => 'ApiRequestEmployeeController@finish']);
        Route::post('list-employee', ['middleware' => 'feature_control:539', 'uses' => 'ApiRequestEmployeeController@listEmployeeOutlet']);
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
    Route::group(['prefix' => 'cash-advance'], function () {
        Route::post('create','ApiEmployeeCashAdvanceController@create');
        Route::post('detail','ApiEmployeeCashAdvanceController@detail');
        Route::post('update','ApiEmployeeCashAdvanceController@update');
        Route::post('pending','ApiEmployeeCashAdvanceController@pending');
        Route::post('history','ApiEmployeeCashAdvanceController@history');
         Route::get('name','ApiEmployeeCashAdvanceController@name_cash_advance');
    });
    Route::group(['prefix' => 'office'], function () {
        Route::get('/total-employee','ApiEmployeeProfileController@total_employee');
        Route::get('/list-employee','ApiEmployeeProfileController@list_employee');
        Route::get('/cuti-employee','ApiEmployeeProfileController@cuti_employee');
        Route::post('/detail-employee','ApiEmployeeProfileController@detail_employee');
    });
    Route::group(['prefix' => 'payslip'], function () {
        Route::post('/','ApiIncome@payslip');
        Route::post('/password','ApiIncome@password');
    });
    Route::group(['prefix' => 'emergency'], function(){
        Route::post('/create', 'ApiEmployeeProfileController@create_emergency_contact');
        Route::post('/detail', 'ApiEmployeeProfileController@detail_emergency_contact');
        Route::post('/update', 'ApiEmployeeProfileController@update_emergency_contact');
        Route::post('/delete', 'ApiEmployeeProfileController@delete_emergency_contact');
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
        Route::get('reminder','ApiEmployeeProfileController@getReminderAttendance');
        Route::post('reminder','ApiEmployeeProfileController@reminderAttendance');
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

    Route::group(['prefix' => 'change-shift'], function () {	
        Route::any('/','ApiEmployeeChangeShiftController@index');	
        Route::post('/date','ApiEmployeeChangeShiftController@sendDate');	
        Route::any('/create','ApiEmployeeChangeShiftController@create');	
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
        Route::post('history/detail','ApiEmployeeAssetInventoryController@detail_history');
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
        Route::get('/', 'ApiEmployeeInboxController@getListInbox');
        Route::post('/', 'ApiEmployeeInboxController@listInbox');
        Route::post('read', 'ApiEmployeeInboxController@inboxRead');
        Route::get('approval', 'ApiEmployeeInboxController@getListReqApproval');
        Route::post('approval', 'ApiEmployeeInboxController@listReqApproval');
        Route::post('approval-detail', 'ApiEmployeeInboxController@listReqApproval');
        Route::post('approval-approve', 'ApiEmployeeInboxController@approveReqApproval');
        
        
        Route::any('history', 'ApiEmployeeInboxController@history');
        Route::get('category', 'ApiEmployeeInboxController@categoryHistory');
    });

    Route::group(['prefix' => 'req-product'], function () {
        Route::get('/','ApiEmployeeRequestProductController@createRequest');    
        Route::post('list-catalog','ApiEmployeeRequestProductController@listCatalog');    
        Route::post('list-product','ApiEmployeeRequestProductController@listProduct');    
        Route::post('/','ApiEmployeeRequestProductController@storeRequest');    
    });
});

 Route::group([ 'middleware' => ['log_activities_employee_apps','auth_client', 'scopes:employee-apps'], 'prefix' => 'employee'], function () {
     Route::get('splash','ApiEmployeeAppController@splash');
 });

Route::group(['prefix' => '/icount/reimbursement'], function() {
    Route::post('/callback','ApiBeEmployeeReimbursementController@callbackreimbursement')->middleware('auth_pos2:PurchaseInvoiceID,status,date_disburse');
});
Route::group(['prefix' => '/icount/cash-advance'], function() {
    Route::post('/callback','ApiBeEmployeeCashAdvanceController@callbackcash_advance')->middleware('auth_pos2:PurchaseDepositRequestID,status,date_disburse');
});


Route::group(['prefix' => '/icount/budgeting'], function() {
    Route::post('/store','ApiEmployeeRequestProductController@storeBudgeting')->middleware('auth_pos2:DepartmentID');
});