<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\UserHairStylistDocuments;
use Modules\Recruitment\Http\Requests\user_hair_stylist_create;
use Modules\Recruitment\Http\Requests\CreateGroup;
use Modules\Recruitment\Http\Requests\CreateGroupCommission;
use Modules\Recruitment\Http\Requests\UpdateGroupCommission;
use Modules\Recruitment\Http\Requests\CreateOvertimeDay;
use Modules\Recruitment\Http\Requests\CreateOvertimeDayDefault;
use Modules\Recruitment\Http\Requests\UpdateDefaultOvertime;
use Modules\Recruitment\Http\Requests\InviteHS;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use Modules\Recruitment\Entities\HairstylistGroupOvertimeDay;
use Modules\Recruitment\Entities\HairstylistGroupOvertimeDayDefault;
use App\Http\Models\Product;
use Modules\Recruitment\Http\Requests\UpdateOvertimeDay;
class ApiHairStylistGroupOvertimeDayController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
//        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
    public function create(CreateOvertimeDay $request)
    {
        $data = HairstylistGroupOvertimeDay::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_overtime_day"   =>  $request->id_hairstylist_group_default_overtime_day,
                ])->first();
        if($data){
            if(isset($request->value)){
                $store = HairstylistGroupOvertimeDay::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_overtime_day"   =>  $request->id_hairstylist_group_default_overtime_day,
                ])->update([
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = HairstylistGroupOvertimeDay::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_overtime_day"   =>  $request->id_hairstylist_group_default_overtime_day,
                ])->delete();
            }
        }else{
            if(isset($request->value)){
                $store = HairstylistGroupOvertimeDay::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_overtime_day"   =>  $request->id_hairstylist_group_default_overtime_day,
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = [];
            }
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateOvertimeDay $request)
    {
        $store = HairstylistGroupOvertimeDay::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))->update([
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = HairstylistGroupOvertimeDay::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group_overtime){
        $store = HairstylistGroupOvertimeDay::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))
                    ->join('hairstylist_group_default_overtime_days','hairstylist_group_default_overtime_days.id_hairstylist_group_default_overtime_day','hairstylist_group_overtimes.id_hairstylist_group_default_overtime_day')
                    ->select('hairstylist_group_default_overtime_days.name','hairstylist_group_overtimes.*')
                    ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_hairstylist_group_default_overtime_day && $request->id_hairstylist_group ){
        $store = HairstylistGroupOvertimeDay::where(array('id_hairstylist_group_default_overtime_day'=>$request->id_hairstylist_group_default_overtime_day,'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
        if($store){
        $store = HairstylistGroupOvertimeDay::where(array('id_hairstylist_group_default_overtime_day'=>$request->id_hairstylist_group_default_overtime_day,'id_hairstylist_group'=>$request->id_hairstylist_group))->delete();
        }else{
            $store = 1;
        }
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function index(Request $request) {
        if($request->id_hairstylist_group){
            $data = array();
            $overtime = HairstylistGroupOvertimeDayDefault::orderby('days','asc')->get();
            foreach ($overtime as $value) {
                $insen = HairstylistGroupOvertimeDay::where(array('id_hairstylist_group_default_overtime_day'=>$value['id_hairstylist_group_default_overtime_day'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                $value['default_value'] = $value['value'];
                $value['default']    = 0;
                if($insen){
                   $value['value']      = $insen->value; 
                   $value['default']    = 1;
                }
                array_push($data,$value);
            }
           return response()->json(MyHelper::checkGet($data));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
   
    public function list_rumus_overtime(Request $request) {
        if($request->id_hairstylist_group){
             $list = array();
             $data = HairstylistGroupOvertimeDayDefault::all();
             foreach ($data as $value) {
                 $cek = HairstylistGroupOvertimeDay::where(array('id_hairstylist_group'=>$request->id_hairstylist_group,'id_hairstylist_group_default_overtime_day'=>$value['id_hairstylist_group_default_overtime_day']))->first();
                 if($cek){
                     $value['value']   = $cek->value;
                     $value['formula'] = $cek->formula;
                     $value['code']    = $cek->code;
                 }
                 array_push($list,$value);
             }
             return MyHelper::checkGet($list);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    
    public function create_default(CreateOvertimeDayDefault $request)
    {
        $store = HairstylistGroupOvertimeDayDefault::create([
                    "days"   => $request->days,
                    "value"   =>  $request->value,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefaultOvertime $request)
    {
        $store = HairstylistGroupOvertimeDayDefault::where(array('id_hairstylist_group_default_overtime_day'=>$request->id_hairstylist_group_default_overtime_day))->update([
                     "days"   => $request->days,
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = HairstylistGroupOvertimeDayDefault::where(array('id_hairstylist_group_default_overtime_day'=>$request->id_hairstylist_group_default_overtime_day))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_hairstylist_group_default_overtime_day){
        $store = HairstylistGroupOvertimeDayDefault::where(array('id_hairstylist_group_default_overtime_day'=>$request->id_hairstylist_group_default_overtime_day))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_hairstylist_group_default_overtime_day){
        $store = HairstylistGroupOvertimeDayDefault::where(array('id_hairstylist_group_default_overtime_day'=>$request->id_hairstylist_group_default_overtime_day))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = HairstylistGroupOvertimeDayDefault::orderby('days','asc')->Select('hairstylist_group_default_overtime_days.*');
        $data = $data->get();
            return response()->json(MyHelper::checkGet($data));
    }
   
    public function filterList($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }
        $where=$operator=='and'?'where':'orWhere';
        $subjects=['name'];
         $i = 1;
        foreach ($subjects as $subject) {
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    if($i<=1){
                    $query->where($subject,$rule[0],$rule[1]);
                    }else{
                    $query->$where($subject,$rule[0],$rule[1]);    
                    }
                    $i++;
                }
            }
        }
    }
    public function list_overtime_default(Request $request) {
        if($request->id_hairstylist_group){
            $data = array();
            $overtime = HairstylistGroupOvertimeDayDefault::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->get();
            foreach ($overtime as $value) {
                $insen = HairstylistGroupOvertimeDay::where(array('id_hairstylist_group_default_overtime_day'=>$value['id_hairstylist_group_default_overtime_day'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    
}
