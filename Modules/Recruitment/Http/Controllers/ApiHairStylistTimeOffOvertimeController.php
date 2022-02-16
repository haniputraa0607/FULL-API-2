<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use App\Http\Models\Outlet;
use DB;
use App\Lib\MyHelper;
use Modules\Recruitment\Entities\HairStylistTimeOff;
use Modules\Recruitment\Entities\HairstylistOverTime;


class ApiHairStylistTimeOffOvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function listHS(Request $request){
        $post = $request->all();
        $list_hs = UserHairStylist::where('id_outlet', $post['id_outlet'])->where('user_hair_stylist_status', 'Active')->get()->toArray();
        return $list_hs;
    }

    public function listDate(Request $request){
        $post = $request->all();
        if(empty($post['id_user_hair_stylist']) || empty($post['month']) || empty($post['year'])){
            return response()->json([
            	'status' => 'empty', 
            ]);
        }

        if($post['year']>=date('Y')){
            if($post['month']>=date('m')){
                $schedule = HairstylistSchedule::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->where('schedule_month', $post['month'])->where('schedule_year', $post['year'])->first();
                if($schedule){
                    $id_schedule = $schedule['id_hairstylist_schedule'];
                    $detail = HairstylistScheduleDate::where('id_hairstylist_schedule',$id_schedule)->get()->toArray();
                    if($detail){
                        $send = [];
                        foreach($detail as $key => $data){
                            if($data['date'] > date('Y-m-d 00:00:00')){
                                $send[$key]['id_hairstylist_schedule_date'] = $data['id_hairstylist_schedule_date'];
                                $send[$key]['date'] = $data['date'];
                                $send[$key]['date_format'] = date('d F Y', strtotime($data['date']));
                                $send[$key]['time_start'] = $data['time_start'];
                                $send[$key]['time_end'] = $data['time_end'];
                            }
                        }
                        return response()->json([
                            'status' => 'success', 
                            'result' => $send
                        ]); 
                    }
                }
                return response()->json([
                    'status' => 'fail', 
                    'messages' => ['The schedule for this hair stylist has not been created yet']
                ]);
            }else{
                return response()->json([
                    'status' => 'fail', 
                    'messages' => ['The month must be greater than or equal to this month']
                ]);
            }
        }else{
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['The year must be greater than or equal to this year']
            ]);
        }
       
    }

    public function createTimeOff(Request $request){
        $post = $request->all();
        $data_store = [];
        if(isset($post['id_hs'])){
            $data_store['id_user_hair_stylist'] = $post['id_hs'];
        }
        if(isset($post['id_outlet'])){
            $data_store['id_outlet'] = $post['id_outlet'];
        }
        if(isset($post['date'])){
            $data_store['date'] = $post['date'];
        }
        if(isset($post['time_start'])){
            $data_store['start_time'] = date('H:i:s', strtotime($post['time_start']));
        }
        if(isset($post['time_end'])){
            $data_store['end_time'] = date('H:i:s', strtotime($post['time_end']));
        }
        
        $data_store['request_by'] = auth()->user()->id;
        $data_store['request_at'] = date('Y-m-d');

        if($data_store){
            DB::beginTransaction();
            $store = HairStylistTimeOff::create($data_store);
            if(!$store){
                DB::rollBack();
                return response()->json([
                    'status' => 'success', 
                    'messages' => ['Failed to create a request hair stylist time off']
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => 'success', 
                'result' => $store
            ]);
        }
    }

    public function listTimeOff(Request $request)
    {
        $post = $request->all();
        $time_off = HairStylistTimeOff::join('user_hair_stylist','user_hair_stylist.id_user_hair_stylist','=','hairstylist_time_off.id_user_hair_stylist')
                    ->join('outlets', 'outlets.id_outlet', '=', 'hairstylist_time_off.id_outlet')
                    ->join('users', 'users.id', '=', 'hairstylist_time_off.request_by')
                    ->select(
                        'hairstylist_time_off.*',
                        'user_hair_stylist.fullname',
                        'outlets.outlet_name',
                        'users.name as request_by'
                    );
        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){
                         
                        if($condition['subject']=='name_hs'){
                            $subject = 'user_hair_stylist.fullname';
                        }elseif($condition['subject']=='outlet'){
                            $subject = 'outlets.outlet_name';
                        }elseif($condition['subject']=='request'){
                            $subject = 'users.name';
                        }else{
                            $subject = $condition['subject'];  
                        }

                        if($condition['operator'] == '='){
                            $time_off = $time_off->where($subject, $condition['parameter']);
                        }else{
                            $time_off = $time_off->where($subject, 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $time_off = $time_off->where(function ($q) use ($post){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){
                            if($condition['subject']=='name_hs'){
                                $subject = 'user_hair_stylist.fullname';
                            }elseif($condition['subject']=='outlet'){
                                $subject = 'outlets.outlet_name';
                            }elseif($condition['subject']=='request'){
                                $subject = 'users.name';
                            }else{
                                $subject = $condition['subject'];  
                            }

                            if($condition['operator'] == '='){
                                $q->orWhere($subject, $condition['parameter']);
                            }else{
                                $q->orWhere($subject, 'like', '%'.$condition['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }
        if(isset($post['order']) && isset($post['order_type'])){
            if($post['order']=='name_hs'){
                $order = 'user_hair_stylist.fullname';
            }elseif($post['order']=='outlet'){
                $order = 'outlets.outlet_name';
            }elseif($post['order']=='request'){
                $order = 'users.name';
            }else{
                $order = 'hairstylist_time_off.created_at';
            }
            if(isset($post['page'])){
                $time_off = $time_off->orderBy($order, $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $time_off = $time_off->orderBy($order, $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $time_off = $time_off->orderBy('hairstylist_time_off.created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $time_off = $time_off->orderBy('hairstylist_time_off.created_at', 'desc')->get()->toArray();
            }
        } 
        return MyHelper::checkGet($time_off);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('recruitment::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('recruitment::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('recruitment::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
