<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;
use Modules\Employee\Entities\Employee;
use Modules\Employee\Entities\EmployeeDocuments;
use Modules\Employee\Entities\EmployeeFamily;
use Modules\Employee\Entities\EmployeeEducation;
use Modules\Employee\Entities\EmployeeEducationNonFormal;
use Modules\Employee\Entities\EmployeeJobExperience;
use Modules\Employee\Entities\EmployeeQuestions;
use Modules\Employee\Http\Requests\users_create;
use Modules\Employee\Http\Requests\status_approved;
use Modules\Employee\Http\Requests\users_create_be;
use App\Http\Models\User;
use Session;
use Modules\Disburse\Entities\BankName;
use App\Lib\Icount;
use DB;
use App\Http\Models\Outlet;
use File;
use Storage;
use Modules\Employee\Entities\EmployeeRoleFixedIncentive;
use Modules\Employee\Entities\EmployeeRoleFixedIncentiveDefault;
use Modules\Employee\Entities\EmployeeRoleFixedIncentiveDefaultDetail;
use Modules\Employee\Http\Requests\Income\Fixed_incentive\CreateDefault;
use Modules\Employee\Http\Requests\Income\Fixed_incentive\CreateFixedIncentive;
use Modules\Employee\Http\Requests\Income\Fixed_incentive\Type2;
use Modules\Employee\Http\Requests\Income\Fixed_incentive\UpdateDefault;

