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
use Modules\Recruitment\Http\Requests\CreateOvertime;
use Modules\Recruitment\Http\Requests\CreateOvertimeDefault;
use Modules\Recruitment\Http\Requests\UpdateDefaultOvertime;
use Modules\Recruitment\Http\Requests\InviteHS;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use Modules\Recruitment\Entities\HairstylistGroupOvertime;
use Modules\Recruitment\Entities\HairstylistGroupOvertimeDefault;
use App\Http\Models\Product;
use Modules\Recruitment\Http\Requests\UpdateOvertime;
class ApiHairStylistGroupOvertimeController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
//        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
    public function create(CreateOvertime $request)
    {
        $data = HairstylistGroupOvertime::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_overtimes"   =>  $request->id_hairstylist_group_default_overtimes,
                ])->first();
        if($data){
            if(isset($request->value)){
                $store = HairstylistGroupOvertime::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_overtimes"   =>  $request->id_hairstylist_group_default_overtimes,
                ])->update([
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = HairstylistGroupOvertime::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_overtimes"   =>  $request->id_hairstylist_group_default_overtimes,
                ])->delete();
            }
        }else{
            if(isset($request->value)){
                $store = HairstylistGroupOvertime::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_overtimes"   =>  $request->id_hairstylist_group_default_overtimes,
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = [];
            }
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateOvertime $request)
    {
        $store = HairstylistGroupOvertime::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))->update([
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = HairstylistGroupOvertime::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group_overtime){
        $store = HairstylistGroupOvertime::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))
                    ->join('hairstylist_group_default_overtimes','hairstylist_group_default_overtimes.id_hairstylist_group_default_overtimes','hairstylist_group_overtimes.id_hairstylist_group_default_overtimes')
                    ->select('hairstylist_group_default_overtimes.name','hairstylist_group_overtimes.*')
                    ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_hairstylist_group_default_overtimes && $request->id_hairstylist_group ){
        $store = HairstylistGroupOvertime::where(array('id_hairstylist_group_default_overtimes'=>$request->id_hairstylist_group_default_overtimes,'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
        if($store){
        $store = HairstylistGroupOvertime::where(array('id_hairstylist_group_default_overtimes'=>$request->id_hairstylist_group_default_overtimes,'id_hairstylist_group'=>$request->id_hairstylist_group))->delete();
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
            $overtime = HairstylistGroupOvertimeDefault::orderby('days','asc')->get();
            foreach ($overtime as $value) {
                $insen = HairstylistGroupOvertime::where(array('id_hairstylist_group_default_overtimes'=>$value['id_hairstylist_group_default_overtimes'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
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
             $data = HairstylistGroupOvertimeDefault::all();
             foreach ($data as $value) {
                 $cek = HairstylistGroupOvertime::where(array('id_hairstylist_group'=>$request->id_hairstylist_group,'id_hairstylist_group_default_overtimes'=>$value['id_hairstylist_group_default_overtimes']))->first();
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
    
    public function create_default(CreateOvertimeDefault $request)
    {
        $store = HairstylistGroupOvertimeDefault::create([
                    "days"   => $request->days,
                    "value"   =>  $request->value,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefaultOvertime $request)
    {
        $store = HairstylistGroupOvertimeDefault::where(array('id_hairstylist_group_default_overtimes'=>$request->id_hairstylist_group_default_overtimes))->update([
                     "days"   => $request->days,
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = HairstylistGroupOvertimeDefault::where(array('id_hairstylist_group_default_overtimes'=>$request->id_hairstylist_group_default_overtimes))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_hairstylist_group_default_overtimes){
        $store = HairstylistGroupOvertimeDefault::where(array('id_hairstylist_group_default_overtimes'=>$request->id_hairstylist_group_default_overtimes))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_hairstylist_group_default_overtimes){
        $store = HairstylistGroupOvertimeDefault::where(array('id_hairstylist_group_default_overtimes'=>$request->id_hairstylist_group_default_overtimes))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = HairstylistGroupOvertimeDefault::orderby('days','asc')->Select('hairstylist_group_default_overtimes.*');
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
            $overtime = HairstylistGroupOvertimeDefault::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->get();
            foreach ($overtime as $value) {
                $insen = HairstylistGroupOvertime::where(array('id_hairstylist_group_default_overtimes'=>$value['id_hairstylist_group_default_overtimes'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    
}
