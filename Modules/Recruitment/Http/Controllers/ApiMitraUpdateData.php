<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;

use Modules\Franchise\Entities\TransactionProduct;
use Modules\Outlet\Entities\OutletTimeShift;

use Modules\Recruitment\Entities\HairstylistLogBalance;
use Modules\Recruitment\Entities\OutletCash;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistInbox;

use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\UserRating\Entities\UserRatingSummary;
use App\Http\Models\Transaction;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Entities\OutletCashAttachment;

use App\Lib\MyHelper;
use DB;
use DateTime;
use DateTimeZone;
use Modules\Users\Http\Requests\users_forgot;
use Modules\Users\Http\Requests\users_phone_pin_new_v2;
use PharIo\Manifest\EmailTest;
use Auth;

class ApiMitraUpdateData extends Controller
{
    public function __construct() {
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }

    public function listField(Request $request)
    {
    	return MyHelper::checkGet(
	        [
	            'field_list' => [
	                [
	                    'text' => 'Nama',
	                    'value' => 'name',
	                ],
	                [
	                    'text' => 'Nomor Telepon',
	                    'value' => 'phone_number',
	                ],
	                [
	                    'text' => 'Email',
	                    'value' => 'email',
	                ],
	                [
	                    'text' => 'Alamat',
	                    'value' => 'address',
	                ],
	                [
	                    'text' => 'Nomor Rekening',
	                    'value' => 'account_number',
	                ]
	            ]
	        ]
	    );
    }

    public function updateRequest(Request $request)
    {
    	$request->validate([
            'field' => 'string|required',
            'new_value' => 'string|required',
            'notes' => 'string|sometimes|nullable',
        ]);
        
        return [
            'status' => 'success',
            'result' => [
                'message' => 'Permintaan perubahan data berhasil dikirim'
            ]
        ];
    }
}