class ApiFixedIncentiveController extends Controller
{
   public function create(CreateFixedIncentive $request)
    {
        $data = EmployeeRoleFixedIncentive::where([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_fixed_incentive_detail"   =>  $request->id_employee_role_default_fixed_incentive_detail,
                ])->first();
        if($data){
            if(isset($request->value)){
                $store = EmployeeRoleFixedIncentive::where([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_fixed_incentive_detail"   =>  $request->id_employee_role_default_fixed_incentive_detail,
                ])->update([
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = EmployeeRoleFixedIncentive::where([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_fixed_incentive_detail"   =>  $request->id_employee_role_default_fixed_incentive_detail,
                ])->first();
                if($store){
                  $store = EmployeeRoleFixedIncentive::where([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_fixed_incentive_detail"   =>  $request->id_employee_role_default_fixed_incentive_detail,
                ])->delete();  
                }else{
                  $store = 1;  
                }
            }
        }else{
            if(isset($request->value)){
                $store = EmployeeRoleFixedIncentive::create([
                    "id_role"   =>  $request->id_role,
                    "id_employee_role_default_fixed_incentive_detail"   =>  $request->id_employee_role_default_fixed_incentive_detail,
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = 1;
            }
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateFixedIncentive $request)
    {
        $store = EmployeeRoleFixedIncentive::where(array('id_employee_role_overtime'=>$request->id_employee_role_overtime))->update([
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = EmployeeRoleFixedIncentive::where(array('id_employee_role_overtime'=>$request->id_employee_role_overtime))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_employee_role_overtime){
        $store = EmployeeRoleFixedIncentive::where(array('id_employee_role_overtime'=>$request->id_employee_role_overtime))
                    ->join('employee_role_default_fixed_incentive','employee_role_default_fixed_incentive.id_employee_role_default_fixed_incentive','employee_role_fixed_incentive.id_employee_role_default_fixed_incentive')
                    ->select('employee_role_default_fixed_incentive.name','employee_role_fixed_incentive.*')
                    ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_employee_role_default_fixed_incentive && $request->id_employee_role ){
        $store = EmployeeRoleFixedIncentive::where(array('id_employee_role_default_fixed_incentive'=>$request->id_employee_role_default_fixed_incentive,'id_role'=>$request->id_role))->first();
        if($store){
        $store = EmployeeRoleFixedIncentive::where(array('id_employee_role_default_fixed_incentive'=>$request->id_employee_role_default_fixed_incentive,'id_role'=>$request->id_role))->delete();
        }else{
            $store = 1;
        }
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function index(Request $request) {
        $overtime = [];
        if($request->id_role){
            $data = array();
            $overtime = EmployeeRoleFixedIncentiveDefault::with(['detail'])->get();
            foreach ($overtime as $value) {
                $last = count($value['detail']);
                $x = 0;
                $i = 0;
                foreach ($value['detail'] as $va) {
                  $insen = EmployeeRoleFixedIncentive::where(array('id_employee_role_default_fixed_incentive_detail'=>$va['id_employee_role_default_fixed_incentive_detail'],'id_role'=>$request->id_role))->first();
                    $va['default_value'] = $va['value'];
                    $va['default']    = 0;
                    if($insen){
                       $va['value']      = $insen->value; 
                       $va['default']    = 1;
                    }
                if($value['type']=="Multiple"){
                      $i+1;
                   if($last == 1){
                        $va['ranges'] = " >= ".$va['range'];
                        $data[] = array(
                            'id_employee_role_default_fixed_incentive_detail'=>null,
                            'id_employee_role_default_fixed_incentive'=>null,
                            'value'=>0,  
                            'range'=>0,  
                            'ranges'=>$value['range'].' - 0',  
                            'default'=>1
                          );
                    }elseif(++$i === $last) {
                       $x--;
                    $va['ranges'] = $va['range'].' - '.$x;
                    $va['range'] = $va['range'] - 1;
                    if($va['range']>=0){
                    $value['detail'][] = array(
                      'id_employee_role_default_fixed_incentive_detail'=>null,
                      'id_employee_role_default_fixed_incentive'=>null,
                      'value'=>0,  
                      'default_value'=>0,  
                      'default'=>0,  
                      'range'=>0,  
                      'ranges'=>$va['range'].' - 0',  
                    );
                    }
                  }else{
                      if($i == 1){
                          $x = $va['range'];
                          $va['ranges'] = ">= ".$va['range'];
                      }else{
                     $b = $va['range'];
                     $x--;
                     $va['ranges'] = $x.' - '.$va['range'];
                      $x = $b;
                      }
                  }
                }
              }
            }
           return response()->json(MyHelper::checkGet($overtime));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
   
    public function list_rumus_overtime(Request $request) {
        if($request->id_role){
             $list = array();
             $data = EmployeeRoleFixedIncentiveDefault::all();
             foreach ($data as $value) {
                 $cek = EmployeeRoleFixedIncentive::where(array('id_role'=>$request->id_role,'id_employee_role_default_fixed_incentive'=>$value['id_employee_role_default_fixed_incentive']))->first();
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
    
    public function create_default(CreateDefault $request)
    {
        $store = EmployeeRoleFixedIncentiveDefault::create([
                    'name_fixed_incentive' => $request->name_fixed_incentive,
                    'status' => $request->status,
                    'type' => $request->type,
                    'formula'=> $request->formula,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefault $request)
    {
        $store = EmployeeRoleFixedIncentiveDefault::where(array('id_employee_role_default_fixed_incentive'=>$request->id_employee_role_default_fixed_incentive))->update([
                    'name_fixed_incentive' => $request->name_fixed_incentive,
                    'status' => $request->status,
                    'type' => $request->type,
                    'formula'=> $request->formula,
                ]);
        if($store){
            $store = EmployeeRoleFixedIncentiveDefault::where(array('id_employee_role_default_fixed_incentive'=>$request->id_employee_role_default_fixed_incentive))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_employee_role_default_fixed_incentive){
        $store = EmployeeRoleFixedIncentiveDefault::where(array('id_employee_role_default_fixed_incentive'=>$request->id_employee_role_default_fixed_incentive))->with(['detail'])->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_employee_role_default_fixed_incentive){
        $store = EmployeeRoleFixedIncentiveDefault::where(array('id_employee_role_default_fixed_incentive'=>$request->id_employee_role_default_fixed_incentive))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = EmployeeRoleFixedIncentiveDefault::get();
            return response()->json(MyHelper::checkGet($data));
    }
    function index_default_detail(Request $request) 
    {
    	$post = $request->json()->all();
        $store = EmployeeRoleFixedIncentiveDefault::where(array('id_employee_role_default_fixed_incentive'=>$post['id_employee_role_default_fixed_incentive']))->with(['detail'])->first();
        if($store){
            if($store->type == 'Single'){
                $data = EmployeeRoleFixedIncentiveDefaultDetail::where('id_employee_role_default_fixed_incentive',$post['id_employee_role_default_fixed_incentive'])->first();
            }else{
              $data = EmployeeRoleFixedIncentiveDefaultDetail::where('id_employee_role_default_fixed_incentive',$post['id_employee_role_default_fixed_incentive'])->orderby('range','desc')->get();
                $last = count($data);
                $x = 0;
                $i = 0;
                foreach ($data as $key => $value) {
                    $i++;
                    if($last == 1){
                        $value['ranges'] = " >= ".$value['range'];
                        $data[] = array(
                            'id_employee_role_default_fixed_incentive_detail'=>null,
                            'id_employee_role_default_fixed_incentive'=>null,
                            'value'=>0,  
                            'range'=>0,  
                            'ranges'=>$value['range'].' - 0',  
                            'default'=>1
                          );
                    }elseif($i === $last) {
                       $x--;
                    $value['ranges'] = $value['range'].' - '.$x;
                    $value['range'] = $value['range'] - 1;
                    if($value['range']>=0){
                    $data[] = array(
                      'id_employee_role_default_fixed_incentive_detail'=>null,
                      'id_employee_role_default_fixed_incentive'=>null,
                      'value'=>0,  
                      'range'=>0,  
                      'ranges'=>$value['range'].' - 0',  
                      'default'=>1
                    );
                    }
                  }else{
                      if($i == 1){
                          $x = $value['range'];
                          $value['ranges'] = " >= ".$value['range'];
                      }else{
                     $b = $value['range'];
                     $x--;
                     $value['ranges'] = $x.' - '.$value['range'];
                      $x = $b;
                      }
                  }
                }
            }
            return response()->json(MyHelper::checkGet($data));
        }
         return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
       
    }
    public function sortDate($a, $b) 
    {
        if ($a['range'] == $b['range']) return 0;
        return $a['range'] > $b['range'] ?-1:1;
    }
    function type1(Request $request) {
        $store = EmployeeRoleFixedIncentiveDefaultDetail::where([
                    'id_employee_role_default_fixed_incentive' => $request->id_employee_role_default_fixed_incentive,
                    
                ])->first();
        if($store){
             $store = EmployeeRoleFixedIncentiveDefaultDetail::where([
                    'id_employee_role_default_fixed_incentive' => $request->id_employee_role_default_fixed_incentive
                ])->update([
                    'value' => $request->value,
                ]);
        }else{
            $store = EmployeeRoleFixedIncentiveDefaultDetail::create([
                    'id_employee_role_default_fixed_incentive' => $request->id_employee_role_default_fixed_incentive,
                    'value' => $request->value,
                ]);
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    function type2(Type2 $request) {
       $store = EmployeeRoleFixedIncentiveDefaultDetail::create([
                    'id_employee_role_default_fixed_incentive' => $request->id_employee_role_default_fixed_incentive,
                    'range' => $request->range,
                    'value'=> $request->value,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function delete_detail(Request $request)
    {
        if($request->id_employee_role_default_fixed_incentive_detail ){
        $store = EmployeeRoleFixedIncentiveDefaultDetail::where(array('id_employee_role_default_fixed_incentive_detail'=>$request->id_employee_role_default_fixed_incentive_detail))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
}
