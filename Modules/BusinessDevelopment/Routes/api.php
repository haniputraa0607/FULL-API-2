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

Route::middleware('auth:api')->get('/businessdevelopment', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth:api','log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'outlet-starter-bundling'], function() {
    Route::get('/', 'ApiOutletStarterBundlingController@index');
    Route::post('/create', 'ApiOutletStarterBundlingController@store');
    Route::post('/detail', 'ApiOutletStarterBundlingController@show');
    Route::post('/update', 'ApiOutletStarterBundlingController@update');
    Route::post('/delete', 'ApiOutletStarterBundlingController@delete');
    Route::post('/icount-product', 'ApiOutletStarterBundlingController@productIcountList');
});

Route::group(['middleware' => ['auth:api','log_activities', 'user_agent'],'prefix' => 'partners'], function() {
    Route::any('/', ['middleware'=>['feature_control:338','scopes:be'],'uses' => 'ApiPartnersController@index']);
    Route::post('/delete', ['middleware'=>['feature_control:341','scopes:be'],'uses' => 'ApiPartnersController@destroy']);
    Route::post('/edit', ['middleware'=>['feature_control:339','scopes:be'],'uses' => 'ApiPartnersController@edit']);
    Route::post('/update', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@update']);
    Route::post('/cek-duplikat', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@cekDuplikat']);
    Route::post('/create-follow-up', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@followUp']);
    Route::post('/new-follow-up', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@followUpNewLoc']);
    Route::post('/pdf', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@pdf']);
    Route::post('/tesIcount', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@tesIcount']);
    Route::any('/list-location', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@listLocationAvailable']);
    Route::post('/detail-bundling', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@detailBundling']);
    Route::any('/term', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@term']);
    Route::any('/generate-spk', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@generateSPK']);
    Route::group(['prefix' => '/locations'], function() {
        Route::any('/', ['middleware'=>['feature_control:342','scopes:be'],'uses' => 'ApiLocationsController@index']);
        Route::post('/create', ['middleware'=>'scopes:franchise-user','uses' => 'ApiLocationsController@store']);
        Route::post('/delete', ['middleware'=>['feature_control:345','scopes:be'],'uses' => 'ApiLocationsController@destroy']);
        Route::post('/edit', ['middleware'=>['feature_control:343','scopes:be'],'uses' => 'ApiLocationsController@edit']);
        Route::post('/update', ['middleware'=>['feature_control:344','scopes:be'],'uses' => 'ApiLocationsController@update']);
        Route::get('/brands', ['middleware'=>['feature_control:344','scopes:be'],'uses' => 'ApiLocationsController@brandsList']);
        Route::post('/create-follow-up', ['middleware'=>['feature_control:344','scopes:be'],'uses' => 'ApiLocationsController@followUp']);
        Route::post('/new-status', ['middleware'=>['feature_control:344','scopes:be'],'uses' => 'ApiLocationsController@newStatusLogs']);
        // Route::post('/create-follow-up', ['middleware'=>['feature_control:344','scopes:be'],'uses' => 'ApiPartnersController@pdfSurvey']);
    });
    Route::group(['prefix' => '/bankaccount'], function() {
        Route::post('/detail', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiBankAccountsController@detail']);
        Route::post('/update', ['middleware'=>['feature_control:352','scopes:be'],'uses' => 'ApiBankAccountsController@update']);
    });
    Route::group(['prefix' => '/request-update'], function() {
        Route::any('/', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@listPartnersLogs']);
        Route::post('/delete', ['middleware'=>['feature_control:341','scopes:be'],'uses' => 'ApiPartnersController@deletePartnersLogs']);
        Route::post('/detail', ['middleware'=>['feature_control:339','scopes:be'],'uses' => 'ApiPartnersController@detailPartnersLogs']);
    });
    Route::group(['prefix' => '/confirmation-letter'], function() {
        Route::post('/create', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@createConfirLetter']);
    });
    Route::group(['prefix' => '/form-survey'], function() {
        Route::post('/', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@formSurvey']);
        Route::any('/all', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@allFormSurvey']);
        Route::post('/store', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@storeFormSurvey']);
        Route::post('/create', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@createFormSurvey']);
        Route::post('/pdf', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@pdfSurvey']);
        Route::get('/list', ['middleware'=>['feature_control:340','scopes:be'],'uses' => 'ApiPartnersController@listFormSurvey']);
    });
    
    //Close Temporary Partners
    Route::group(['prefix' => '/close-temporary'], function() {
        Route::post('/', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@index']);
        Route::post('/cronInactive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@cronInactive']);
        Route::post('/cronActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@cronActive']);
        Route::post('/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@create']);
        Route::post('/update', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@update']);
        Route::post('/createActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@create_active']);
        Route::post('/updateActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@update_active']);
        Route::post('/detail', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@detail']);
        Route::post('/submit', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@submit']);
        Route::post('/reject', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@reject']);
        Route::post('/success', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@success']);
        Route::post('/successActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@successActive']);
        Route::post('/closeTemporary', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@closeTemporary']);
        Route::post('/temporary', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@temporary']);
        Route::post('/lampiran/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@lampiranCreate']);
        Route::post('/lampiran/delete', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@lampiranDelete']);
        Route::post('/lampiran/data', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersCloseController@lampiranData']);
    });
    Route::group(['prefix' => '/close-permanent'], function() {
        Route::post('/', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@index']);
        Route::post('/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@create']);
        Route::post('/createActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@create_active']);
        Route::post('/closePermanent', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@closePermanent']);
        Route::post('/detail', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@detail']);
        Route::post('/reject', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@reject']);
        Route::post('/permanent', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@permanent']);
        Route::post('/cronInactive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@cronInactive']);
        Route::post('/update', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@update']);
        Route::post('/updateActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@update_active']);
        Route::post('/success', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@success']);
        Route::post('/successActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@successActive']);
        Route::post('/lampiran/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@lampiranCreate']);
        Route::post('/lampiran/delete', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@lampiranDelete']);
        Route::post('/lampiran/data', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnerClosePermanentController@lampiranData']);
    });
    Route::group(['prefix' => '/becomes-ixobox'], function() {
        Route::post('/', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@index']);
        Route::post('/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@create']);
        Route::post('/createActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@create_active']);
        Route::post('/becomesIxobox', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@becomesIxobox']);
        Route::post('/detail', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@detail']);
        Route::post('/reject', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@reject']);
        Route::post('/becomes', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@becomes']);
        Route::post('/cronBecomeIxobox', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@cronBecomeIxobox']);
        Route::post('/update', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@update']);
        Route::post('/updateActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@update_active']);
        Route::post('/success', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@success']);
        Route::post('/successActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@successActive']);
        Route::post('/lampiran/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@lampiranCreate']);
        Route::post('/lampiran/delete', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@lampiranDelete']);
        Route::post('/lampiran/data', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiPartnersBecomesIxoboxController@lampiranData']);
    });
    Route::group(['prefix' => '/outlet'], function() {
        Route::post('/', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@index']);
        Route::post('/detail', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@detail']);
        Route::post('/ready', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@ready']);
        Route::post('/active', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@active']);
        Route::post('/partner', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@partner']);
        Route::group(['prefix' => '/cutoff'], function() {
            Route::post('/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@createCutOff']);
            Route::post('/update', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@updateCutOff']);
            Route::post('/detail', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@detailCutOff']);
            Route::post('/reject', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@rejectCutOff']);
            Route::post('/success', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@successCutOff']);
            Route::post('/cronCutOff', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@cronCutOff']);
            Route::post('/lampiran/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@lampiranCreateCutOff']);
            Route::post('/lampiran/delete', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@lampiranDeleteCutOff']);
            Route::post('/lampiran/data', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@lampiranDataCutOff']);
        });
        Route::group(['prefix' => '/change'], function() {
            Route::post('/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@createChange']);
            Route::post('/update', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@updateChange']);
            Route::post('/detail', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@detailChange']);
            Route::post('/reject', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@rejectChange']);
            Route::post('/success', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@successChange']);
            Route::post('/cronChange', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@cronChange']);
            Route::post('/lampiran/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@lampiranCreateChange']);
            Route::post('/lampiran/delete', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@lampiranDeleteChange']);
            Route::post('/lampiran/data', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseController@lampiranDataChange']);
        });
        Route::group(['prefix' => '/change_location'], function() {
            Route::post('/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@create']);
            Route::post('/update', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@update']);
            Route::post('/detail', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@detail']);
            Route::post('/reject', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@reject']);
            Route::post('/success', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@success']);
            Route::post('/cron', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@cron']);
             //step
            Route::post('/updatestepstatus', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@update_step_status']);
            Route::post('/updatesteplog', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@update_step_log']);
            Route::post('/updateStatus', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@updateStatus']);
            Route::post('/create-follow-up', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@createFollowUp']);
            Route::post('/form-survey', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@createFormSurvey']);
            Route::post('/locations', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@updatelokasi']);
            Route::post('/confirmation-letter', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletChangeLocationController@createConfirLetter']);
        });
         Route::group(['prefix' => '/close'], function() {
            Route::post('/', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@index']);
            Route::post('/index', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@indexClose']);
            Route::post('/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@createClose']);
            Route::post('/update', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@updateClose']);
            Route::post('/updateActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@updateCloseActive']);
            Route::post('/detail', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@detailClose']);
            Route::post('/reject', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@rejectClose']);
            Route::post('/success', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@successClose']);
            Route::post('/lampiran/create', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@lampiranCreateClose']);
            Route::post('/lampiran/delete', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@lampiranDeleteClose']);
            Route::post('/lampiran/data', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@lampiranDataClose']);
            Route::post('/createActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@createActive']);
            Route::post('/cronClose', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@cronClose']);
            Route::post('/cronActive', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@cronActive']);
            Route::post('/cronChangeLocation', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@cronChangeLocation']);
            //active steps
            Route::post('/updatestepstatus', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@update_step_status']);
            Route::post('/updatesteplog', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@update_step_log']);
            Route::post('/updateStatus', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@updateStatus']);
            Route::post('/create-follow-up', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@createFollowUp']);
            Route::post('/form-survey', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@createFormSurvey']);
            Route::post('/locations', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@updatelokasi']);
            Route::post('/confirmation-letter', ['middleware'=>['feature_control:351','scopes:be'],'uses' => 'ApiOutletCloseTemporaryController@createConfirLetter']);
        });
    });
});

Route::group(['middleware' => ['auth:partners','log_activities','user_agent','scopes:partners'],'prefix' => 'partner'], function() {
    Route::get('/detailpartner', ['uses' => 'ApiPartnersController@detailByPartner']);
    Route::post('/updatepartner', ['uses' => 'ApiPartnersController@updateByPartner']);
    Route::post('/updatepassword', ['uses' => 'ApiPartnersController@passwordByPartner']);
    Route::post('/checkpassword', ['uses' => 'ApiPartnersController@checkPassword']);
    Route::get('/detailBank', ['uses' => 'ApiBankAccountsController@detailBankPartner']);
    Route::post('/updateBank', ['uses' => 'ApiBankAccountsController@updateBankPartner']);
    Route::any('/list-bank', ['uses' => 'ApiBankAccountsController@listBank']);
    Route::get('/status', ['uses' => 'ApiPartnersController@statusPartner']);
});

Route::group(['middleware' => ['auth_client','scopes:landing-page'],'prefix' => 'partners'], function() {
    Route::post('/create', ['uses' => 'ApiPartnersController@store']);
    Route::post('/create-location', ['uses' => 'ApiLocationsController@storeLandingPage']);
    Route::post('/new', ['uses' => 'ApiPartnersController@new']);
});