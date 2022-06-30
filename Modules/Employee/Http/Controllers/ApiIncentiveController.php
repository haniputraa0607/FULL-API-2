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
use Modules\Employee\Entities\EmployeeRoleIncentive;
use Modules\Employee\Entities\EmployeeRoleIncentiveDefault;
use Modules\Employee\Http\Requests\Income\Incentive\CreateIncentive;
use Modules\Employee\Http\Requests\Income\Incentive\CreateIncentiveDefault;
use Modules\Employee\Http\Requests\Income\Incentive\UpdateDefaultIncentive;
use Modules\Employee\Http\Requests\Income\Incentive\UpdateIncentive;

class ApiIncentiveController extends Controller
{
    public function create(CreateIncentive $request)
    {
        $data = EmployeeRoleIncentive::where([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_incentive"   =>  $request->id_employee_role_default_incentive,
                ])->first();
        if($data){
            $store = EmployeeRoleIncentive::where([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_incentive"   =>  $request->id_employee_role_default_incentive,
                ])->update([
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        }else{
        $store = EmployeeRoleIncentive::create([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_incentive"   =>  $request->id_employee_role_default_incentive,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateIncentive $request)
    {
        $store = EmployeeRoleIncentive::where(array('id_employee_role_incentive'=>$request->id_employee_role_incentive))->update([
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                    "code"   =>  $request->code,
                ]);
        if($store){
            $store = EmployeeRoleIncentive::where(array('id_employee_role_incentive'=>$request->id_employee_role_incentive))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_employee_role_incentive){
        $store = EmployeeRoleIncentive::where(array('id_employee_role_incentive'=>$request->id_employee_role_incentive))
                    ->join('employee_role_default_incentives','employee_role_default_incentives.id_employee_role_default_incentive','employee_role_incentive.id_employee_role_default_incentive')
                    ->select('employee_role_default_incentives.name','employee_role_incentive.*')
                    ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_employee_role_default_incentive && $request->id_role ){
        $store = EmployeeRoleIncentive::where(array('id_employee_role_default_incentive'=>$request->id_employee_role_default_incentive,'id_role'=>$request->id_role))->first();
        if($store){
        $store = EmployeeRoleIncentive::where(array('id_employee_role_default_incentive'=>$request->id_employee_role_default_incentive,'id_role'=>$request->id_role))->delete();
        }else{
            $store = 1;
        }
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function index(Request $request) {
        if($request->id_role){
            $data = array();
            $incentive = EmployeeRoleIncentiveDefault::get();
            foreach ($incentive as $value) {
                $insen = EmployeeRoleIncentive::where(array('id_employee_role_default_incentive'=>$value['id_employee_role_default_incentive'],'id_role'=>$request->id_role))->first();
                $value['default_formula'] = $value['formula'];
                $value['default_value'] = $value['value'];
                $value['default']    = 0;
                if($insen){
                   $value['value']      = $insen->value; 
                   $value['formula']    = $insen->formula;
                   $value['default']    = 1;
                }
                array_push($data,$value);
            }
           return response()->json(MyHelper::checkGet($data));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
   
    public function list_rumus_incentive(Request $request) {
        if($request->id_role){
             $list = array();
             $data = EmployeeRoleIncentiveDefault::all();
             foreach ($data as $value) {
                 $cek = EmployeeRoleIncentive::where(array('id_role'=>$request->id_role,'id_employee_role_default_incentive'=>$value['id_employee_role_default_incentive']))->first();
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
    public function create_default(CreateIncentiveDefault $request)
    {
        $store = EmployeeRoleIncentiveDefault::create([
                    "name"   =>  $request->name,
                    "code"   => $request->code,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefaultIncentive $request)
    {
        $store = EmployeeRoleIncentiveDefault::where(array('id_employee_role_default_incentive'=>$request->id_employee_role_default_incentive))->update([
                    "name"   =>  $request->name,
                    "code"   => $request->code,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        if($store){
            $store = EmployeeRoleIncentiveDefault::where(array('id_employee_role_default_incentive'=>$request->id_employee_role_default_incentive))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_employee_role_default_incentive){
        $store = EmployeeRoleIncentiveDefault::where(array('id_employee_role_default_incentive'=>$request->id_employee_role_default_incentive))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_employee_role_default_incentive){
        $store = EmployeeRoleIncentiveDefault::where(array('id_employee_role_default_incentive'=>$request->id_employee_role_default_incentive))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = EmployeeRoleIncentiveDefault::Select('employee_role_default_incentives.*');
        if ($request->json('rule')){
             $this->filterList($data,$request->json('rule'),$request->json('operator')??'and');
        }
        $data = $data->paginate($request->length ?: 10);
        //jika mobile di pagination
        if (!$request->json('web')) {
            $resultMessage = 'Data tidak ada';
            return response()->json(MyHelper::checkGet($data, $resultMessage));
        }
        else{
           
            return response()->json(MyHelper::checkGet($data));
        }
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
    public function list_incentive_default(Request $request) {
        if($request->id_role){
            $data = array();
            $incentive = EmployeeRoleIncentiveDefault::where(array('id_role'=>$request->id_role))->get();
            foreach ($incentive as $value) {
                $insen = EmployeeRoleIncentive::where(array('id_employee_role_default_incentive'=>$value['id_employee_role_default_incentive'],'id_role'=>$request->id_role))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
}
