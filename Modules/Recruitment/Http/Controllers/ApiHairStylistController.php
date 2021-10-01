<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Http\Requests\user_hair_stylist_create;

class ApiHairStylistController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
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
            'user_hair_stylist_status' => 'Candidate'
        ];

        $create = UserHairStylist::create($dataCreate);
        return response()->json(MyHelper::checkCreate($create));
    }

    public function canditateList(Request $request){
        $post = $request->json()->all();

        $data = UserHairStylist::whereIn('user_hair_stylist_status', ['Candidate', 'Rejected'])->orderBy('created_at', 'desc');

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
                ->whereIn('user_hair_stylist_status', ['Active', 'Inactive'])->orderBy('join_date', 'desc');

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
	                        'hairstylist_schedules.hairstylist_schedule_dates'
	                    ])
                        ->first();
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function update(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_user_hair_stylist']) && !empty($post['id_user_hair_stylist'])){
            if(isset($post['update_type']) && $post['update_type'] == 'reject'){
                $getData = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->first();
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Rejected Candidate Hair Stylist',
                    $getData['phone_number'],
                    [
                        'fullname' => $getData['fullname'],
                        'phone_number' => $getData['phone_number'],
                        'email' => $getData['email']
                    ], null, false, false, 'hairstylist'
                );

                if(!$autocrm){
                    return response()->json(['status' => 'fail', 'messages' => ['Failed send notif reject']]);
                }

                $update = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update(['user_hair_stylist_status' => 'Rejected']);
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

                $checkPhone = UserHairStylist::where('phone_number', $post['phone_number'])->whereNotIn('id_user_hair_stylist', [$post['id_user_hair_stylist']])->first();

                if(!empty($checkPhone)){
                    return response()->json(['status' => 'fail', 'messages' => ['Phone Number already use with another hairstylist']]);
                }

                unset($post['update_type']);
                $data = $post;
                $data['birthdate'] = date('Y-m-d', strtotime($data['birthdate']));
                $data['join_date'] = date('Y-m-d H:i:s');
                $data['approve_by'] = $request->user()->id;
                $data['user_hair_stylist_status'] = 'Active';
                $update = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update($data);
            }else{
                $checkPhone = UserHairStylist::where('phone_number', $post['phone_number'])->whereNotIn('id_user_hair_stylist', [$post['id_user_hair_stylist']])->first();

                if(!empty($checkPhone)){
                    return response()->json(['status' => 'fail', 'messages' => ['Phone Number already use with another hairstylist']]);
                }

                if(!empty($post['birthdate'])){
                    $post['birthdate'] = date('Y-m-d', strtotime($post['birthdate']));
                }

                $update = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->update($post);
            }

            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
