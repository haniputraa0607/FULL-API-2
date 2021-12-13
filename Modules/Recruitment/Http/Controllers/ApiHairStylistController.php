<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\UserHairStylistDocuments;
use Modules\Recruitment\Entities\HairstylistSchedule;	
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Outlet\Entities\OutletBox;
use App\Http\Models\LogOutletBox;
use Modules\Recruitment\Http\Requests\user_hair_stylist_create;
use Image;
use DB;

class ApiHairStylistController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->mitra 			= "Modules\Recruitment\Http\Controllers\ApiMitra";
    }
    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function create(user_hair_stylist_create $request)
    {
        $post = $request->json()->all();

        $phone = $request->json('phone_number');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $check = UserHairStylist::where('email', $post['email'])->orWhere('phone_number', $phone)->first();

        if(!empty($check)){
            return response()->json(['status' => 'fail', 'messages' => ['Email or phone already use']]);
        }

        if (isset($post['photo']) && !empty($post['photo'])) {
            $img = Image::make(base64_decode($post['photo']));
            $imgwidth = $img->width();
            $imgheight = $img->height();
            $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/hs/', $imgwidth, $imgheight, time());
            if ($upload['status'] == "success") {
                $post['user_hair_stylist_photo'] = $upload['path'];
            }
        }

        $dataCreate = [
            'level' => (empty($post['level']) ? null : $post['level']),
            'email' => $post['email'],
            'phone_number' => $phone,
            'fullname' => $post['fullname'],
            'gender' => $post['gender']??null,
            'nationality' => $post['nationality']??null,
            'birthplace' => $post['birthplace']??null,
            'birthdate' => date('Y-m-d', strtotime($post['birthdate']))??null,
            'religion' => $post['religion']??null,
            'height' => (empty($post['height']) ? 0 : $post['height']),
            'weight' => (empty($post['weight']) ? 0 : $post['weight']),
            'recent_job' => (empty($post['recent_job']) ? null : $post['recent_job']),
            'recent_company' => (empty($post['recent_company']) ? null : $post['recent_company']),
            'blood_type' => (empty($post['blood_type']) ? null : $post['blood_type']),
            'recent_address' => (empty($post['recent_address']) ? null : $post['recent_address']),
            'postal_code' => (empty($post['postal_code']) ? null : $post['postal_code']),
            'marital_status' => (empty($post['marital_status']) ? null : $post['marital_status']),
            'user_hair_stylist_status' => 'Candidate',
            'user_hair_stylist_photo' => $post['user_hair_stylist_photo']??null
        ];

        $create = UserHairStylist::create($dataCreate);
        if($create){
            $autocrm = app($this->autocrm)->SendAutoCRM(
                'Register Candidate Hair Stylist',
                $create['phone_number'],
                [
                    'fullname' => $create['fullname'],
                    'phone_number' => $create['phone_number'],
                    'email' => $create['email']
                ], null, false, false, 'hairstylist'
            );
        }
        return response()->json(MyHelper::checkCreate($create));
    }

    public function canditateList(Request $request){
        $post = $request->json()->all();

        $data = UserHairStylist::whereNotIn('user_hair_stylist_status', ['Active', 'Inactive'])->orderBy('created_at', 'desc');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('created_at', '>=', $start_date)
                ->whereDate('created_at', '<=', $end_date);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'email'){
                            if($row['operator'] == '='){
                                $data->where('email', $row['parameter']);
                            }else{
                                $data->where('email', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'phone_number'){
                            if($row['operator'] == '='){
                                $data->where('phone_number', $row['parameter']);
                            }else{
                                $data->where('phone_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'fullname'){
                            if($row['operator'] == '='){
                                $data->where('fullname', $row['parameter']);
                            }else{
                                $data->where('fullname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'gender'){
                            $data->where('gender', $row['operator']);
                        }
                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'email'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('email', $row['parameter']);
                                }else{
                                    $subquery->orWhere('email', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'phone_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('phone_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('phone_number', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'fullname'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('fullname', $row['parameter']);
                                }else{
                                    $subquery->orWhere('fullname', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'gender'){
                                $subquery->orWhere('gender', $row['operator']);
                            }
                        }
                    }
                });
            }
        }
        $data = $data->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    public function hsList(Request $request){
        $post = $request->json()->all();

        $data = UserHairStylist::leftJoin('users as approver', 'approver.id', 'user_hair_stylist.approve_by')
                ->whereIn('user_hair_stylist_status', ['Active', 'Inactive'])->with('outlet')->orderBy('join_date', 'desc');

        if(isset($post['date_start']) && !empty($post['date_start']) &&
            isset($post['date_end']) && !empty($post['date_end'])){
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('join_date', '>=', $start_date)
                ->whereDate('join_date', '<=', $end_date);
        }

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'nickname'){
                            if($row['operator'] == '='){
                                $data->where('nickname', $row['parameter']);
                            }else{
                                $data->where('nickname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'email'){
                            if($row['operator'] == '='){
                                $data->where('email', $row['parameter']);
                            }else{
                                $data->where('email', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'phone_number'){
                            if($row['operator'] == '='){
                                $data->where('phone_number', $row['parameter']);
                            }else{
                                $data->where('phone_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'fullname'){
                            if($row['operator'] == '='){
                                $data->where('fullname', $row['parameter']);
                            }else{
                                $data->where('fullname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'gender'){
                            $data->where('gender', $row['operator']);
                        }
                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'nickname'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('nickname', $row['parameter']);
                                }else{
                                    $subquery->orWhere('nickname', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'email'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('email', $row['parameter']);
                                }else{
                                    $subquery->orWhere('email', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'phone_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('phone_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('phone_number', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'fullname'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('fullname', $row['parameter']);
                                }else{
                                    $subquery->orWhere('fullname', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'gender'){
                                $subquery->orWhere('gender', $row['operator']);
                            }
                        }
                    }
                });
            }
        }
        $data = $data->select('user_hair_stylist.*', 'approver.name as approve_by_name')->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    public function detail(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_user_hair_stylist']) && !empty($post['id_user_hair_stylist'])){
            $detail = UserHairStylist::leftJoin('outlets', 'outlets.id_outlet', 'user_hair_stylist.id_outlet')
                        ->leftJoin('bank_accounts', 'bank_accounts.id_bank_account', 'user_hair_stylist.id_bank_account')
                        ->leftJoin('bank_name', 'bank_name.id_bank_name', 'bank_accounts.id_bank_name')
                        ->leftJoin('users as approver', 'approver.id', 'user_hair_stylist.approve_by')
                        ->where('id_user_hair_stylist', $post['id_user_hair_stylist'])
                        ->select('user_hair_stylist.*', 'outlets.outlet_name', 'outlets.outlet_code', 'bank_accounts.*',
                            'bank_name.bank_name',
                            'approver.name as approve_by_name')
                        ->with([
                        	'hairstylist_schedules' => function($q) {
                        		$q->limit(2)->orderBy('created_at', 'desc');
	                        },
	                        'hairstylist_schedules.hairstylist_schedule_dates',
                            'documents'
	                    ])
                        ->first();

            if ($detail) {
            	$detail['today_shift'] = app($this->mitra)->getTodayShift($detail->id_user_hair_stylist);
            	$detail['shift_box'] = app('Modules\Recruitment\Http\Controllers\ApiMitraOutletService')->shiftBox($detail->id_outlet);
            }
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function update(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_user_hair_stylist']) && !empty($post['id_user_hair_stylist'])){
            if(isset($post['update_type']) && $post['update_type'] != 'approve'){
                $getData = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->first();
                if(!empty($post['data_document']['attachment'])){
                    $upload = MyHelper::uploadFile($post['data_document']['attachment'], 'document/hs/', $post['data_document']['ext'], $post['id_user_hair_stylist'].'_'.str_replace(" ","_", $post['data_document']['document_type']));
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $path = $upload['path'];
                    }else {
                        return response()->json(['status' => 'fail', 'messages' => ['Failed upload document']]);
                    }
                }

                if(!empty($post['data_document']['attachment_psychological_test'])){
                    $upload = MyHelper::uploadFile($post['data_document']['attachment_psychological_test'], 'document/hs/', $post['data_document']['attachment_psychological_test_ext'], $post['id_user_hair_stylist'].'_attachment_psychological_test');
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $pathPsychological = $upload['path'];
                    }else {
                        return response()->json(['status' => 'fail', 'messages' => ['Failed upload document psychological test']]);
                    }

                    $createDoc = UserHairStylistDocuments::create([
                        'id_user_hair_stylist' => $post['id_user_hair_stylist'],
                        'document_type' => $post['data_document']['document_type'],
                        'process_date' => date('Y-m-d H:i:s', strtotime($post['data_document']['process_date']??null)),
                        'process_name_by' => $post['data_document']['process_name_by']??null,
                        'process_notes' => $post['data_document']['process_notes'],
                        'attachment' => $path??null,
                        'attachment_psychological_test' => $pathPsychological??null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    if(!$createDoc){
                        return response()->json(MyHelper::checkCreate($createDoc));
                    }
                }

                $update = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update(['user_hair_stylist_status' => $post['update_type']]);

                if($update && $post['update_type'] == 'Rejected'){
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Rejected Candidate Hair Stylist',
                        $getData['phone_number'],
                        [
                            'fullname' => $getData['fullname'],
                            'phone_number' => $getData['phone_number'],
                            'email' => $getData['email']
                        ], null, false, false, 'hairstylist'
                    );
                }
                return response()->json(MyHelper::checkUpdate($update));
            }

            if(!empty($post['user_hair_stylist_photo'])){
                $upload = MyHelper::uploadPhotoStrict($post['user_hair_stylist_photo'], 'img/hs/', 300, 300, $post['nickname']);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $post['user_hair_stylist_photo'] = $upload['path'];
                }else {
                    return response()->json(['status' => 'fail', 'messages' => ['Failed upload image']]);
                }
            }

            if(isset($post['update_type']) && $post['update_type'] == 'approve'){
                $check = UserHairStylist::where('nickname', $post['nickname'])->whereNotIn('id_user_hair_stylist', [$post['id_user_hair_stylist']])->first();

                if(!empty($check)){
                    return response()->json(['status' => 'fail', 'messages' => ['Nickname already use with hairstylist : '.$check['fullname']]]);
                }

                if(isset($post['auto_generate_pin'])){
                    $pin = MyHelper::createrandom(6, 'Angka');
                }else{
                    $pin = $post['pin'];
                }
                $dtHs = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->first();
                if(empty($dtHs)){
                    return response()->json(['status' => 'fail', 'messages' => ['Hs not found']]);
                }

                unset($post['update_type']);
                unset($post['pin']);
                unset($post['pin2']);
                unset($post['auto_generate_pin']);
                unset($post['action_type']);
                $data = $post;
                $data['password'] = bcrypt($pin);
                $data['join_date'] = date('Y-m-d H:i:s');
                $data['approve_by'] = $request->user()->id;
                $data['user_hair_stylist_status'] = 'Active';
                $update = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update($data);

                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Approve Candidate Hair Stylist',
                    $dtHs['phone_number'],
                    [
                        'fullname' => $dtHs['fullname'],
                        'phone_number' => $dtHs['phone_number'],
                        'email' => $dtHs['email'],
                        'pin_hair_stylist' => $pin
                    ], null, false, false, 'hairstylist'
                );

            }else{
                unset($post['data_document']);
                unset($post['action_type']);
                $checkPhone = UserHairStylist::where(function ($q) use ($post){
                            $q->where('phone_number', $post['phone_number'])
                                ->orWhere('email', $post['email']);
                        })
                        ->whereNotIn('id_user_hair_stylist', [$post['id_user_hair_stylist']])->first();

                if(!empty($checkPhone)){
                    return response()->json(['status' => 'fail', 'messages' => ['Phone Number already use with another hairstylist']]);
                }

                if(!empty($post['birthdate'])){
                    $post['birthdate'] = date('Y-m-d', strtotime($post['birthdate']));
                }

                $sendCrmUpdatePin = 0;
                if(isset($post['auto_generate_pin'])){
                    $pin = MyHelper::createrandom(6, 'Angka');
                    $post['password'] = bcrypt($pin);
                    $sendCrmUpdatePin = 1;
                }elseif(isset($post['pin']) && !empty($post['pin'])){
                    $pin = $post['pin'];
                    $post['password'] = bcrypt($pin);
                    $sendCrmUpdatePin = 1;
                }

                unset($post['pin']);
                unset($post['pin2']);
                unset($post['auto_generate_pin']);
                $update = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update($post);

                if($update && $sendCrmUpdatePin == 1){
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Reset Password User Hair Stylist',
                        $post['phone_number'],
                        [
                            'fullname' => $post['fullname'],
                            'phone_number' => $post['phone_number'],
                            'email' => $post['email'],
                            'pin_hair_stylist' => $pin
                        ], null, false, false, 'hairstylist'
                    );
                }
            }

            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function detailDocument(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_user_hair_stylist_document'])){
            $detail = UserHairStylistDocuments::where('id_user_hair_stylist_document', $post['id_user_hair_stylist_document'])->first();
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function updateStatus(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_user_hair_stylist'])){
            $update = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update(['user_hair_stylist_status' => $post['user_hair_stylist_status']]);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function delete(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_user_hair_stylist'])){
            $check = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->first();
            if($check['user_hair_stylist_status'] == 'Active' || $check['user_hair_stylist_status'] == 'Inactive'){
                return response()->json(['status' => 'fail', 'messages' => ['Can not delete active/inactive hair stylist']]);
            }
            $del = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->delete();
            return response()->json(MyHelper::checkDelete($del));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function updateBox(Request $request)
    {
    	$post = $request->json()->all();
        if (!empty($post['id_user_hair_stylist']) && !empty($post['id_hairstylist_schedule_date'])) {

        	if (!empty($post['id_outlet_box'])) {
	        	$outletBox = OutletBox::find($post['id_outlet_box']);
	        	if (!$outletBox) {
	        		return ['status' => 'fail', 'messages' => ['Outlet box not found']];
	        	}

	        	$shift = app($this->mitra)->getOutletShift($outletBox['id_outlet']);
	        	if (!$shift) {
	        		return response()->json(['status' => 'fail', 'messages' => ['Outlet shift not found']]);
	        	}

	        	$usedBox = HairstylistSchedule::join(
						'hairstylist_schedule_dates', 
						'hairstylist_schedules.id_hairstylist_schedule', 
						'hairstylist_schedule_dates.id_hairstylist_schedule'
					)
			 		->where('id_user_hair_stylist', '!=', $post['id_user_hair_stylist'])
			 		->whereDate('date', date('Y-m-d'))
			 		->where('shift', $shift)
			 		->where('id_outlet_box', $post['id_outlet_box'])
			 		->first();

			 	if ($usedBox) {
					return [
						'status' => 'fail',
						'messages' => ['Box already used']
					];
				}
				$id_outlet_box = $post['id_outlet_box'];
        	} else {
				$id_outlet_box = null;
        	}

        	DB::beginTransaction();
			$update = HairstylistScheduleDate::where('id_hairstylist_schedule_date', $post['id_hairstylist_schedule_date'])->update(['id_outlet_box' => $id_outlet_box]);

			$createLog = LogOutletBox::create([
				'id_user_hair_stylist' => $post['id_user_hair_stylist'],
		    	'assigned_by' => $request->user()->id,
		    	'id_outlet_box' => $id_outlet_box,
		        'note' => $post['note']
			]);

			if ($createLog) {
				DB::commit();
			}
            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
