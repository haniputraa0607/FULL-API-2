<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;
use Modules\Employee\Entities\Employee;
use App\Http\Models\User;
use Session;
use App\Lib\Icount;
use App\Http\Models\Outlet;
use Modules\Product\Entities\ProductIcount;
use Validator;
use Modules\Users\Entities\Role;


class ApiIncomeOvertimeController extends Controller
{
    public function create(CreateOvertime $request)
    {
        $data = EmployeeRoleOvertime::where([
                    "id_employee_role"   =>  $request->id_employee_role,
                    "id_employee_role_default_overtime"   =>  $request->id_employee_role_default_overtime,
                ])->first();
        if($data){
            if(isset($request->value)){
                $store = EmployeeRoleOvertime::where([
                    "id_employee_role"   =>  $request->id_employee_role,
                    "id_employee_role_default_overtime"   =>  $request->id_employee_role_default_overtime,
                ])->update([
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = EmployeeRoleOvertime::where([
                    "id_employee_role"   =>  $request->id_employee_role,
                    "id_employee_role_default_overtime"   =>  $request->id_employee_role_default_overtime,
                ])->delete();
            }
        }else{
            if(isset($request->value)){
                $store = EmployeeRoleOvertime::create([
                    "id_employee_role"   =>  $request->id_employee_role,
                    "id_employee_role_default_overtime"   =>  $request->id_employee_role_default_overtime,
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
        $store = EmployeeRoleOvertime::where(array('id_employee_role_overtime'=>$request->id_employee_role_overtime))->update([
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = EmployeeRoleOvertime::where(array('id_employee_role_overtime'=>$request->id_employee_role_overtime))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_employee_role_overtime){
        $store = EmployeeRoleOvertime::where(array('id_employee_role_overtime'=>$request->id_employee_role_overtime))
                    ->join('employee_role_default_overtime','employee_role_default_overtime.id_employee_role_default_overtime','employee_role_overtime.id_employee_role_default_overtime')
                    ->select('employee_role_default_overtime.name','employee_role_overtime.*')
                    ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_employee_role_default_overtime && $request->id_employee_role ){
        $store = EmployeeRoleOvertime::where(array('id_employee_role_default_overtime'=>$request->id_employee_role_default_overtime,'id_employee_role'=>$request->id_employee_role))->first();
        if($store){
        $store = EmployeeRoleOvertime::where(array('id_employee_role_default_overtime'=>$request->id_employee_role_default_overtime,'id_employee_role'=>$request->id_employee_role))->delete();
        }else{
            $store = 1;
        }
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function index(Request $request) {
        if($request->id_employee_role){
            $data = array();
            $overtime = EmployeeRoleOvertimeDefault::get();
            foreach ($overtime as $value) {
                $insen = EmployeeRoleOvertime::where(array('id_employee_role_default_overtime'=>$value['id_employee_role_default_overtime'],'id_employee_role'=>$request->id_employee_role))->first();
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
        if($request->id_employee_role){
             $list = array();
             $data = EmployeeRoleOvertimeDefault::all();
             foreach ($data as $value) {
                 $cek = EmployeeRoleOvertime::where(array('id_employee_role'=>$request->id_employee_role,'id_employee_role_default_overtime'=>$value['id_employee_role_default_overtime']))->first();
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
        $store = EmployeeRoleOvertimeDefault::create([
                    "hours"   => $request->hours,
                    "value"   =>  $request->value,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefaultOvertime $request)
    {
        $store = EmployeeRoleOvertimeDefault::where(array('id_employee_role_default_overtime'=>$request->id_employee_role_default_overtime))->update([
                     "hours"   => $request->hours,
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = EmployeeRoleOvertimeDefault::where(array('id_employee_role_default_overtime'=>$request->id_employee_role_default_overtime))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_employee_role_default_overtime){
        $store = EmployeeRoleOvertimeDefault::where(array('id_employee_role_default_overtime'=>$request->id_employee_role_default_overtime))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_employee_role_default_overtime){
        $store = EmployeeRoleOvertimeDefault::where(array('id_employee_role_default_overtime'=>$request->id_employee_role_default_overtime))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = EmployeeRoleOvertimeDefault::Select('employee_role_default_overtime.*');
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
        if($request->id_employee_role){
            $data = array();
            $overtime = EmployeeRoleOvertimeDefault::where(array('id_employee_role'=>$request->id_employee_role))->get();
            foreach ($overtime as $value) {
                $insen = EmployeeRoleOvertime::where(array('id_employee_role_default_overtime'=>$value['id_employee_role_default_overtime'],'id_employee_role'=>$request->id_employee_role))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
}
