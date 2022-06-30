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
use Modules\Employee\Entities\EmployeeRoleSalaryCut;
use Modules\Employee\Entities\EmployeeRoleSalaryCutDefault;
use Modules\Employee\Http\Requests\Income\Salary_cut\CreateSalaryCut;
use Modules\Employee\Http\Requests\Income\Salary_cut\CreateSalaryCutDefault;
use Modules\Employee\Http\Requests\Income\Salary_cut\UpdateDefaultSalaryCut;
use Modules\Employee\Http\Requests\Income\SalaryCut\UpdateSalaryCut;

class ApiSalaryCutController extends Controller
{
    public function create(CreateSalaryCut $request)
    {
        $data = EmployeeRoleSalaryCut::where([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_salary_cut"   =>  $request->id_employee_role_default_salary_cut,
                ])->first();
        if($data){
            $store = EmployeeRoleSalaryCut::where([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_salary_cut"   =>  $request->id_employee_role_default_salary_cut,
                ])->update([
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        }else{
        $store = EmployeeRoleSalaryCut::create([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_salary_cut"   =>  $request->id_employee_role_default_salary_cut,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateSalaryCut $request)
    {
        $store = EmployeeRoleSalaryCut::where(array('id_employee_role_salary_cut'=>$request->id_employee_role_salary_cut))->update([
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                    "code"   =>  $request->code,
                ]);
        if($store){
            $store = EmployeeRoleSalaryCut::where(array('id_employee_role_salary_cut'=>$request->id_employee_role_salary_cut))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_employee_role_salary_cut){
        $store = EmployeeRoleSalaryCut::where(array('id_employee_role_salary_cut'=>$request->id_employee_role_salary_cut))
                    ->join('employee_role_default_salary_cuts','employee_role_default_salary_cuts.id_employee_role_default_salary_cut','employee_role_salary_cut.id_employee_role_default_salary_cut')
                    ->select('employee_role_default_salary_cuts.name','employee_role_salary_cuts.*')
                    ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_employee_role_default_salary_cut && $request->id_role ){
        $store = EmployeeRoleSalaryCut::where(array('id_employee_role_default_salary_cut'=>$request->id_employee_role_default_salary_cut,'id_role'=>$request->id_role))->first();
        if($store){
        $store = EmployeeRoleSalaryCut::where(array('id_employee_role_default_salary_cut'=>$request->id_employee_role_default_salary_cut,'id_role'=>$request->id_role))->delete();
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
            $salary_cut = EmployeeRoleSalaryCutDefault::get();
            foreach ($salary_cut as $value) {
                $insen = EmployeeRoleSalaryCut::where(array('id_employee_role_default_salary_cut'=>$value['id_employee_role_default_salary_cut'],'id_role'=>$request->id_role))->first();
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
   
    public function list_rumus_salary_cut(Request $request) {
        if($request->id_role){
             $list = array();
             $data = EmployeeRoleSalaryCutDefault::all();
             foreach ($data as $value) {
                 $cek = EmployeeRoleSalaryCut::where(array('id_role'=>$request->id_role,'id_employee_role_default_salary_cut'=>$value['id_employee_role_default_salary_cut']))->first();
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
    public function create_default(CreateSalaryCutDefault $request)
    {
        $store = EmployeeRoleSalaryCutDefault::create([
                    "name"   =>  $request->name,
                    "code"   => $request->code,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefaultSalaryCut $request)
    {
        $store = EmployeeRoleSalaryCutDefault::where(array('id_employee_role_default_salary_cut'=>$request->id_employee_role_default_salary_cut))->update([
                    "name"   =>  $request->name,
                    "code"   => $request->code,
                    "value"   =>  $request->value,
                    "formula"   =>  $request->formula,
                ]);
        if($store){
            $store = EmployeeRoleSalaryCutDefault::where(array('id_employee_role_default_salary_cut'=>$request->id_employee_role_default_salary_cut))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_employee_role_default_salary_cut){
        $store = EmployeeRoleSalaryCutDefault::where(array('id_employee_role_default_salary_cut'=>$request->id_employee_role_default_salary_cut))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_employee_role_default_salary_cut){
        $store = EmployeeRoleSalaryCutDefault::where(array('id_employee_role_default_salary_cut'=>$request->id_employee_role_default_salary_cut))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = EmployeeRoleSalaryCutDefault::Select('employee_role_default_salary_cuts.*');
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
    public function list_salary_cut_default(Request $request) {
        if($request->id_role){
            $data = array();
            $salary_cut = EmployeeRoleSalaryCutDefault::where(array('id_role'=>$request->id_role))->get();
            foreach ($salary_cut as $value) {
                $insen = EmployeeRoleSalaryCut::where(array('id_employee_role_default_salary_cut'=>$value['id_employee_role_default_salary_cut'],'id_role'=>$request->id_role))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
}
