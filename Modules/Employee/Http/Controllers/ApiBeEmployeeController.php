<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;
use Modules\Employee\Entities\Employee;
use Modules\Employee\Entities\EmployeeCustomLink;
use Modules\Employee\Entities\EmployeeFormEvaluation;
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
use Modules\Employee\Entities\CategoryQuestion;
use Modules\Employee\Entities\QuestionEmployee;
use Modules\Employee\Entities\EmployeeRoleBasicSalary;

class ApiBeEmployeeController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }
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
                        'employee.custom_links',
                        'employee.form_evaluation'=>function($eval){
                            $eval->orderBy('updated_at','desc');
                        },
                        'employee.city_ktp',
                        'employee.city_domicile',
                        'employee_family',
                        'employee_main_family',
                        'employee_education',
                        'employee_education.city',
                        'employee_education_non_formal',
                        'employee_job_experience',
                        'employee_emergency_call'])
                    ->first();
            $category = CategoryQuestion::get();
            $array = array();
            foreach ($category as $value) {
                $s = QuestionEmployee::join('employee_questions','employee_questions.id_question_employee','question_employees.id_question_employee')
                                        ->where('id_user',$detail->id_user)
                                        ->where('id_category_question',$value['id_category_question'])
                                        ->count();
                if($s != 0){
                    $value['employee']= QuestionEmployee::join('employee_questions','employee_questions.id_question_employee','question_employees.id_question_employee')
                                        ->where('id_user',$detail->id_user)
                                        ->where('id_category_question',$value['id_category_question'])
                                        ->get();
                    array_push($array,$value);
                }
            }
            $detail['question'] = $array;
            $detail['duration_probation'] = Setting::where('key', 'duration_month_probation_employee')->first()['value'] ?? '3';
            $detail['surat_perjanjian'] = isset($detail['surat_perjanjian']) ? env('STORAGE_URL_API').$detail['surat_perjanjian'] : null; 
            foreach($detail['employee']['form_evaluation'] ?? [] as $key_eval => $eval){
                $detail['employee']['form_evaluation'][$key_eval]['directory'] = isset($eval['directory']) ? env('STORAGE_URL_API').$eval['directory'] : null; 
            }
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
                        'employee.custom_links',
                        'employee.form_evaluation'=>function($eval){
                            $eval->orderBy('updated_at','desc');
                        },
                        'employee.city_ktp',
                        'employee.city_domicile',
                        'employee_family',
                        'employee_main_family',
                        'employee_education',
                        'employee_education.city',
                        'employee_education_non_formal',
                        'employee_job_experience',
                        'employee_emergency_call',])
                    ->first();
            $category = CategoryQuestion::get();
            $array = array();
            foreach ($category as $value) {
                $s = QuestionEmployee::join('employee_questions','employee_questions.id_question_employee','question_employees.id_question_employee')
                                        ->where('id_user',$detail->id_user)
                                        ->where('id_category_question',$value['id_category_question'])
                                        ->count();
                if($s != 0){
                    $value['employee']= QuestionEmployee::join('employee_questions','employee_questions.id_question_employee','question_employees.id_question_employee')
                                        ->where('id_user',$detail->id_user)
                                        ->where('id_category_question',$value['id_category_question'])
                                        ->get();
                    foreach($value['employee'] as $v){
                        if($v['type']=='Type 3'||$v['type']=="Type 4"){
                            $v['question'] = json_decode($v['question']);
                        }
                        if($v['type']!='Type 1'){
                            $v['answer'] = json_decode($v['answer']);
                        }
                    }
                    array_push($array,$value);
                }
            }
            $detail['question'] = $category;
            $detail['duration_probation'] = Setting::where('key', 'duration_month_probation_employee')->first()['value'] ?? '3';
            $detail['surat_perjanjian'] = isset($detail['surat_perjanjian']) ? env('STORAGE_URL_API').$detail['surat_perjanjian'] : null; 
            foreach($detail['employee']['form_evaluation'] ?? [] as $key_eval => $eval){
                $detail['employee']['form_evaluation'][$key_eval]['directory'] = isset($eval['directory']) ? env('STORAGE_URL_API').$eval['directory'] : null; 
            }
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
   }
    public function update(Request $request){
        $post = $request->json()->all();
        $update = array();
        if(isset($post['id_employee']) && !empty($post['id_employee'])){
            if(isset($post['update_type']) && $post['update_type'] != 'Approved'){
              $getData = Employee::join('users','users.id','employees.id_user')->where('id_employee', $post['id_employee'])->first();
                if(!empty($post['data_document']['attachment'])){
                    $upload = MyHelper::uploadFile($post['data_document']['attachment'], 'document/employee/', $post['data_document']['ext'], $post['id_employee'].'_'.str_replace(" ","_", $post['data_document']['document_type']));
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $path = $upload['path'];
                    }else {
                        return response()->json(['status' => 'fail', 'messages' => ['Failed upload document']]);
                    }
                }
                $update = array();
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
                       $update = Employee::where('id_employee', $post['id_employee'])->update(['user_hair_stylist_passed_status' => $post['user_hair_stylist_passed_status']]);
                    }
                }else{
                    if(isset($post['update_type'])){
                     $update = Employee::where('id_employee', $post['id_employee'])->update([
                         'status_approved' => $post['update_type'],
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
                    if($post['data_document']['document_type']=='Interview Invitation'){
                        if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Interview Invitation Employee',
                            $getData->phone,
                            [
                                'date_invitation'=>date('Y-m-d', strtotime($post['data_document']['process_date']??date('Y-m-d H:i:s'))),
                                'time_invitation'=>date('H:i:s', strtotime($post['data_document']['process_date']??date('Y-m-d H:i:s'))),
                            ], null, null, null, null, null, null, null, null,
                        );
                        // return $autocrm;
                        if (!$autocrm) {
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
                    }
                    }
                    if(!$createDoc){
                        return response()->json(MyHelper::checkCreate($createDoc));
                    }
                }
                
                return response()->json(MyHelper::checkUpdate($update));
            }

            if(isset($post['update_type']) && $post['update_type'] == 'Approved'){
                
                $employee = Employee::where('id_employee', $post['id_employee'])->first();
//                if(isset($post['auto_generate_pin'])){
//                    $pin = MyHelper::createrandom(6, 'Angka');
//                }else{
//                    $pin = $post['pin'];
//                }
                $dtHs = User::where('id', $employee['id_user'])->first();
                if(empty($dtHs)){
                    return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
                }
//                $dtHs->password = bcrypt($pin);
                $dtHs->level = "Admin";
                $dtHs->id_outlet = $post['id_outlet']??null;
                $dtHs->id_role = $post['id_role']??null;
               $role = Role::where('id_role',$post['id_role'])->first();
                $dtHs->save();
                $number = $this->number();
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
                    'status' => 'active',
                    "id_cluster"=>"013",
                    "id_term_payment"=>"011",
                    "number"=>$number['number'],
                    "code"=>$number['code'],
                    'start_date'=>$post['start_date'],
                    'end_date'=>$post['end_date'],
                    'id_department'=>$role['id_department']??null,
                    'id_manager'=>$post['id_manager']??null,
                    'status_employee' => $post['status_employee'],
                        ]);
                if($update){
                    $employee = Employee::where('id_employee', $post['id_employee'])
                                ->join('users','users.id','employees.id_user')
                                ->leftjoin('roles','roles.id_role','users.id_role')
                                ->leftjoin('departments','departments.id_department','roles.id_department')
                                ->first();
                    $basic = Setting::where('key','basic_salary_employee')->first();
                    $basic = $basic['value']??0;
                    $group = EmployeeRoleBasicSalary::where(array('id_role'=>$employee->id_role))->first();
                    if(isset($group)){
                       $basic = $group['value'];
                    }
                    $outlet = Outlet::where('id_outlet', $employee['id_outlet'])->with('location_outlet')->first();
                    $outletName = $outlet['outlet_name']??'';
                    $companyType = $outlet['location_outlet']['company_type']??'';
                    $companyType = str_replace('PT ', '', $companyType);
                    $number = $employee['number'];
                    if($employee['status_employee']=='Contract'){
                     $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('Employee_Contract.docx');
                     $templateProcessor->setValue('end_date', MyHelper::dateFormatInd($employee['end_date'], true, false));
                    }else{
                     $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('Employee_Probation.docx');
                    }
                    $templateProcessor->setValue('start_date', MyHelper::dateFormatInd($employee['start_date'], true, false));
                    $templateProcessor->setValue('now', MyHelper::dateFormatInd(date('Y-m-d'), true, false));
                    $templateProcessor->setValue('name', $this->changeString($employee['name']??''));
                    $templateProcessor->setValue('ttl', $employee['birthplace'].", ".MyHelper::dateFormatInd($employee['birthday'], true, false));
                    $templateProcessor->setValue('address', $this->changeString($employee['address_domicile']??''));
                    $templateProcessor->setValue('ktp', (empty($employee['card_number']) ? '':$employee['card_number']));
                    $templateProcessor->setValue('npwp', (empty($employee['npwp']) ? '':$employee['npwp']));
                    $templateProcessor->setValue('lokasi', $this->changeString($outletName));
                    $templateProcessor->setValue('jabatan', $this->changeString($employee['role_name']??''));
                    $templateProcessor->setValue('departmen', $this->changeString($$employee['department_name']??''));
                    $templateProcessor->setValue('basic_salary','Rp '.number_format($basic??0,0,',','.'));


                    if(!File::exists(public_path().'/employee_contract')){
                        File::makeDirectory(public_path().'/employee_contract');
                    }
                    $directory = 'employee_contract/employee_'.$employee['code'].'.docx';
                    $templateProcessor->saveAs($directory);
                    if(config('configs.STORAGE') != 'local'){
                        $contents = File::get(public_path().'/'.$directory);
                        $store = Storage::disk(config('configs.STORAGE'))->put($directory,$contents, 'public');
                        if($store){
                            File::delete(public_path().'/'.$directory);
                        }
                    }

                    if($templateProcessor){
                        Employee::where('id_employee', $post['id_employee'])->update(['surat_perjanjian' => $directory]);
                    }

                   
                }
                $data_send = [
                    "employee" => Employee::join('users','users.id','employees.id_user')->where('id_employee',$post["id_employee"])->first(),
                    "location" => Outlet::leftjoin('locations','locations.id_location','outlets.id_location')->where('id_outlet',$post["id_outlet"])->first(),
                ];
                return response()->json(MyHelper::checkUpdate($update));
                // $initBranch = Icount::ApiCreateEmployee($data_send, $data_send['location']['company_type']??null);
                // if($initBranch['response']['Status']=='1' && $initBranch['response']['Message']=='success'){
                //     $initBranch = $initBranch['response']['Data'][0];
                //     if($data_send['location']['company_type']=='PT IMS'){
                //          $initBranch_ims = Icount::ApiCreateEmployee($data_send, 'PT IMA');
                //          $data_init_ims = $initBranch_ims['response']['Data'][0];
                //          $update = Employee::where('id_employee', $post['id_employee'])->update([
                //              'id_business_partner' => $initBranch['BusinessPartnerID'],
                //              'id_business_partner_ima' => $data_init_ims['BusinessPartnerID'],
                //              'id_company' => $initBranch['CompanyID'],
                //              'id_group_business_partner' => $initBranch['GroupBusinessPartner'],
                //                  ]);
                //      }else{
                //          $update = Employee::where('id_employee', $post['id_employee'])->update([
                //              'id_business_partner' => $initBranch['BusinessPartnerID'],
                //              'id_company' => $initBranch['CompanyID'],
                //              'id_group_business_partner' => $initBranch['GroupBusinessPartner'],
                //                  ]);
                //      }
                //      return response()->json(MyHelper::checkUpdate($update));
                // }else{
                //     return response()->json(['status' => 'fail', 'messages' => [$initBranch['response']['Message']]]);
                // }
            }
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
    public function reject(Request $request) {
       $post = $request->json()->all();
        if(isset($post['id_employee']) && !empty($post['id_employee'])){
             $detail = Employee::where('id_employee',$post['id_employee'])
                        ->update([
                            'status'=>'rejected'
                        ]);
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
   }
    public function bank() {
            $bank = BankName::get();
           return response()->json(['status' => 'success', 'result' => $bank]);
    }
    public function number(){
        $y = 1;
        $no = Employee::orderby('number','desc')->first();
        $nos = $no->number??0;
        for ($x = 0; $x < $y; $x++) {
            $year = date('y');
            $month = date('m');
            $yearMonth = 'EMP'.$year.$month;
            $nos = $nos+1;
            if($nos < 10 ){
                $no = '000'.$nos;
            }elseif($nos < 100 && $nos >= 10){
                $no = '00'.$nos;
            }elseif($nos < 1000 && $nos >= 100){
                $no = '0'.$nos;
            }
            $code = $yearMonth.$no;
            $check = Employee::where('code',$code)->count();
            if($check==0){
                break;
            }
            $y++;
        }
        return array(
            'number'=>$nos,
            'code'=>$code
        );
    }
   public function complement(Request $request) {
       $post = $request->json()->all();
        if(isset($post['id_employee']) && !empty($post['id_employee'])){
             $detail = Employee::where('id_employee',$post['id_employee'])
                        ->first();
             if($detail){
                $update_employee = $this->update_employe($post, $detail->id_user);
                $update_icount = $this->update_icount($detail->id_user);
             }
             
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
   }
   public function update_employe($data,$id_user) {
       $employee = Employee::where('id_user',$id_user)->first();
       $user = User::where('id',$id_user)->first();
       
       if(isset($data['name'])){
            $user->name = $data['name'];
        }
        if(isset($data['id_outlet'])){
            $user->id_outlet = $data['id_outlet'];
        }
        if(isset($data['id_role'])){
            $user->id_role = $data['id_role'];
        }
        if(isset($data['address'])){
            $user->address = $data['address'];
        }
        if(isset($data['birthday'])){
            $user->birthday = date('Y-m-d', strtotime($data['birthday']));
        }
        if(isset($data['gender'])){
            $user->gender = $data['gender'];
        }
        if(isset($data['nickname'])){
            $employee->nickname = $data['nickname'];
        }
       if(isset($data['country'])){
            $employee->country = $data['country'];
        }
        if(isset($data['birthplace'])){
            $employee->birthplace = $data['birthplace'];
        }
        if(isset($data['religion'])){
            $employee->religion = $data['religion'];
        }
        if(isset($data['nickname'])){
            $employee->nickname = $data['nickname'];
        }
        
        if(isset($employee->status_approved)&&$employee->status_approved!='Success'){
            $employee->status_approved = "Success";
        }
        if(isset($data['height'])){
            $employee->height = $data['height'];
        }
        if(isset($data['weight'])){
            $employee->weight = $data['weight'];
        }
        if(isset($data['place_of_origin'])){
            $employee->place_of_origin = $data['place_of_origin'];
        }
        if(isset($data['card_number'])){
            $employee->card_number = $data['card_number'];
        }
        if(isset($data['id_city_ktp'])){
            $employee->id_city_ktp = $data['id_city_ktp'];
        }
        if(isset($data['address_ktp'])){
            $employee->address_ktp = $data['address_ktp'];
        }
        if(isset($data['postcode_ktp'])){
            $employee->postcode_ktp = $data['postcode_ktp'];
        }
        if(isset($data['address_domicile'])){
            $employee->address_domicile = $data['address_domicile'];
        }
        if(isset($data['id_city_domicile'])){
            $employee->id_city_domicile = $data['id_city_domicile'];
            $user->id_city = $data['id_city_domicile'];
        }
        if(isset($data['postcode_domicile'])){
            $employee->postcode_domicile = $data['postcode_domicile'];
        }
        if(isset($data['phone_number'])){
            $employee->phone_number = $data['phone_number'];
        }
        if(isset($data['status_address_domicile'])){
            $employee->status_address_domicile = $data['status_address_domicile'];
        }
        if(isset($data['marital_status'])){
            $employee->marital_status = $data['marital_status'];
        }
        if(isset($data['blood_type'])){
            $employee->blood_type = $data['blood_type'];
        }
        if(isset($data['id_bank_name'])){
            $employee->id_bank_name = $data['id_bank_name'];
        }
        if(isset($data['bank_account_name'])){
            $employee->bank_account_name = $data['bank_account_name'];
        }
        if(isset($data['bank_account_number'])){
            $employee->bank_account_number = $data['bank_account_number'];
        }
        if(isset($data['npwp'])){
            $employee->npwp = $data['npwp'];
        }
        if(isset($data['npwp_name'])){
            $employee->npwp_name = $data['npwp_name'];
        }
        if(isset($data['npwp_address'])){
            $employee->npwp_address = $data['npwp_address'];
        }
        if(isset($data['bpjs_ketenagakerjaan'])){
            $employee->bpjs_ketenagakerjaan = $data['bpjs_ketenagakerjaan'];
        }
        if(isset($data['bpjs_kesehatan'])){
            $employee->bpjs_kesehatan = $data['bpjs_kesehatan'];
        }
        if(isset($data['contact_person'])){
            $employee->contact_person = $data['contact_person'];
        }
        if(isset($data['type'])){
            $employee->type = $data['type'];
        }
        if(isset($data['notes'])){
            $employee->notes = $data['notes'];
        }
        if(isset($data['is_tax'])){
            $employee->is_tax = $data['is_tax'];
        }
        
        $user->save();
        
        $employee->save();
        return $employee;
   }
   public function update_icount($id) {
        if(isset($id) && !empty($id)){
             $detail = Employee::join('users','users.id','employees.id_user')->where('id_user',$id)->first();
             if($detail){
               $data_send = [
                    "employee" => Employee::join('users','users.id','employees.id_user')->where('id_user',$id)->first(),
                    "location" => Outlet::leftjoin('locations','locations.id_location','outlets.id_location')->where('id_outlet',$detail["id_outlet"])->first(),
                ];
                if($data_send['employee']['is_tax'] == 1){
                    $data_send['employee']['is_tax'] = true;
                    }else{
                        $data_send['employee']['is_tax'] = false;
                    }
                return $initBranch = Icount::ApiUpdateEmployee($data_send, $data_send['location']['company_type']??null);
               if($initBranch['response']['Status']=='1' && $initBranch['response']['Message']=='success'){
                   $initBranch = $initBranch['response']['Data'][0];
                   if($data_send['location']['company_type']=='PT IMS'){
                        $initBranch_ims = Icount::ApiUpdateEmployee($data_send, 'PT IMA');
                    }
                }
                return response()->json(MyHelper::checkGet($initBranch));
             }
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
   }

    public function createBusinessPartner(Request $request){
        $post = $request->all();
        if(isset($post['id_employee']) && !empty($post['id_employee'])){
            $employee = Employee::find($post['id_employee']);
            if(!$employee){
                return [
                    'status' => 'fail',
                    'messages' => 'Employee not found',
                ];
            }
            $id_business_partner = null;
            if(isset($post['id_business_partner']) && !empty($post['id_business_partner'])){
                $id_business_partner = $post['id_business_partner'];
            }
            $employee = $employee->businessPartner($id_business_partner);
            if(isset($employee['status']) && $employee['status']=='success'){
                return [
                    'status' => 'success',
                    'id_business_partner' => $employee['id_business_partner']
                ];
            }else{
                return [
                    'status' => 'fail',
                    'messages' =>  $employee['messages']
                ];
            }
        }
        return [
            'status' => 'fail',
            'messages' => 'Id Employee Cant be empty',
        ];
    }
   public function manager(Request $request) {
       $post = $request->json()->all();
        if(isset($post['id_outlet']) && !empty($post['id_role'])){
             $detail = Employee::join('users','users.id','employees.id_user')->join('roles','roles.id_role','users.id_role')
                     ->where('users.id_outlet',$post['id_outlet'])
                     ->where('employees.status','active')
                     ->where('employees.status_employee','Permanent')
                     ->where('roles.id_role',$post['id_role'])
                     ->select('users.id','users.name')
                     ->get();
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
   }

    public function deleteCustomLink(Request $request){
        $post = $request->all();
        $delete = EmployeeCustomLink::where('id_employee_custom_link', $post['id_employee_custom_link'])->delete();      
        return MyHelper::checkDelete($delete);
    }

    public function addCustomLink(Request $request){
        $post = $request->all();
        
        if(isset($post['id_employee']) && !empty($post['id_employee'])){
            DB::beginTransaction();
            $store = EmployeeCustomLink::create($post);
            if(!$store) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed']]);
            }
            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function employeeEvaluation(Request $request) {
        $request->validate([
            'status_form' => 'required|string'
        ]);

        $post = $request->json()->all();

        if((isset($post['id_employee_form_evaluation']) && !empty($post['id_employee_form_evaluation'])) || (isset($post['id_employee']) && !empty($post['id_employee']))){
            $is_done = false;
            $data_update = $post;
            if($post['status_form'] == 'approve_manager'){
                $request->validate([
                    'work_productivity' => 'required|string',
                    'work_quality' => 'required|string',
                    'knwolege_task' => 'required|string',
                    'relationship' => 'required|string',
                    'cooperation' => 'required|string',
                    'discipline' => 'required|string',
                    'initiative' => 'required|string',
                    'expandable' => 'required|string',
                    'update_status' => 'required|string',
                ]);
                $data_update['id_manager'] = $request->user()->id;
                $data_update['update_manager'] = date('Y-m-d H:i:s');
            }elseif($post['status_form'] == 'reject_hr'){
                $data_update['id_hrga'] = $request->user()->id;
                $data_update['update_hrga'] = date('Y-m-d H:i:s');
            }elseif($post['status_form'] == 'approve_hr'){
                $request->validate([
                    'update_status' => 'required|string',
                ]);
                $data_update['id_hrga'] = $request->user()->id;
                $data_update['update_hrga'] = date('Y-m-d H:i:s');
            }elseif($post['status_form'] == 'reject_director'){
                $data_update['id_director'] = $request->user()->id;
                $data_update['update_director'] = date('Y-m-d H:i:s');
            }elseif($post['status_form'] == 'approve_director'){
                $data_update['id_director'] = $request->user()->id;
                $data_update['update_director'] = date('Y-m-d H:i:s');
                $is_done = true;
            }

            if(isset($post['update_status']) && !empty($post['update_status'])){
                if($post['update_status'] == 'Extension'){
                    $request->validate([
                        'current_extension' => 'integer|required',
                        'time_extension' => 'required|string',
                    ]);
                }else{
                    $data_update['current_extension'] = null;
                    $data_update['time_extension'] = null;
                }
            }
            
            DB::beginTransaction();
            if(isset($post['id_employee_form_evaluation']) && !empty($post['id_employee_form_evaluation'])){
                unset($data_update['id_employee_form_evaluation']);
                $updateCreate = EmployeeFormEvaluation::updateOrCreate([
                    'id_employee_form_evaluation' => $post['id_employee_form_evaluation'],
                ],$data_update);
            }else{
                $data_update['code'] = $this->genereateCodeFormEval();
                $updateCreate = EmployeeFormEvaluation::create($data_update);
            }

            
            if(!$updateCreate){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed']]);
            }
            $employee_before = Employee::with(['user'])->where('id_employee', $updateCreate['id_employee'])->first();
            if($is_done && $updateCreate['update_status'] != 'Not Change'){
                $employee = Employee::where('id_employee', $updateCreate['id_employee'])->first();
                $employee_update = [];
                if($employee){
                    if($updateCreate['update_status'] == 'Terminated'){
                        $employee_update['status'] = 'inactive';
                    }elseif($updateCreate['update_status'] == 'Permanent'){
                        $employee_update['status_employee'] = 'Permanent';
                        $employee_update['start_date'] = $employee['end_date'];
                        $employee_update['end_date'] = null;
                    }elseif($updateCreate['update_status'] == 'Extension'){
                        $employee_update['status_employee'] = 'Contract';
                        $employee_update['start_date'] = $employee['end_date'];
                        $extension = ' + '.$updateCreate['current_extension'].' '.($updateCreate['time_extension'] == 'Month' ? 'months' : 'years');
                        $employee_update['end_date'] = date('Y-m-d', strtotime($employee['end_date'].$extension));
                    }

                    $update_employee = Employee::where('id_employee', $updateCreate['id_employee'])->update($employee_update);
                    if(!$update_employee){
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed']]);
                    }
                    $newGenerateCode = $this->generateContract(New Request(['id_employee' => $updateCreate['id_employee'],'form_eval' => true]));
                    $update_employee_file = Employee::where('id_employee', $updateCreate['id_employee'])->update(['surat_perjanjian' => $newGenerateCode]);
                    if(!$update_employee){
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed']]);
                    }

                }
            }

            $data_employee = EmployeeFormEvaluation::with(['employee.user.role.department','employee.user.role.job','manager','hrga', 'director'])->where('id_employee_form_evaluation', $updateCreate['id_employee_form_evaluation'])->first();
            
            $value = [
                'pp' => $data_employee['work_productivity'] == 'Perfect' ? 'V' : '',
                'pg' => $data_employee['work_productivity'] == 'Good' ? 'V' : '',
                'pe' => $data_employee['work_productivity'] == 'Enough' ? 'V' : '',
                'pb' => $data_employee['work_productivity'] == 'Bad' ? 'V' : '',
                'qp' => $data_employee['work_quality'] == 'Perfect' ? 'V' : '',
                'qg' => $data_employee['work_quality'] == 'Good' ? 'V' : '',
                'qe' => $data_employee['work_quality'] == 'Enough' ? 'V' : '',
                'qb' => $data_employee['work_quality'] == 'Bad' ? 'V' : '',
                'kp' => $data_employee['knwolege_task'] == 'Perfect' ? 'V' : '',
                'kg' => $data_employee['knwolege_task'] == 'Good' ? 'V' : '',
                'ke' => $data_employee['knwolege_task'] == 'Enough' ? 'V' : '',
                'kb' => $data_employee['knwolege_task'] == 'Bad' ? 'V' : '',
                'rp' => $data_employee['relationship'] == 'Perfect' ? 'V' : '',
                'rg' => $data_employee['relationship'] == 'Good' ? 'V' : '',
                're' => $data_employee['relationship'] == 'Enough' ? 'V' : '',
                'rb' => $data_employee['relationship'] == 'Bad' ? 'V' : '',
                'cp' => $data_employee['cooperation'] == 'Perfect' ? 'V' : '',
                'cg' => $data_employee['cooperation'] == 'Good' ? 'V' : '',
                'ce' => $data_employee['cooperation'] == 'Enough' ? 'V' : '',
                'cb' => $data_employee['cooperation'] == 'Bad' ? 'V' : '',
                'dp' => $data_employee['discipline'] == 'Perfect' ? 'V' : '',
                'dg' => $data_employee['discipline'] == 'Good' ? 'V' : '',
                'de' => $data_employee['discipline'] == 'Enough' ? 'V' : '',
                'db' => $data_employee['discipline'] == 'Bad' ? 'V' : '',
                'ip' => $data_employee['initiative'] == 'Perfect' ? 'V' : '',
                'ig' => $data_employee['initiative'] == 'Good' ? 'V' : '',
                'ie' => $data_employee['initiative'] == 'Enough' ? 'V' : '',
                'ib' => $data_employee['initiative'] == 'Bad' ? 'V' : '',
                'ep' => $data_employee['expandable'] == 'Perfect' ? 'V' : '',
                'eg' => $data_employee['expandable'] == 'Good' ? 'V' : '',
                'ee' => $data_employee['expandable'] == 'Enough' ? 'V' : '',
                'eb' => $data_employee['expandable'] == 'Bad' ? 'V' : '',
            ];

            $status = [
                's1'=> $data_employee['update_status'] == 'Permanent' ? 'V' : '',
                's2'=> $data_employee['update_status'] == 'Terminated' ? 'V' : '',
                's3'=> $data_employee['update_status'] == 'Extension' ? 'V' : '',
            ];

            if($data_employee['update_status'] == 'Extension'){
                $extend = $data_employee['current_extension'].' '.($data_employee['time_extension'] == 'Month' ? 'bulan' : 'tahun');
            }else{
                $exted = '-';
            }

            if(isset($employee_before['start_date'])){
                $start_date = MyHelper::dateFormatInd($employee_before['start_date'], true, false, false);
            }else{
                $start_date = '-';
            }
            if(isset($employee_before['end_date'])){
                $end_date = MyHelper::dateFormatInd($employee_before['end_date'], true, false, false);
            }else{
                $end_date = '-';
            }
            
            $template = new \PhpOffice\PhpWord\TemplateProcessor('employee_form_evaluation.docx');
            $template->setValue('name', $this->changeString($data_employee['employee']['user']['name']??''));
            $template->setValue('start_date', $start_date);
            $template->setValue('end_date', $end_date);
            $template->setValue('position', $this->changeString($data_employee['employee']['user']['role']['job']['job_level_name']??''));
            $template->setValue('role', $this->changeString($data_employee['employee']['user']['role']['role_name']??''));
            $template->setValue('department', $this->changeString($data_employee['employee']['user']['role']['department']['department_name']??''));
            foreach($value ?? [] as $key => $val){
                $template->setValue($key, $this->changeString($val??''));
            }
            $template->setValue('comment', $data_employee['comment']??'');
            foreach($status ?? [] as $key_2 => $stat){
                $template->setValue($key_2, $this->changeString($stat??''));
            }
            $template->setValue('name_manager', $this->changeString($data_employee['manager']['name']??''));
            $template->setValue('manager_date', MyHelper::dateFormatInd($data_employee['update_manager'], true, false, false));
            if($updateCreate['status_form']!='approve_manager' && $updateCreate['status_form']!='reject_hr'){
                $template->setValue('name_hrga', $this->changeString($data_employee['hrga']['name']??''));
                $template->setValue('hrga_date', MyHelper::dateFormatInd($data_employee['update_hrga'], true, false, false));
            }else{
                $template->setValue('name_hrga', '');
                $template->setValue('hrga_date', '');
            }
            if($updateCreate['status_form']=='approve_director'){
                $template->setValue('name_director', $this->changeString($data_employee['director']['name']??''));
                $template->setValue('director_date', MyHelper::dateFormatInd($data_employee['update_director'], true, false, false));
            }else{
                $template->setValue('name_director', '');
                $template->setValue('director_date', '');
            }

            if(!File::exists(public_path().'/employee_form_evaluation')){
                File::makeDirectory(public_path().'/employee_form_evaluation');
            }
            $directory = 'employee_form_evaluation/employee_form_evaluation_'.$data_employee['employee']['code']."_".$data_employee['code'].'.docx';
            $template->saveAs($directory);
            if(config('configs.STORAGE') != 'local'){
                $contents = File::get(public_path().'/'.$directory);
                $store = Storage::disk(config('configs.STORAGE'))->put($directory,$contents, 'public');
                if($store){
                    if($updateCreate['update_status'] != 'Not Change'){
                        $update_directory = EmployeeFormEvaluation::updateOrCreate(['id_employee_form_evaluation' => $data_employee['id_employee_form_evaluation']],['directory' => $directory]);
                    }else{
                        $update_directory = EmployeeFormEvaluation::updateOrCreate(['id_employee_form_evaluation' => $data_employee['id_employee_form_evaluation']],['directory' => null]);
                        File::delete(public_path().'/'.$directory);
                    }
                    if(!$update_directory){
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed']]);
                    }

                    File::delete(public_path().'/'.$directory);
                }else{
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed']]);
                }
            }

            if($post['status_form'] == 'approve_manager'){
                $crm_title = 'Manager Has been Approve An Evaluation Forms';
                $crm_receipt = User::with(['employee'])->where(function($where){
                    $where->where('level','Super Admin');
                    $where->orWhere(function($whereOr){
                        $whereOr->whereHas('role.roles_features',function($rf){$rf->where('id_feature',529);});
                    });
                })->where('users.id_outlet', $data_employee['employee']['user']['id_outlet'])->get()->toArray();


            }elseif($post['status_form'] == 'reject_hr'){
                $crm_title = 'HRGA Has been Reject An Evaluation Form';
                $crm_receipt = User::with(['employee'])->where('id',$data_employee['employee']['id_manager'])->get()->toArray();

            }elseif($post['status_form'] == 'approve_hr'){
                $crm_title = 'HRGA Has been Approve An Evaluation Form';
                $crm_receipt = User::with(['employee'])->where(function($where){
                    $where->where('level','Super Admin');
                    $where->orWhere(function($whereOr){
                        $whereOr->whereHas('role.roles_features',function($rf){$rf->where('id_feature',528);});
                    });
                })->get()->toArray();

            }elseif($post['status_form'] == 'reject_director'){
                $crm_title = 'Director Has been Reject An Evaluation Form';
                $crm_receipt = User::with(['employee'])->where(function($where){
                    $where->where('level','Super Admin');
                    $where->orWhere(function($whereOr){
                        $whereOr->whereHas('role.roles_features',function($rf){$rf->where('id_feature',529);});
                    });
                })->where('users.id_outlet', $data_employee['employee']['user']['id_outlet'])->get()->toArray();

            }elseif($post['status_form'] == 'approve_director'){
                $crm_title = 'Director Has been Approve An Evaluation Form';
                $crm_receipt = User::with(['employee'])->where('id', $data_employee['employee']['user']['id'])->get()->toArray();
            }

            foreach($crm_receipt ?? [] as $key_crm => $receipt){
                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        $crm_title,
                        $receipt->phone,
                        [], null, null, null, null, null, null, null, null,
                    );
                    if(!$autocrm){
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed']]);
                    }
                }
            }
            
            DB::commit();
            return response()->json(MyHelper::checkCreate($updateCreate));

        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    
    }

    public function generateContract(Request $request){
        $post = $request->id_employee??null;
        $form_eval = $request->form_eval??false;
        $employee = Employee::where('id_employee', $post)
                                ->join('users','users.id','employees.id_user')
                                ->join('roles','roles.id_role','users.id_role')
                                ->join('cities','cities.id_city','employees.id_city_domicile')
                                ->join('departments','departments.id_department','employees.id_department')
                                ->first();
        if(!$employee){
            return array("Not Found");
        }
        $outlet = Outlet::where('id_outlet', $employee['id_outlet'])->with('location_outlet')->first();
        $outletName = $outlet['outlet_name']??'';
        $companyType = $outlet['location_outlet']['company_type']??'';
        $companyType = str_replace('PT ', '', $companyType);
        $number = $employee['number'];
        if($employee['status_employee']=='Probation'){
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('Employee_Probation.docx');
        }else{
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('Employee_Contract.docx');
        }
        $basic = Setting::where('key','basic_salary_employee')->first();
            $basics = number_format($basic['value']??0??0,0,',','.');
            $group = EmployeeRoleBasicSalary::where(array('id_role'=>$request->id_role))->first();
            $overtime['default_value']    = 0;
            if(isset($group)){
                $basics = number_format($group['value']??0,0,',','.');
            }
       $ttl = $employee['birthplace'].', '.MyHelper::dateFormatInd($employee['birthday'], true, false);
        $templateProcessor->setValue('name', $this->changeString($employee['name']??''));
        $templateProcessor->setValue('address',$this->changeString($employee['address_domicile'].' Kota '.$employee['city_name'].', '.$employee['postcode_domicile']));
        $templateProcessor->setValue('end_date', MyHelper::dateFormatInd($employee['end_date'], true, false));
        $templateProcessor->setValue('start_date', MyHelper::dateFormatInd($employee['start_date'], true, false));
        $templateProcessor->setValue('ktp', $this->changeString($employee['card_number']??''));
        $templateProcessor->setValue('npwp', $this->changeString($employee['npwp']??''));
        $templateProcessor->setValue('jabatan', $this->changeString($employee['role_name']??''));
        $templateProcessor->setValue('lokasi', $this->changeString($outletName??''));
        $templateProcessor->setValue('departmen', $this->changeString($employee['department_name']??''));
        $templateProcessor->setValue('basic_salary', 'Rp '.$basics);
        $templateProcessor->setValue('now', MyHelper::dateFormatInd(date('Y-m-d'), true, false));
        $templateProcessor->setValue('ttl', $this->changeString($ttl));
        if(!File::exists(public_path().'/employee_contract')){
                File::makeDirectory(public_path().'/employee_contract');
            }
            $directory = 'employee_contract/employee_'.$employee['status_employee']."_".$employee['code'].'.docx';
            $templateProcessor->saveAs($directory);
        if(config('configs.STORAGE') != 'local'){
            $contents = File::get(public_path().'/'.$directory);
            $store = Storage::disk(config('configs.STORAGE'))->put($directory,$contents, 'public');
            if($store){
                File::delete(public_path().'/'.$directory);
            }
        }
        if($form_eval){
            return $directory;
        }else{
            return public_path().'/'.$directory;
        }
    }

    public function employeeEvaluationDelete(Request $request){
        $post = $request->all();
        $delete = EmployeeFormEvaluation::where('id_employee_form_evaluation', $post['id_employee_form_evaluation'])->delete();      
        return MyHelper::checkDelete($delete);
    }

    public function genereateCodeFormEval(){
        $date = date('ymd');
        $random = rand(1000,9999);
        $code = 'EVAL-'.$date.$random;
        $cek_code = EmployeeFormEvaluation::where('code',$code)->first();
        if($cek_code){
            $this->codeGenerate();
        }
        return $code;
    }

    public function cronInputFormEval(){
        $log = MyHelper::logCron('Manager Make An Evaluation Form');
        try{
            $current_date = date('Y-m-d');
            $employees = Employee::where(function($where1){$where1->where('status_employee','Probation')->orWhere('status_employee','Contract');})->whereNotNull('end_date')->get()->toArray();
            foreach($employees ?? [] as $key => $employee){
                $check = date('Y-m-d', strtotime($employee['end_date']. ' - 10 days')); 
                if($current_date == $check){
                    $manager = User::with(['employee'])->where('id',$employee['id_manager'])->first();
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Make An Evaluation Form For Employees',
                            $manager->phone,
                            [], null, null, null, null, null, null, null, null,
                        );
                    }
                }
            }

            $log->success('success');

        }catch (\Exception $e) {
            $log->fail($e->getMessage());
        }   

    }
	private function changeString($data){
        $data = str_replace("&","dan",$data);
        return $data;
    }
}
