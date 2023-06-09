<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Jobs\UpdateScheduleHSJob;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\Disburse\Entities\BankAccount;
use Modules\ProductService\Entities\ProductHairstylistCategory;
use Modules\Recruitment\Entities\HairstylistCategory;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\UserHairStylistDocuments;
use Modules\Recruitment\Entities\HairstylistSchedule;	
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Outlet\Entities\OutletBox;
use App\Http\Models\LogOutletBox;
use Modules\Recruitment\Entities\UserHairStylistTheory;
use Modules\Recruitment\Http\Requests\user_hair_stylist_create;
use Image;
use DB;
use Modules\Recruitment\Entities\UserHairStylistExperience;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\Transaction\Entities\TransactionProductService;
use App\Http\Models\Transaction;
use File;
use Storage;
use Modules\Recruitment\Entities\HairstylistGroupProteksiAttendanceDefault;
use Modules\Recruitment\Entities\HairstylistGroupOvertimeDayDefault;
use Modules\Recruitment\Entities\HairstylistGroupLateDefault;
use App\Http\Models\Product;
use Modules\Product\Entities\ProductCommissionDefault;

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
                'messages' => 'Invalid number phone format'
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

        //check setting
        $hsStatus = 'Candidate';
        $setting = Setting::where('key', 'candidate_hs_requirements')->first()['value_text']??'';
        if(!empty(($setting))){
            $setting = (array)json_decode($setting);

            if(strtolower($post['gender']) == 'male'){
                $age = $setting['male_age']??'';
                $height = $setting['male_height']??'';
            }elseif(strtolower($post['gender']) == 'female'){
                $age = $setting['female_age']??'';
                $height = $setting['female_height']??'';
            }

            if(!empty($post['height']) && $post['height'] < $height){
                $hsStatus = 'Rejected';
            }

            if(!empty($post['birthdate'])){
                $dateOfBirth = date('Y-m-d', strtotime($post['birthdate']));
                $today = date("Y-m-d");
                $diff = date_diff(date_create($dateOfBirth), date_create($today));
                $currentage = (int)$diff->format('%y');
                if($currentage > $age){
                    $hsStatus = 'Rejected';
                }
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
            'user_hair_stylist_status' => $hsStatus,
            'user_hair_stylist_photo' => $post['user_hair_stylist_photo']??null
        ];

        $create = UserHairStylist::create($dataCreate);
        if($create){
            
            if (isset($post['photo_id_card']) && !empty($post['photo_id_card'])) {
                $img = Image::make(base64_decode($post['photo_id_card']));
                $imgwidth = $img->width();
                $imgheight = $img->height();
                $upload_id_card = MyHelper::uploadPhotoStrict($post['photo_id_card'], 'img/hs/id_card/', $imgwidth, $imgheight, time());
                if ($upload_id_card['status'] == "success") {
                    $create_doc = UserHairStylistDocuments::create([
                        "id_user_hair_stylist" => $create['id_user_hair_stylist'],
                        "document_type"        => 'Registration',
                        "process_name_by"      => 'ID Card',
                        "process_date"         => date('Y-m-d H:i:s'),
                        "attachment"           => $upload_id_card['path'],
                    ]);
                }
            }

            if (isset($post['experience']) && !empty($post['experience'])) {
                $create_experience = UserHairStylistExperience::create([
                    "id_user_hair_stylist" => $create['id_user_hair_stylist'],
                    "value"                => json_encode($post['experience']) 
                ]);
            }

            $autocrm = app($this->autocrm)->SendAutoCRM(
                'Register Candidate Hair Stylist',
                $create['phone_number'],
                [
                    'fullname' => $create['fullname'],
                    'phone_number' => $create['phone_number'],
                    'email' => $create['email'],
                    'approved_date' => date('d F Y'),
                    'status' => 'Candidate',
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

                        if($row['subject'] == 'level'){
                            $data->where('user_hair_stylist.level', $row['operator']);
                        }
                        if($row['subject'] == 'outlet'){
                            $data->where('user_hair_stylist.id_outlet', $row['operator']);
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

                            if($row['subject'] == 'level'){
                                $subquery->orWhere('level', $row['operator']);
                            }
                            if($row['subject'] == 'outlet'){
                             $subquery->orWhere('user_hair_stylist.id_outlet', $row['operator']);
                            }
                        }
                    }
                });
            }
        }
        $data = $data->select('user_hair_stylist.*', 'approver.name as approve_by_name');
        if(isset($post['without_paginate'])){
            $data = $data->get()->toArray();
        }else{
            $data = $data->paginate(25);
        }
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
                            'documents',
                            'experiences'
	                    ])
                        ->first();

            if ($detail) {
                $detail['file_contract'] = (empty($detail['file_contract'])? '' : config('url.storage_url_api').$detail['file_contract']);
            	$detail['today_shift'] = app($this->mitra)->getTodayShift($detail['id_user_hair_stylist']);
            	$detail['shift_box'] = app('Modules\Recruitment\Http\Controllers\ApiMitraOutletService')->shiftBox($detail['id_outlet']);
                if(isset($detail['experiences']) && !empty(isset($detail['experiences']))){
                    $value_experinces =  json_decode($detail['experiences']['value']??'' , true);
                    unset($detail['experiences']);
                    $detail['experiences'] = $value_experinces;
                }

                if(!empty($detail['documents'])){
                    foreach ($detail['documents'] as $key=>$doc){
                        if($doc['document_type'] == 'Training Completed'){
                            $theories = UserHairStylistTheory::where('id_user_hair_stylist_document', $doc['id_user_hair_stylist_document'])->get()->toArray();
                            $detail['documents'][$key]['theories'] = $theories;
                        }
                    }
                }
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

                if(!empty($post['data_document'])){
                    $idCat = $post['data_document']['id_theory_category'] ?? null;
                    $createDoc = UserHairStylistDocuments::create([
                        'id_user_hair_stylist' => $post['id_user_hair_stylist'],
                        'id_theory_category' => $idCat,
                        'document_type' => $post['data_document']['document_type'],
                        'process_date' => date('Y-m-d H:i:s', strtotime($post['data_document']['process_date']??date('Y-m-d H:i:s'))),
                        'process_name_by' => $post['data_document']['process_name_by']??null,
                        'process_notes' => $post['data_document']['process_notes'],
                        'attachment' => $path??null,
                        'conclusion_status' => $post['conclusion_status'][$idCat]??null,
                        'conclusion_score' => $post['conclusion_score'][$idCat]??null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    if(!$createDoc){
                        return response()->json(MyHelper::checkCreate($createDoc));
                    }
                }

                if(!empty($post['data_document']['theory'])){
                    $insertTheory = [];
                    foreach ($post['data_document']['theory'] as $theory){
                        if($post['data_document']['id_theory_category'] == $theory['id_theory_category']){
                            $theory['id_user_hair_stylist_document'] = $createDoc['id_user_hair_stylist_document'];
                            $theory['created_at'] = date('Y-m-d H:i:s');
                            $theory['updated_at'] = date('Y-m-d H:i:s');
                            unset($theory['id_theory_category']);
                            $insertTheory[] = $theory;
                        }
                    }

                    if(!empty($insertTheory)){
                        $update = UserHairStylistTheory::insert($insertTheory);
                    }
                }

                if(empty($post['data_document']['theory']) || $post['update_type'] == 'Rejected'){
                    $update = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update(['user_hair_stylist_status' => $post['update_type']]);
                }

                if($post['update_type'] == 'Rejected'){
                    UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update([
                        'user_hair_stylist_passed_status' => $post['user_hair_stylist_passed_status']??'Not Passed',
                        'user_hair_stylist_score' => $post['user_hair_stylist_score']??0
                    ]);
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

                return response()->json(MyHelper::checkUpdate($update ?? false));
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

                $checkIDCard = UserHairStylist::where('id_card_number', $post['id_card_number'])->first();

                if(!empty($checkIDCard)){
                    return response()->json(['status' => 'fail', 'messages' => ['ID card already use with hairstylist : '.$checkIDCard['fullname']]]);
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

                DB::beginTransaction();
                //generate code
                $count = UserHairStylist::whereNotNull('user_hair_stylist_code')->count();
                $currentYear = substr(date('Y'), -2);
                $currentMonth = date('m');

                unset($post['update_type']);
                unset($post['pin']);
                unset($post['pin2']);
                unset($post['auto_generate_pin']);
                unset($post['action_type']);
                $data = $post;
                $data['user_hair_stylist_code'] = 'IXO'.$currentYear.$currentMonth.sprintf("%04d", ($count+1));
                $data['password'] = bcrypt($pin);
                $data['join_date'] = date('Y-m-d H:i:s');
                $data['approve_by'] = $request->user()->id;
                $data['user_hair_stylist_status'] = 'Active';
                $data['user_hair_stylist_photo'] = $post['user_hair_stylist_photo']??null;
                $update = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update($data);

                if($update){
                    $dtHs = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->first();
                    $outlet = Outlet::where('id_outlet', $data['id_outlet'])->with('location_outlet')->first();
                    $outletName = $outlet['outlet_name']??'';
                    $companyType = $outlet['location_outlet']['company_type']??'';
                    $companyType = str_replace('PT ', '', $companyType);
                    $number = UserHairStylist::whereYear('join_date', date('Y'))->count();

                    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('hs_contract.docx');
                    $templateProcessor->setValue('number', $number);
                    $templateProcessor->setValue('company_type', $companyType);
                    $templateProcessor->setValue('roman_month', MyHelper::numberToRomanRepresentation(date('n')));
                    $templateProcessor->setValue('current_year', date('Y'));
                    $templateProcessor->setValue('current_date', MyHelper::dateFormatInd(date('Y-m-d'), true, false));
                    $templateProcessor->setValue('name', $dtHs['fullname']);
                    $templateProcessor->setValue('gender', $dtHs['gender']);
                    $templateProcessor->setValue('birthplace', $dtHs['birthplace']);
                    $templateProcessor->setValue('birthdate', MyHelper::dateFormatInd($dtHs['birthdate'], true, false));
                    $templateProcessor->setValue('recent_address', $dtHs['recent_address']);
                    $templateProcessor->setValue('id_card_number', (empty($dtHs['id_card_number']) ? '':$dtHs['id_card_number']));
                    $templateProcessor->setValue('join_date', MyHelper::dateFormatInd($dtHs['join_date'], true, false));
                    $templateProcessor->setValue('outlet_name', $outletName);
                    $proteksi = HairstylistGroupProteksiAttendanceDefault::leftJoin('hairstylist_group_proteksi_attendances', function ($join) use ($dtHs) {
                        $join->on('hairstylist_group_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance', 'hairstylist_group_default_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance')
                            ->where('id_hairstylist_group', $dtHs->id_hairstylist_group);
                    })
                         ->where('month', date('m'))
                        ->select('hairstylist_group_default_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance','hairstylist_group_default_proteksi_attendances.month',
                            DB::raw('
                                CASE WHEN
                                hairstylist_group_proteksi_attendances.value IS NOT NULL THEN hairstylist_group_proteksi_attendances.value ELSE hairstylist_group_default_proteksi_attendances.value
                                END as value
                             '),
                        DB::raw('
                            CASE WHEN
                            hairstylist_group_proteksi_attendances.amount IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount ELSE hairstylist_group_default_proteksi_attendances.amount
                            END as amount
                         '),
                        DB::raw('
                            CASE WHEN
                            hairstylist_group_proteksi_attendances.amount_proteksi IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount_proteksi ELSE hairstylist_group_default_proteksi_attendances.amount_proteksi
                            END as amount_proteksi
                         '),
                        DB::raw('
                            CASE WHEN
                            hairstylist_group_proteksi_attendances.amount_day IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount_day ELSE hairstylist_group_default_proteksi_attendances.amount_day
                            END as amount_day
                         '),
                        )->first();
                    $overtime = HairstylistGroupOvertimeDayDefault::leftJoin('hairstylist_group_overtime_days', function ($join) use ($dtHs) {
                            $join->on('hairstylist_group_overtime_days.id_hairstylist_group_default_overtime_day', 'hairstylist_group_default_overtime_days.id_hairstylist_group_default_overtime_day')
                                ->where('id_hairstylist_group', $dtHs->id_hairstylist_group);
                        })
                            ->select('hairstylist_group_default_overtime_days.id_hairstylist_group_default_overtime_day','hairstylist_group_default_overtime_days.days',
                                DB::raw('
                                                   CASE WHEN
                                                   hairstylist_group_overtime_days.value IS NOT NULL THEN hairstylist_group_overtime_days.value ELSE hairstylist_group_default_overtime_days.value
                                                   END as value
                                                '),
                            )->orderby('days', 'asc')->first();
                    $templateProcessor->setValue('proteksi', number_format($proteksi->amount??0,0,',','.'));
                    $templateProcessor->setValue('amount_value', $proteksi->value??0);
                    $templateProcessor->setValue('amount_proteksi', number_format($proteksi->amount_proteksi??0,0,',','.'));
                    $templateProcessor->setValue('amount_hari', number_format($proteksi->amount_proteksi??0,0,',','.'));
                    $templateProcessor->setValue('overtime', number_format($overtime->value??0,0,',','.'));
                    
                    //lateness
                    $lateness1 = HairstylistGroupLateDefault::leftJoin('hairstylist_group_lates', function ($join) use ($dtHs) {
                                    $join->on('hairstylist_group_lates.id_hairstylist_group_default_late', 'hairstylist_group_default_lates.id_hairstylist_group_default_late')
                                        ->where('id_hairstylist_group', $dtHs->id_hairstylist_group);
                                })
                                    ->select('hairstylist_group_default_lates.id_hairstylist_group_default_late','hairstylist_group_default_lates.range',
                                        DB::raw('
                                                           CASE WHEN
                                                           hairstylist_group_lates.value IS NOT NULL THEN hairstylist_group_lates.value ELSE hairstylist_group_default_lates.value
                                                           END as value
                                                        '),
                                    )->orderby('range', 'asc')->first();
                    $lateness2 = HairstylistGroupLateDefault::leftJoin('hairstylist_group_lates', function ($join) use ($dtHs) {
                                    $join->on('hairstylist_group_lates.id_hairstylist_group_default_late', 'hairstylist_group_default_lates.id_hairstylist_group_default_late')
                                        ->where('id_hairstylist_group', $dtHs->id_hairstylist_group);
                                })
                                    ->select('hairstylist_group_default_lates.id_hairstylist_group_default_late','hairstylist_group_default_lates.range',
                                        DB::raw('
                                                           CASE WHEN
                                                           hairstylist_group_lates.value IS NOT NULL THEN hairstylist_group_lates.value ELSE hairstylist_group_default_lates.value
                                                           END as value
                                                        '),
                                    )->orderby('range', 'DESC')->first();
                                
                    $templateProcessor->setValue('amount_late_time1', $lateness1->range??0);
                    $templateProcessor->setValue('amount_late_time2', $lateness2->range??0);
                    $templateProcessor->setValue('amount_late1', number_format($lateness1->value??0,0,',','.'));
                    $templateProcessor->setValue('amount_late2', number_format($lateness2->value??0,0,',','.'));
                    $haircut = Setting::where('key','haircut_service')->first()['value']??0;
                    $other = Setting::where('key','other_service')->first()['value']??0;
                    //commision 
                    $product = Product::where(array('id_product'=>$haircut))->first();
                        $commission = ProductCommissionDefault::where(array('id_product'=>$product->id_product??0))->with(['dynamic_rule'=> function($d){$d->orderBy('qty','desc');}])->first();
                        if($commission && ($commission['dynamic_rule'])){
                            $dynamic_rule = [];
                            $count = count($commission['dynamic_rule']) - 1;
                            foreach($commission['dynamic_rule'] as $key => $value){
                                if($count==$key || $count==0){
                                    $for_null = $value['qty']-1;
                                    if($count!=0){
                                        $dynamic_rule[] = [
                                            'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                            'qty' => $value['qty'].' - '.($commission['dynamic_rule'][$key-1]['qty']-1),
                                            'value' => $value['value']
                                        ];
                                    }else{
                                        $dynamic_rule[] = [
                                            'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                            'qty' => '>= '.$value['qty'],
                                            'value' => $value['value']
                                        ];
                                    }
                                    if($value['qty']!=1){
                                        $dynamic_rule[] = [
                                            'id_product_commission_default_dynamic' => null,
                                            'qty' => '0 - '.$for_null,
                                            'value' => 0
                                        ];
                                    }
                                }else{
                                    if($key==0){
                                        $dynamic_rule[] = [
                                            'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                            'qty' => '>= '.$value['qty'],
                                            'value' => $value['value']
                                        ];
                                    }else{
                                        $before = $commission['dynamic_rule'][$key-1]['qty'] - 1;
                                        if($before == $value['qty']){
                                            $qty = $value['qty'];
                                        }else{
                                            $qty = $value['qty'].' - '.$before;
                                        }
                                        $dynamic_rule[] = [
                                            'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                            'qty' => $qty,
                                            'value' => $value['value']
                                        ];
                                    }
                                }
                            }
                            $commission['dynamic_rule_list'] = $dynamic_rule;
                        }
                        $commissions = '-';
                        if($commission){
                            
                            if($commission->dynamic){
                                $commissions = "Dinamic";
                                foreach($commission->dynamic_rule_list as $com){
                                    $commissions .= "\n".$com['qty'].' Kepala : Rp. '.number_format($com['value']??0,0,',','.');
                                }
                            }else{
                                if($commission->percent){
                                    $commissions = "Static \nCommission : ".$commission->commission."%";
                                }else{
                                    $commissions = "Static \nCommission : Rp. ".number_format($commission->commission??0,0,',','.');
                                }
                            }
                        }
                    //other
                        $product = Product::where(array('id_product'=>$other))->first();
                        $other = ProductCommissionDefault::where(array('id_product'=>$product->id_product??0))->with(['dynamic_rule'=> function($d){$d->orderBy('qty','desc');}])->first();
                        if($other && ($other['dynamic_rule'])){
                            $dynamic_rule = [];
                            $count = count($other['dynamic_rule']) - 1;
                            foreach($other['dynamic_rule'] as $key => $value){
                                if($count==$key || $count==0){
                                    $for_null = $value['qty']-1;
                                    if($count!=0){
                                        $dynamic_rule[] = [
                                            'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                            'qty' => $value['qty'].' - '.($other['dynamic_rule'][$key-1]['qty']-1),
                                            'value' => $value['value']
                                        ];
                                    }else{
                                        $dynamic_rule[] = [
                                            'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                            'qty' => '>= '.$value['qty'],
                                            'value' => $value['value']
                                        ];
                                    }
                                    if($value['qty']!=1){
                                        $dynamic_rule[] = [
                                            'id_product_commission_default_dynamic' => null,
                                            'qty' => '0 - '.$for_null,
                                            'value' => 0
                                        ];
                                    }
                                }else{
                                    if($key==0){
                                        $dynamic_rule[] = [
                                            'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                            'qty' => '>= '.$value['qty'],
                                            'value' => $value['value']
                                        ];
                                    }else{
                                        $before = $other['dynamic_rule'][$key-1]['qty'] - 1;
                                        if($before == $value['qty']){
                                            $qty = $value['qty'];
                                        }else{
                                            $qty = $value['qty'].' - '.$before;
                                        }
                                        $dynamic_rule[] = [
                                            'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                            'qty' => $qty,
                                            'value' => $value['value']
                                        ];
                                    }
                                }
                            }
                            $other['dynamic_rule_list'] = $dynamic_rule;
                        }
                        $others = '-';
                        if($other){
                            if($other->dynamic){
                                $others = "Dinamic\n";
                                foreach($other->dynamic_rule_list as $oth){
                                    $others .= "\n".$oth['qty'].' Kepala : Rp. '.number_format($oth['value']??0,0,',','.');
                                }
                            }else{
                                if($other->percent){
                                    $others = "Static \nCommission : ".$other->commission."%";
                                }else{
                                    $others = "Static \nCommission : Rp. ".number_format($other->commission??0,0,',','.');
                                }
                            }
                        }
                    
                    $templateProcessor->setValue('haircut', $commissions);
                    $templateProcessor->setValue('other', $others);
                    if(!File::exists(public_path().'/hs_contract')){
                        File::makeDirectory(public_path().'/hs_contract');
                    }
                    $directory = 'hs_contract/hs_'.$data['user_hair_stylist_code'].'.docx';
                    $templateProcessor->saveAs($directory);
                    if(config('configs.STORAGE') != 'local'){
                        $contents = File::get(public_path().'/'.$directory);
                        $store = Storage::disk(config('configs.STORAGE'))->put($directory,$contents, 'public');
                        if($store){
                            File::delete(public_path().'/'.$directory);
                        }
                    }
                    if($templateProcessor){
                        UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update(['file_contract' => $directory]);
                    }

                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Approve Candidate Hair Stylist',
                        $dtHs['phone_number'],
                        [
                            'fullname' => $dtHs['fullname'],
                            'phone_number' => $dtHs['phone_number'],
                            'email' => $dtHs['email'],
                            'pin_hair_stylist' => $pin,
                            'approved_date' => date('d F Y'),
                            'status' => 'Active',
                        ], null, false, false, 'hairstylist'
                    );
                }
                DB::commit();
            }else{
                $check = UserHairStylist::where('nickname', $post['nickname'])->whereNotIn('id_user_hair_stylist', [$post['id_user_hair_stylist']])->first();

                if(!empty($check)){
                    return response()->json(['status' => 'fail', 'messages' => ['Nickname already use with hairstylist : '.$check['fullname']]]);
                }

                $checkIDCard = UserHairStylist::where('id_card_number', $post['id_card_number'])->whereNotIn('id_user_hair_stylist', [$post['id_user_hair_stylist']])->first();

                if(!empty($checkIDCard)){
                    return response()->json(['status' => 'fail', 'messages' => ['ID card already use with hairstylist : '.$checkIDCard['fullname']]]);
                }

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
                unset($post['businees_partner_id']);
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

    function totalOrder(Request $request){
        $post = $request->json()->all();

        if (!empty($post['id_user_hair_stylist'])) {
            $hs = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->first();
            if(empty($hs)){
                return response()->json(['status' => 'fail', 'messages' => ['Hair stylist not found']]);
            }

            $currentDate = date('Y-m-d');
            $outletService = TransactionProductService::join('transaction_products', 'transaction_products.id_transaction_product', 'transaction_product_services.id_transaction_product')
                                ->where('transaction_product_services.id_user_hair_stylist', $post['id_user_hair_stylist'])
                                ->whereNull('transaction_products.reject_at')
                                ->where(function ($q){
                                    $q->where('service_status', '<>', 'Completed')
                                        ->orWhereNull('service_status');
                                })
                                ->pluck('transaction_product_services.id_transaction')->toArray();
            $homeService = TransactionHomeService::where('id_user_hair_stylist', $post['id_user_hair_stylist'])
                                ->where(function ($q){
                                    $q->whereNotIn('status', ['Completed', 'Cancelled'])
                                        ->orWhereNull('status');
                                })
                                ->pluck('id_transaction')->toArray();

            if(!empty($outletService)) {
                $trxOutlet = Transaction::where('transaction_from', 'outlet-service')
                    ->where(function ($q){
                        $q->where('transaction_payment_status', 'Completed')
                            ->orWhere(function ($sub){
                                $sub->where('transaction_payment_status', 'Pending')
                                    ->where('trasaction_payment_type', 'Cash');
                            });
                    })
                    ->whereIn('id_transaction', $outletService)
                    ->whereNull('reject_at')
                    ->where('id_outlet', $hs['id_outlet'])->with(['user', 'outlet'])->get()->toArray();
            }

            if(!empty($homeService)) {
                $trxHome = Transaction::where('transaction_payment_status', 'Completed')->where('transaction_from', 'home-service')
                    ->whereNull('reject_at')
                    ->whereIn('id_transaction', $homeService)->with(['user', 'outlet'])->get()->toArray();
            }

            $res = [
                'order_outlet' => $trxOutlet??[],
                'order_home' => $trxHome??[]
            ];
            return response()->json(MyHelper::checkGet($res));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    function moveOutlet(Request $request){
        $post = $request->json()->all();

        if (!empty($post['id_user_hair_stylist'])) {
            $hs = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->first();
            if(empty($hs)){
                return response()->json(['status' => 'fail', 'messages' => ['Hair stylist not found']]);
            }

            $outlet = Outlet::where('id_outlet', $post['id_outlet'])->first();
            if(empty($outlet)){
                return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
            }
            if($hs['id_outlet'] != $post['id_outlet']){
                $days = [
                    'Mon' => 'Senin',
                    'Tue' => 'Selasa',
                    'Wed' => 'Rabu',
                    'Thu' => 'Kamis',
                    'Fri' => 'Jumat',
                    'Sat' => 'Sabtu',
                    'Sun' => 'Minggu'
                ];

                $currentDate = date('Y-m-d');
                $outletService = TransactionProductService::join('transaction_products', 'transaction_products.id_transaction_product', 'transaction_product_services.id_transaction_product')
                    ->where('transaction_product_services.id_user_hair_stylist', $post['id_user_hair_stylist'])
                    ->whereNull('transaction_products.reject_at')
                    ->where(function ($q){
                        $q->where('service_status', '<>', 'Completed')
                            ->orWhereNull('service_status');
                    })
                    ->pluck('transaction_product_services.id_transaction')->toArray();
                $homeService = TransactionHomeService::where('id_user_hair_stylist', $post['id_user_hair_stylist'])
                    ->where(function ($q){
                        $q->whereNotIn('status', ['Completed', 'Cancelled'])
                            ->orWhereNull('status');
                    })
                    ->pluck('id_transaction')->toArray();

                if(!empty($outletService)) {
                    $trxOutlet = Transaction::where('transaction_from', 'outlet-service')
                        ->where(function ($q){
                            $q->where('transaction_payment_status', 'Completed')
                                ->orWhere(function ($sub){
                                    $sub->where('transaction_payment_status', 'Pending')
                                        ->where('trasaction_payment_type', 'Cash');
                                });
                        })
                        ->whereIn('id_transaction', $outletService)
                        ->whereNull('reject_at')
                        ->where('id_outlet', $hs['id_outlet'])->with(['user', 'outlet'])->get()->toArray();
                }

                if(!empty($homeService)) {
                    $trxHome = Transaction::join('transaction_home_services', 'transaction_home_services.id_transaction', 'transactions.id_transaction')
                        ->whereNull('reject_at')
                        ->where('transaction_payment_status', 'Completed')->where('transaction_from', 'home-service')
                        ->whereIn('transactions.id_transaction', $homeService)->with(['user', 'outlet'])->get()->toArray();
                }

                if(!empty($trxOutlet)){
                    return response()->json(['status' => 'fail', 'messages' => ['Hair stylist have outlet transaction']]);
                }

                $conflictTrx = [];
                if(!empty($homeService)){
                    $getScheduleOutlet = OutletSchedule::where('id_outlet', $post['id_outlet'])->with('time_shift')->get()->toArray();
                    $columnDay = array_column($getScheduleOutlet, 'day');
                    foreach ($trxHome as $home){
                        $day = $days[date('D', strtotime($home['schedule_date']))];
                        $search = array_search($day,$columnDay);
                        if($search !== false){
                            $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                                ->where('id_user_hair_stylist', $post['id_user_hair_stylist'])
                                ->whereDate('date', $home['schedule_date'])
                                ->first()['shift']??'';

                            $dataOutletShift = $getScheduleOutlet[$search]['time_shift'];
                            $columnShift = array_search($shift, array_column($dataOutletShift, 'shift'));
                            if(empty($shift) || $columnShift === false){
                                continue;
                            }
                            $start = date('H:i:s', strtotime($dataOutletShift[$columnShift]['shift_time_start']));
                            $end = date('H:i:s', strtotime($dataOutletShift[$columnShift]['shift_time_end']));

                            if(strtotime($home['schedule_time']) >= strtotime($start) && strtotime($home['schedule_time']) < strtotime($end)){
                                $conflictTrx[] = $home['transaction_receipt_number'];
                            }
                        }
                    }
                }

                if(!empty($conflictTrx)){
                    return response()->json(['status' => 'fail', 'messages' => ['Transaction Home service have conflict with schedule outlet '.$outlet['outlet_name'].' : '.implode(', ', $conflictTrx)]]);
                }

                $update = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update(['id_outlet' => $post['id_outlet']]);
                if($update){
                    UpdateScheduleHSJob::dispatch(['id_user_hair_stylist' => $post['id_user_hair_stylist']])->allOnConnection('database');

                }

                return response()->json(MyHelper::checkUpdate($update));
            }

            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function candidateSettingRequirements(Request $request){
        $post = $request->json()->all();

        if(empty($post)){
            $setting = Setting::where('key', 'candidate_hs_requirements')->first()['value_text']??'';

            if(!empty(($setting))){
                $setting = (array)json_decode($setting);
            }else{
                $setting = [
                    'male_age' => '',
                    'male_height' => '',
                    'female_age' => '',
                    'female_height' => ''
                ];
            }

            return response()->json(MyHelper::checkGet($setting));
        }else{
            $data = json_encode($post);
            $update = Setting::updateOrCreate(['key' => 'candidate_hs_requirements'], ['value_text' => $data]);

            return response()->json(MyHelper::checkUpdate($update));
        }
    }

    public function exportCommission($request){
        $post = $request;

        $dateStart = date('Y-m-d', strtotime($post['date_start']));
        $dateEnd = date('Y-m-d', strtotime($post['date_end']));
        $idOutlets = $post['id_outlet'];

        $outletService = Transaction::join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
            ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_product_services.id_user_hair_stylist')
            ->join('products', 'products.id_product', 'transaction_products.id_product')
            ->whereDate('transaction_products.transaction_product_completed_at', '>=', $dateStart)->whereDate('transaction_products.transaction_product_completed_at', '<=', $dateEnd)
            ->whereIn('transactions.id_outlet', $idOutlets)
            ->whereNotNull('transaction_products.transaction_product_completed_at')
            ->groupBy(DB::raw('transaction_products.transaction_product_completed_at'), 'transaction_product_services.id_user_hair_stylist', 'transaction_products.id_product')
            ->select(DB::raw('DATE(transaction_products.transaction_product_completed_at) as schedule_date'), 'transactions.id_outlet', 'transaction_product_services.id_user_hair_stylist', 'transaction_products.id_product', 'fullname', 'outlet_name','transaction_products.type', 'product_name', DB::raw('SUM(transaction_products.transaction_product_qty) as total'))
            ->get()->toArray();

        $homeService = Transaction::join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
            ->join('transaction_home_services', 'transaction_home_services.id_transaction', 'transaction_products.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_home_services.id_user_hair_stylist')
            ->join('products', 'products.id_product', 'transaction_products.id_product')
            ->whereDate('transaction_products.transaction_product_completed_at', '>=', $dateStart)->whereDate('transaction_products.transaction_product_completed_at', '<=', $dateEnd)
            ->whereIn('transactions.id_outlet', $idOutlets)
            ->whereNotNull('transaction_products.transaction_product_completed_at')
            ->groupBy(DB::raw('transaction_products.transaction_product_completed_at'), 'transaction_home_services.id_user_hair_stylist', 'transaction_products.id_product')
            ->select(DB::raw('DATE(transaction_products.transaction_product_completed_at) as schedule_date'), 'transactions.id_outlet', 'transaction_home_services.id_user_hair_stylist', 'transaction_products.id_product', 'fullname', 'outlet_name','transaction_products.type', 'product_name', DB::raw('SUM(transaction_products.transaction_product_qty) as total'))
            ->get()->toArray();
        $product = Transaction::join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_products.id_user_hair_stylist')
            ->join('products', 'products.id_product', 'transaction_products.id_product')
            ->whereDate('transaction_products.transaction_product_completed_at', '>=', $dateStart)->whereDate('transaction_products.transaction_product_completed_at', '<=', $dateEnd)
            ->whereIn('transactions.id_outlet', $idOutlets)
            ->where('transaction_products.type', "Product")
            ->whereNotNull('transaction_products.transaction_product_completed_at')
            ->groupBy(DB::raw('transaction_products.transaction_product_completed_at'), 'transaction_products.id_user_hair_stylist', 'transaction_products.id_product')
            ->select(DB::raw('DATE(transaction_products.transaction_product_completed_at) as schedule_date'), 'transactions.id_outlet', 'transaction_products.id_user_hair_stylist', 'transaction_products.id_product', 'fullname', 'outlet_name','transaction_products.type', 'product_name', DB::raw('SUM(transaction_products.transaction_product_qty) as total'))
            ->get()->toArray();
        $datas = array_merge($outletService, $homeService);
        $datas = array_merge($product, $datas);
        $dates = [];
        $tmp = $dateStart;
        $i=0;
        while(strtotime($tmp) <= strtotime($dateEnd)) {
            $dateTimeConvert = date('Y-m-d', strtotime("+".$i." days", strtotime($dateStart)));
            if(strtotime($dateTimeConvert) > strtotime($dateEnd)){
                break;
            }
            $tmp = $dateTimeConvert;
            $dates[] = $dateTimeConvert;
            $i++;
        }

        $tmpData = [];
        foreach ($datas as $data){
            $key = $data['id_product'].'|'.$data['id_user_hair_stylist'].'|'.$data['id_outlet'];
            if(!isset($tmpData[$key])){
                $tmpData[$key] = [
                    'Name' => $data['fullname'],
                    'Outlet' => $data['outlet_name'],
                    'Product' => $data['product_name'],
                    'Type' => $data['type'],
                    'Total' => 0
                ];
            }

            foreach ($dates as $date){
                if(empty($tmpData[$key][$date])){
                    $tmpData[$key][$date] = 0;
                }
                if($date == $data['schedule_date']){
                    $tmpData[$key][$date] = $tmpData[$key][$date]+$data['total'];
                    $tmpData[$key]['Total'] = $tmpData[$key]['Total'] + $data['total'];
                }
            }
        }

        $res = [];
        foreach ($tmpData as $dt){
            foreach ($dt as $key=>$value){
                if(is_int($value)){
                    $dt[$key] = (int)$value;
                }

                if($value == 0){
                    $dt[$key] = (string)$value;
                }
            }

            $res[] = $dt;
        }

        return $res;
    }

    public function createCategory(Request $request){
        $post = $request->json()->all();

        $save = HairstylistCategory::create($post);
        return response()->json(MyHelper::checkUpdate($save));
    }

    public function listCategory(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_hairstylist_category'])){
            $data = HairstylistCategory::where('id_hairstylist_category', $post['id_hairstylist_category'])->first();
        }else{
            $data = HairstylistCategory::get()->toArray();
        }

        return response()->json(MyHelper::checkGet($data));
    }

    public function updateCategory(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_hairstylist_category'])){
            $save = HairstylistCategory::where('id_hairstylist_category', $post['id_hairstylist_category'])->update([
                'hairstylist_category_name' => $post['hairstylist_category_name']
            ]);

            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function deleteCategory(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_hairstylist_category'])){
            $save = HairstylistCategory::where('id_hairstylist_category', $post['id_hairstylist_category'])->delete();
            if($save){
                ProductHairstylistCategory::where('id_hairstylist_category', $post['id_hairstylist_category'])->delete();
            }
            return response()->json(MyHelper::checkDelete($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function createBusinessPartner(Request $request){
        $post = $request->all();
        if(isset($post['id_user_hair_stylist']) && !empty($post['id_user_hair_stylist'])){
            $user_hs = UserHairStylist::find($post['id_user_hair_stylist']);
            if(!$user_hs){
                return [
                    'status' => 'fail',
                    'messages' => 'Hair Stylist not found',
                ];
            }
            $id_business_partner = null;
            if(isset($post['id_business_partner']) && !empty($post['id_business_partner'])){
                $id_business_partner = $post['id_business_partner'];
            }
            $user_hs = $user_hs->businessPartner($id_business_partner);
            if(isset($user_hs['status']) && $user_hs['status']=='success'){
                return [
                    'status' => 'success',
                    'id_business_partner' => $user_hs['id_business_partner']
                ];
            }else{
                return [
                    'status' => 'fail',
                    'messages' =>  $user_hs['messages']
                ];
            }
        }
        return [
            'status' => 'fail',
            'messages' => 'Id Hair Stylist Cant be empty',
        ];
    }

    public function bankAccountSave(Request $request){
        $post = $request->all();
        $check = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->first();
        $dtBank = [
            'id_bank_name' => $post['id_bank_name'],
            'beneficiary_name' => $post['beneficiary_name'],
            'beneficiary_account' => $post['beneficiary_account']
        ];
        if(empty($check['id_bank_account'])){
            $createBank = BankAccount::create($dtBank);
            if($createBank){
                UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update(['id_bank_account' => $createBank['id_bank_account']]);
            }

            return response()->json(MyHelper::checkCreate($createBank));
        }else{
            $update = BankAccount::where('id_bank_account', $check['id_bank_account'])->update($dtBank);
            return response()->json(MyHelper::checkUpdate($update));
        }
    }

    public function updateByExcel(Request $request){
        $post = $request->all();
        if(isset($post['data']) && !empty($post['data'])){
            DB::beginTransaction();

            foreach($post['data'] ?? [] as $data){
                if(isset($data['no_hp']) && !empty($data['no_hp']) && isset($data['nik']) && !empty($data['nik'])){

                    //cek code
                    $check_code = UserHairStylist::where('user_hair_stylist_code', $data['nik'])->where('phone_number', '<>', $data['no_hp'])->get()->toArray();
                    if(!$check_code){
                        $check_hs = UserHairStylist::where('phone_number', $data['no_hp'])->first();
                        if($check_hs){
                            $update = UserHairStylist::where('phone_number', $data['no_hp'])->update(['user_hair_stylist_code' => $data['nik']]);
                            if(!$update){
                                DB::rollback();
                                return [
                                    'status' => 'fail',
                                    'messages' => 'Failed',
                                ];
                            }
                        }
                    }else{
                        DB::rollback();
                        return [
                            'status' => 'fail',
                            'messages' => 'Failed',
                        ];
                    }
                }else{
                    DB::rollback();
                    return [
                        'status' => 'fail',
                        'messages' => 'Failed',
                    ];
                }
            }

            DB::commit();
            return [
                'status' => 'success'
            ];
        }
        return [
            'status' => 'fail',
            'messages' => 'Failed',
        ];
    }
}
