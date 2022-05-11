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
class ApiBeEmployeeController extends Controller
{
   public function create(users_create_be $request) {
       $post = $request->all();
       $post['provider'] = MyHelper::cariOperator($post['phone']);
       $post['id_city'] = $post['id_city_ktp'];
       $post['level'] = "Customer";
       $post['status_step'] = "Register BE";
       $user = User::create($post);
       if($user){
            if($post){
                if(isset($post['relatives'])){
                    $post['relatives'] = 0;
                    $post['relative_name'] = null;
                    $post['relative_position'] = null;
                }
                if($post['birthday']){
                    $post['birthday'] = date('Y-m-d', strtotime($post['birthday']));
                }
                $post['id_user']=$user->id;
                $employee = Employee::create($post);
            }
       }
       $user = User::where('id',$user->id)->with(['employee','employee_family','employee_education','employee_education_non_formal','employee_job_experience','employee_question'])->first();
       return MyHelper::checkGet($user);
   }
   public function index(Request $request) {
        $post = $request->all();
        $employee = User::where(array(
            "employees.status"=>"active",
            "users.level"=>"Admin"
            ))->join('employees','employees.id_user','users.id');
        if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }
            if($rule == 'and'){
                foreach ($post['rule'] as $condition){
                    if(isset($condition['subject'])){               
                        $employee = $employee->where('employees.'.$condition['subject'], $condition['parameter']);
                    }
                }
            }else{
                $employee = $employee->where(function ($q) use ($post){
                    foreach ($post['rule'] as $condition){
                        if(isset($condition['subject'])){
                                 if($condition['operator'] == 'like'){
                                      $q->orWhere('employees.'.$condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                 }else{
                                      $q->orWhere('employees.'.$condition['subject'], $condition['parameter']);
                                 }
                        }
                    }
                });
            }
        }
            $employee = $employee->orderBy('employees.created_at', 'desc')->paginate($request->length ?: 10);
        return MyHelper::checkGet($employee);
   }
    public function detail(Request $request) {
       $post = $request->json()->all();
        if(isset($post['id_employee']) && !empty($post['id_employee'])){
             $detail = User::join('cities','cities.id_city','users.id_city')
                    ->join('employees','employees.id_user','users.id')
                    ->where('employees.id_employee',$post['id_employee'])
                    ->with([
                        'employee',
                        'employee.documents',
                        'employee.city_ktp',
                        'employee.city_domicile',
                        'employee_family',
                        'employee_main_family',
                        'employee_education',
                        'employee_education.city',
                        'employee_education_non_formal',
                        'employee_job_experience',
                        'employee_question',
                        'employee_question.questions'])
                    ->first();
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
   }
   public function candidate(Request $request) {
        $post = $request->all();
        $employee = User::where(array(
            "employees.status"=>"candidate",
            "users.level"=>"Customer"
            ))->wherenotnull('employees.status_approved')->join('employees','employees.id_user','users.id');
        if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }
            if($rule == 'and'){
                foreach ($post['rule'] as $condition){
                    if(isset($condition['subject'])){               
                        $employee = $employee->where('employees.'.$condition['subject'], $condition['parameter']);
                    }
                }
            }else{
                $employee = $employee->where(function ($q) use ($post){
                    foreach ($post['rule'] as $condition){
                        if(isset($condition['subject'])){
                                 if($condition['operator'] == 'like'){
                                      $q->orWhere('employees.'.$condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                 }else{
                                      $q->orWhere('employees.'.$condition['subject'], $condition['parameter']);
                                 }
                        }
                    }
                });
            }
        }
            $employee = $employee->orderBy('employees.created_at', 'desc')->paginate($request->length ?: 10);
        return MyHelper::checkGet($employee);
   }
   public function candidateDetail(Request $request) {
       $post = $request->json()->all();
        if(isset($post['id_employee']) && !empty($post['id_employee'])){
            $detail = User::join('cities','cities.id_city','users.id_city')
                    ->join('employees','employees.id_user','users.id')
                    ->where('employees.id_employee',$post['id_employee'])
                    ->with([
                        'employee',
                        'employee.documents',
                        'employee.city_ktp',
                        'employee.city_domicile',
                        'employee_family',
                        'employee_main_family',
                        'employee_education',
                        'employee_education.city',
                        'employee_education_non_formal',
                        'employee_job_experience',
                        'employee_question',
                        'employee_question.questions'])
                    ->first();
                  
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
   }
    public function update(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_employee']) && !empty($post['id_employee'])){
            if(isset($post['update_type']) && $post['update_type'] != 'Approved'){
              $getData = Employee::where('id_employee', $post['id_employee'])->first();
                if(!empty($post['data_document']['attachment'])){
                    $upload = MyHelper::uploadFile($post['data_document']['attachment'], 'document/employee/', $post['data_document']['ext'], $post['id_employee'].'_'.str_replace(" ","_", $post['data_document']['document_type']));
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $path = $upload['path'];
                    }else {
                        return response()->json(['status' => 'fail', 'messages' => ['Failed upload document']]);
                    }
                }
                if((!empty($post['data_document']['document_type']) && $post['data_document']['document_type'] != 'Contract' ) ||
                    empty($post['data_document']['document_type'])){
                      
                    $update = Employee::where('id_employee', $post['id_employee'])->update(['status_approved' => $post['update_type']]);
                     if(isset($post['bank_account_number'])&&isset($post['bank_account_name'])){
                     $update = Employee::where('id_employee', $post['id_employee'])->update([
                         'status_approved' => $post['update_type'],
                         'bank_account_number'=>$post['bank_account_number'],
                         'bank_account_name'=>$post['bank_account_name'],
                             ]);
                     }
                    if($update && $post['update_type'] == 'Rejected'){
                        Employee::where('id_employee', $post['id_employee'])->update(['user_hair_stylist_passed_status' => $post['user_hair_stylist_passed_status']]);
                    }
                }else{
                    if(isset($post['start_date'])&&isset($post['start_date'])){
                     $update = Employee::where('id_employee', $post['id_employee'])->update([
                         'status_approved' => $post['update_type'],
                         'start_date'=>$post['start_date'],
                         'end_date'=>$post['end_date'],
                             ]);
                     }
                  
                }
                if(!empty($post['data_document'])){
                    $createDoc = EmployeeDocuments::create([
                        'id_employee' => $post['id_employee'],
                        'document_type' => $post['data_document']['document_type'],
                        'process_date' => date('Y-m-d H:i:s', strtotime($post['data_document']['process_date']??date('Y-m-d H:i:s'))),
                        'process_name_by' => $post['data_document']['process_name_by']??null,
                        'process_notes' => $post['data_document']['process_notes'],
                        'attachment' => $path??null
                    ]);
                    if(!$createDoc){
                        return response()->json(MyHelper::checkCreate($createDoc));
                    }
                }
                
                return response()->json(MyHelper::checkUpdate($update));
            }

            if(isset($post['update_type']) && $post['update_type'] == 'Approved'){
                
                $employee = Employee::where('id_employee', $post['id_employee'])->first();
                if(isset($post['auto_generate_pin'])){
                    $pin = MyHelper::createrandom(6, 'Angka');
                }else{
                    $pin = $post['pin'];
                }
                $dtHs = User::where('id', $employee['id_user'])->first();
                if(empty($dtHs)){
                    return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
                }
                $dtHs->password = bcrypt($pin);
                $dtHs->level = "Admin";
                $dtHs->id_outlet = $post['id_outlet']??null;
                $dtHs->id_role = $post['id_role']??null;
                $dtHs->save();
                if(!empty($post['data_document'])){
                    $createDoc = EmployeeDocuments::create([
                        'id_employee' => $post['id_employee'],
                        'document_type' => $post['data_document']['document_type'],
                        'process_date' => date('Y-m-d H:i:s', strtotime($post['data_document']['process_date']??date('Y-m-d H:i:s'))),
                        'process_name_by' => $post['data_document']['process_name_by']??null,
                        'process_notes' => $post['data_document']['process_notes'],
                        'attachment' => $path??null
                    ]);
                    if(!$createDoc){
                        return response()->json(MyHelper::checkCreate($createDoc));
                    }
                }
                $update = Employee::where('id_employee', $post['id_employee'])->update([
                    'status_approved' => $post['update_type'],
                    'status' => 'active'
                        ]);
                
            }
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
