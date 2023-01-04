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
use Modules\Recruitment\Http\Requests\CreateProteksiAttendance;
use Modules\Recruitment\Http\Requests\CreateProteksiAttendanceDefault;
use Modules\Recruitment\Http\Requests\UpdateDefaultOvertime;
use Modules\Recruitment\Http\Requests\InviteHS;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use Modules\Recruitment\Entities\HairstylistGroupProteksiAttendance;
use Modules\Recruitment\Entities\HairstylistGroupProteksiAttendanceDefault;
use App\Http\Models\Product;
use Modules\Recruitment\Http\Requests\UpdateProteksiAttendance;
class ApiHairStylistGroupProteksiAttendanceController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
//        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
    public function create(CreateProteksiAttendance $request)
    {
        $data = HairstylistGroupProteksiAttendance::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_proteksi_attendance"   =>  $request->id_hairstylist_group_default_proteksi_attendance,
                ])->first();
        if($data){
            if(isset($request->value)||isset($request->amount)||isset($request->amount_day)){
                $store = HairstylistGroupProteksiAttendance::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_proteksi_attendance"   =>  $request->id_hairstylist_group_default_proteksi_attendance,
                ])->update([
                    "value"   =>  $request->value,
                    "amount"   =>  $request->amount,
                    "amount_proteksi"   =>  $request->amount_proteksi,
                    "amount_day"   =>  $request->amount_day,
                ]);
            }else{
                $store = HairstylistGroupProteksiAttendance::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_proteksi_attendance"   =>  $request->id_hairstylist_group_default_proteksi_attendance,
                ])->delete();
            }
        }else{
            if(isset($request->value)||isset($request->amount)||isset($request->amount_day)||isset($request->amount_proteksi)){
                $store = HairstylistGroupProteksiAttendance::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_proteksi_attendance"   =>  $request->id_hairstylist_group_default_proteksi_attendance,
                    "value"   =>  $request->value,
                    "amount"   =>  $request->amount,
                    "amount_proteksi"   =>  $request->amount_proteksi,
                    "amount_day"   =>  $request->amount_day,
                ]);
            }else{
                $store = [];
            }
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateProteksiAttendance $request)
    {
        $store = HairstylistGroupProteksiAttendance::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))->update([
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = HairstylistGroupProteksiAttendance::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group_overtime){
        $store = HairstylistGroupProteksiAttendance::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))
                    ->join('hairstylist_group_default_proteksi_attendances','hairstylist_group_default_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance','hairstylist_group_overtimes.id_hairstylist_group_default_proteksi_attendance')
                    ->select('hairstylist_group_default_proteksi_attendances.name','hairstylist_group_overtimes.*')
                    ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_hairstylist_group_default_proteksi_attendance && $request->id_hairstylist_group ){
        $store = HairstylistGroupProteksiAttendance::where(array('id_hairstylist_group_default_proteksi_attendance'=>$request->id_hairstylist_group_default_proteksi_attendance,'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
        if($store){
        $store = HairstylistGroupProteksiAttendance::where(array('id_hairstylist_group_default_proteksi_attendance'=>$request->id_hairstylist_group_default_proteksi_attendance,'id_hairstylist_group'=>$request->id_hairstylist_group))->delete();
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
            $overtime = HairstylistGroupProteksiAttendanceDefault::orderby('month','asc')->get();
            foreach ($overtime as $value) {
               $insen = HairstylistGroupProteksiAttendance::where(array('id_hairstylist_group_default_proteksi_attendance'=>$value['id_hairstylist_group_default_proteksi_attendance'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                $value['name_month'] = date('M', strtotime(date('Y-'.$value['month'])));
                $value['default_value'] = $value['value'];
                $value['default']    = 0;
                $value['default_amount'] = $value['amount'];
                $value['amount_default']    = 0;
                $value['default_amount_day'] = $value['amount_day'];
                $value['amount_day_default']    = 0;
                $value['default_amount_proteksi'] = $value['amount_proteksi'];
                $value['amount_proteksi_default']    = 0;
                if(isset($insen->value)){
                   $value['value']      = $insen->value; 
                   $value['default']    = 1;
                }
                if(isset($insen->amount)){
                   $value['default_amount']      = $insen->amount; 
                   $value['amount_default']    = 1;
                }
                if(isset($insen->amount_day)){
                   $value['default_amount_day']      = $insen->amount_day; 
                   $value['amount_day_default']    = 1;
                }
                if(isset($insen->amount_proteksi)){
                   $value['default_amount_proteksi']      = $insen->amount_proteksi; 
                   $value['amount_proteksi_default']    = 1;
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
             $data = HairstylistGroupProteksiAttendanceDefault::all();
             foreach ($data as $value) {
                 $cek = HairstylistGroupProteksiAttendance::where(array('id_hairstylist_group'=>$request->id_hairstylist_group,'id_hairstylist_group_default_proteksi_attendance'=>$value['id_hairstylist_group_default_proteksi_attendance']))->first();
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
    
    public function create_default(CreateProteksiAttendanceDefault $request)
    {
        $store = HairstylistGroupProteksiAttendanceDefault::where('month',$request->month)->first();
        if($store){
            $store->value = $request->value;
            $store->amount = $request->amount;
            $store->amount_day = $request->amount_day;
            $store->amount_proteksi = $request->amount_proteksi;
            $store->save();
        }else{
            $store = HairstylistGroupProteksiAttendanceDefault::create([
                    "month"   => $request->month,
                    "value"   =>  $request->value,
                    "amount"   =>  $request->amount,
                    "amount_proteksi"   =>  $request->amount_proteksi,
                    "amount_day"   =>  $request->amount_day,
                ]);
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefaultOvertime $request)
    {
        $store = HairstylistGroupProteksiAttendanceDefault::where(array('id_hairstylist_group_default_proteksi_attendance'=>$request->id_hairstylist_group_default_proteksi_attendance))->update([
                     "month"   => $request->month,
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = HairstylistGroupProteksiAttendanceDefault::where(array('id_hairstylist_group_default_proteksi_attendance'=>$request->id_hairstylist_group_default_proteksi_attendance))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_hairstylist_group_default_proteksi_attendance){
        $store = HairstylistGroupProteksiAttendanceDefault::where(array('id_hairstylist_group_default_proteksi_attendance'=>$request->id_hairstylist_group_default_proteksi_attendance))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_hairstylist_group_default_proteksi_attendance){
        $store = HairstylistGroupProteksiAttendanceDefault::where(array('id_hairstylist_group_default_proteksi_attendance'=>$request->id_hairstylist_group_default_proteksi_attendance))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = HairstylistGroupProteksiAttendanceDefault::orderby('month','asc')->Select('hairstylist_group_default_proteksi_attendances.*');
        $data = $data->get();
        $m = 12;
        $data = array();
        for($i=1;$i <= 12;$i++){
            if($i>9){
                $d = '2020-'.$i.'-01';
            }else{
                $d = '2020-0'.$i.'-01';
            }
            $m = HairstylistGroupProteksiAttendanceDefault::where('month',date('m', strtotime($d)))->first();
            $b = array(
                'month'=> date('m', strtotime($d)),
                'name_month'=> date('M', strtotime($d)),
                'value'=>$m->value??null,
                'amount'=>$m->amount??null,
                'amount_proteksi'=>$m->amount_proteksi??null,
                'amount_day'=>$m->amount_day??null
            );
            array_push($data, $b);
        }
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
            $overtime = HairstylistGroupProteksiAttendanceDefault::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->get();
            foreach ($overtime as $value) {
                $insen = HairstylistGroupProteksiAttendance::where(array('id_hairstylist_group_default_proteksi_attendance'=>$value['id_hairstylist_group_default_proteksi_attendance'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    
}
