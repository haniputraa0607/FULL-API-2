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

        $check = UserHairStylist::where('email', $post['email'])->first();

        if(!empty($check)){
            return response()->json(['status' => 'fail', 'messages' => ['Email already use']]);
        }

        $dataCreate = [
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

        $data = UserHairStylist::where('level', 'Candidate')->orderBy('created_at', 'desc');

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
                            $data->orWhere('gender', $row['operator']);
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
}
