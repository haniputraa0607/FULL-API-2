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
use Modules\Recruitment\Http\Requests\CreateLate;
use Modules\Recruitment\Http\Requests\CreateLateDefault;
use Modules\Recruitment\Http\Requests\UpdateDefaultLate;
use Modules\Recruitment\Http\Requests\InviteHS;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use Modules\Recruitment\Entities\HairstylistGroupLate;
use Modules\Recruitment\Entities\HairstylistGroupLateDefault;
use App\Http\Models\Product;
use Modules\Recruitment\Http\Requests\UpdateLate;
class ApiHairStylistGroupLateController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
//        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
    public function create(CreateLate $request)
    {
        $data = HairstylistGroupLate::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_late"   =>  $request->id_hairstylist_group_default_late,
                ])->first();
        if($data){
            if(isset($request->value)){
                $store = HairstylistGroupLate::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_late"   =>  $request->id_hairstylist_group_default_late,
                ])->update([
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = HairstylistGroupLate::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_late"   =>  $request->id_hairstylist_group_default_late,
                ])->delete();
            }
        }else{
            if(isset($request->value)){
                $store = HairstylistGroupLate::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_late"   =>  $request->id_hairstylist_group_default_late,
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = [];
            }
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateLate $request)
    {
        $store = HairstylistGroupLate::where(array('id_hairstylist_group_late'=>$request->id_hairstylist_group_late))->update([
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = HairstylistGroupLate::where(array('id_hairstylist_group_late'=>$request->id_hairstylist_group_late))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group_late){
        $store = HairstylistGroupLate::where(array('id_hairstylist_group_late'=>$request->id_hairstylist_group_late))
                    ->join('hairstylist_group_default_lates','hairstylist_group_default_lates.id_hairstylist_group_default_late','hairstylist_group_lates.id_hairstylist_group_default_late')
                    ->select('hairstylist_group_default_lates.name','hairstylist_group_lates.*')
                    ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_hairstylist_group_default_late && $request->id_hairstylist_group ){
        $store = HairstylistGroupLate::where(array('id_hairstylist_group_default_late'=>$request->id_hairstylist_group_default_late,'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
        if($store){
        $store = HairstylistGroupLate::where(array('id_hairstylist_group_default_late'=>$request->id_hairstylist_group_default_late,'id_hairstylist_group'=>$request->id_hairstylist_group))->delete();
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
            $late = HairstylistGroupLateDefault::orderby('range','desc')->get();
            foreach ($late as $value) {
                $insen = HairstylistGroupLate::where(array('id_hairstylist_group_default_late'=>$value['id_hairstylist_group_default_late'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
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
   
    
    public function create_default(CreateLateDefault $request)
    {
        $store = HairstylistGroupLateDefault::create([
                    "range"   => $request->range,
                    "value"   =>  $request->value,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefaultLate $request)
    {
        $store = HairstylistGroupLateDefault::where(array('id_hairstylist_group_default_late'=>$request->id_hairstylist_group_default_late))->update([
                     "range"   => $request->range,
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = HairstylistGroupLateDefault::where(array('id_hairstylist_group_default_late'=>$request->id_hairstylist_group_default_late))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_hairstylist_group_default_late){
        $store = HairstylistGroupLateDefault::where(array('id_hairstylist_group_default_late'=>$request->id_hairstylist_group_default_late))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_hairstylist_group_default_late){
        $store = HairstylistGroupLateDefault::where(array('id_hairstylist_group_default_late'=>$request->id_hairstylist_group_default_late))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = HairstylistGroupLateDefault::orderby('range','desc')->Select('hairstylist_group_default_lates.*');
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
    public function list_late_default(Request $request) {
        if($request->id_hairstylist_group){
            $data = array();
            $late = HairstylistGroupLateDefault::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->get();
            foreach ($late as $value) {
                $insen = HairstylistGroupLate::where(array('id_hairstylist_group_default_late'=>$value['id_hairstylist_group_default_late'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    
}
