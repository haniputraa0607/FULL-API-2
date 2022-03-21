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
use Modules\Transaction\Entities\HairstylistNotAvailable;


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

                    if(isset($post['date'])){
                        $time = HairstylistScheduleDate::where('id_hairstylist_schedule',$id_schedule)->where('date',$post['date'])->first();
                        return response()->json([
                            'status' => 'success', 
                            'result' => $time
                        ]); 
                    }

                    $detail = HairstylistScheduleDate::where('id_hairstylist_schedule',$id_schedule)->get()->toArray();
                    if($detail){
                        $send = [];
                        foreach($detail as $key => $data){
                            if($data['date'] >= date('Y-m-d 00:00:00')){
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

    public function deleteTimeOff(Request $request){
        $post = $request->all();
        $delete = HairStylistTimeOff::where('id_hairstylist_time_off', $post['id_hairstylist_time_off'])->delete();
        if($delete){
            return MyHelper::checkDelete($delete);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function detailTimeOff(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_hairstylist_time_off']) && !empty($post['id_hairstylist_time_off'])){
            $time_off = HairStylistTimeOff::where('id_hairstylist_time_off', $post['id_hairstylist_time_off'])->with(['hair_stylist','outlet','approve','request'])->first();
            
            if($time_off==null){
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => $time_off,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function updateTimeOff(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_hairstylist_time_off']) && !empty($post['id_hairstylist_time_off'])){
            $data_update = [];
            if(isset($post['id_hs'])){
                $data_update['id_user_hair_stylist'] = $post['id_hs'];
            }
            if(isset($post['id_outlet'])){
                $data_update['id_outlet'] = $post['id_outlet'];
            }
            if(isset($post['date'])){
                $data_update['date'] = $post['date'];
            }
            if(isset($post['time_start'])){
                $data_update['start_time'] = date('H:i:s', strtotime($post['time_start']));
            }
            if(isset($post['time_end'])){
                $data_update['end_time'] = date('H:i:s', strtotime($post['time_end']));
            }
            if(isset($post['approve'])){
                $data_update['approve_by'] = auth()->user()->id;
                $data_update['approve_at'] = date('Y-m-d');
            }

            if($data_update){
                DB::beginTransaction();
                $update = HairStylistTimeOff::where('id_hairstylist_time_off',$post['id_hairstylist_time_off'])->update($data_update);
                if(!$update){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'success', 
                        'messages' => ['Failed to updated a request hair stylist time off']
                    ]);
                }
                if(isset($post['approve'])){
                    $data_not_avail = [
                        "id_outlet" => $data_update['id_outlet'],
                        "id_user_hair_stylist" => $data_update['id_user_hair_stylist'],
                        "booking_start" => date('Y-m-d', strtotime($data_update['date'])).' '.$data_update['start_time'],
                        "booking_end" => date('Y-m-d', strtotime($data_update['date'])).' '.$data_update['end_time'],
                    ];
                    $store_not_avail = HairstylistNotAvailable::create($data_not_avail);
                    if(!$store_not_avail){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'success', 
                            'messages' => ['Failed to updated a request hair stylist time off']
                        ]);
                    }
                }
                DB::commit();
                return response()->json([
                    'status' => 'success'
                ]);
            }
            
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function createOvertime(Request $request){
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
        if(isset($post['time'])){
            $data_store['time'] =$post['time'];
        }
        if(isset($post['duration'])){
            $data_store['duration'] = date('H:i:s', strtotime($post['duration']));
        }
        
        $data_store['request_by'] = auth()->user()->id;
        $data_store['request_at'] = date('Y-m-d');
        if($data_store){
            DB::beginTransaction();
            $store = HairstylistOverTime::create($data_store);
            if(!$store){
                DB::rollBack();
                return response()->json([
                    'status' => 'success', 
                    'messages' => ['Failed to create a request hair stylist overtime']
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => 'success', 
                'result' => $store
            ]);
        }
    }

    public function listOvertime(Request $request)
    {
        $post = $request->all();
        $time_off = HairstylistOverTime::join('user_hair_stylist','user_hair_stylist.id_user_hair_stylist','=','hairstylist_overtime.id_user_hair_stylist')
                    ->join('outlets', 'outlets.id_outlet', '=', 'hairstylist_overtime.id_outlet')
                    ->join('users', 'users.id', '=', 'hairstylist_overtime.request_by')
                    ->select(
                        'hairstylist_overtime.*',
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
                $order = 'hairstylist_overtime.created_at';
            }
            if(isset($post['page'])){
                $time_off = $time_off->orderBy($order, $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $time_off = $time_off->orderBy($order, $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $time_off = $time_off->orderBy('hairstylist_overtime.created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $time_off = $time_off->orderBy('hairstylist_overtime.created_at', 'desc')->get()->toArray();
            }
        } 
        return MyHelper::checkGet($time_off);
    }

    public function deleteOvertime(Request $request){
        $post = $request->all();
        $delete = HairstylistOverTime::where('id_hairstylist_overtime', $post['id_hairstylist_overtime'])->delete();
        if($delete){
            return MyHelper::checkDelete($delete);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function detailOvertime(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_hairstylist_overtime']) && !empty($post['id_hairstylist_overtime'])){
            $time_off = HairstylistOverTime::where('id_hairstylist_overtime', $post['id_hairstylist_overtime'])->with(['hair_stylist','outlet','approve','request'])->first();
            
            if($time_off==null){
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => $time_off,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function updateOvertime(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_hairstylist_overtime']) && !empty($post['id_hairstylist_overtime'])){
            $data_update = [];
            if(isset($post['id_hs'])){
                $data_update['id_user_hair_stylist'] = $post['id_hs'];
            }
            if(isset($post['id_outlet'])){
                $data_update['id_outlet'] = $post['id_outlet'];
            }
            if(isset($post['date'])){
                $data_update['date'] = $post['date'];
            }
            if(isset($post['time'])){
                $data_update['time'] =$post['time'];
            }
            if(isset($post['duration'])){
                $data_update['duration'] = date('H:i:s', strtotime($post['duration']));
            }
            
            if(isset($post['approve'])){
                $data_update['approve_by'] = auth()->user()->id;
                $data_update['approve_at'] = date('Y-m-d');
            }

            
            if($data_update){
                DB::beginTransaction();
                $update = HairstylistOverTime::where('id_hairstylist_overtime',$post['id_hairstylist_overtime'])->update($data_update);
                if(!$update){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'success', 
                        'messages' => ['Failed to updated a request hair stylist overtime']
                    ]);
                }
                $update_schedule = $this->updatedScheduleOvertime($data_update);
                if(!$update_schedule){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'success', 
                        'messages' => ['Failed to updated a request hair stylist overtime']
                    ]);
                }
                DB::commit();
                return response()->json([
                    'status' => 'success'
                ]);
            }
            
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function updatedScheduleOvertime($data){
        //get schedule
        $month_sc = date('m', strtotime($data['date']));
        $year_sc = date('Y', strtotime($data['date']));
        $get_schedule = HairstylistSchedule::where('id_user_hair_stylist', $data['id_user_hair_stylist'])->where('schedule_month', $month_sc)->where('schedule_year',$year_sc)->first();
        if($get_schedule){
            $get_schedule_date = HairstylistScheduleDate::where('id_hairstylist_schedule',$get_schedule['id_hairstylist_schedule'])->where('date',$data['date'])->first();
            if($get_schedule_date){
                //update
                if($data['time']=='before'){
                    $duration = strtotime($data['duration']);
                    $start = strtotime($get_schedule_date['time_start']);
                    $diff = $start - $duration;
                    $hour = floor($diff / (60*60));
                    $minute = floor(($diff - ($hour*60*60))/(60));
                    $second = floor(($diff - ($hour*60*60))%(60));
                    $new_time =  date('H:i:s', strtotime($hour.':'.$minute.':'.$second));
                    $order = 'time_start';
                }elseif($data['time']=='after'){
                    $secs = strtotime($data['duration'])-strtotime("00:00:00");
                    $new_time = date("H:i:s",strtotime($get_schedule_date['time_end'])+$secs);
                    $order = 'time_end';
                }else{
                    return false;
                }

                $update_date = HairstylistScheduleDate::where('id_hairstylist_schedule_date',$get_schedule_date['id_hairstylist_schedule_date'])->update([$order => $new_time]);
                if($update_date){
                    return true;
                }
            }
        }
        return false;
    }
}
