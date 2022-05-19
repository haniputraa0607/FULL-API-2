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
use Modules\Employee\Entities\EmployeeFamily;
use Modules\Employee\Entities\EmployeeMainFamily;
use Modules\Employee\Entities\EmployeeEducation;
use Modules\Employee\Entities\EmployeeEducationNonFormal;
use Modules\Employee\Entities\EmployeeJobExperience;
use Modules\Employee\Entities\EmployeeQuestions;
use Modules\Employee\Http\Requests\Reimbursement\Create;
use Modules\Employee\Http\Requests\Reimbursement\Detail;
use Modules\Employee\Http\Requests\Reimbursement\Update;
use Modules\Employee\Http\Requests\Reimbursement\Delete;
use Modules\Employee\Http\Requests\Reimbursement\history;
use Modules\Employee\Http\Requests\InputFile\CreateFile;
use Modules\Employee\Http\Requests\EmergencyContact\CreateEmergencyContact;
use Modules\Employee\Http\Requests\EmergencyContact\UpdateEmergencyContact;
use Modules\Employee\Http\Requests\InputFile\UpdateFile;
use Modules\Employee\Http\Requests\PerubahanData\UpdatePerubahanData;
use Modules\Employee\Http\Requests\Faq\CreateFaq;
use Modules\Employee\Http\Requests\Faq\UpdateFaq;
use App\Http\Models\User;
use Session;
use DB;
use Modules\Employee\Entities\QuestionEmployee;
use Modules\Employee\Entities\EmployeeReimbursement;
use Modules\Employee\Entities\EmployeeFile;
use Modules\Employee\Entities\EmployeeEmergencyContact;
use Modules\Employee\Entities\EmployeePerubahanData;
use Modules\Employee\Entities\EmployeeFaq;

class ApiBeEmployeeProfileController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "document/employee/file/"; 
    }
   
   //kontak_darurat
   public function emergency_contact() {
       $data = EmployeeEmergencyContact::paginate(10);
       return MyHelper::checkGet($data);
   }
   public function create_emergency_contact(CreateEmergencyContact $request) {
       $post = $request->all();
       $profile = EmployeeEmergencyContact::create($post);
       return MyHelper::checkGet($profile);
   }
   public function detail_emergency_contact(Request $request) {
       $post = $request->all();
       $profile = EmployeeEmergencyContact::where(array(
               'id_employee_emergency_contact'=>$request->id_employee_emergency_contact,
               )
       )->first();
       return MyHelper::checkGet($profile);
   }
   public function update_emergency_contact(UpdateEmergencyContact $request) {
       $post = $request->all();
       $profile = EmployeeEmergencyContact::where(array('id_employee_emergency_contact'=>$request->id_employee_emergency_contact))->first();
     
       if(!empty($post['name_emergency_contact'])){
           $profile['name_emergency_contact'] = $post['name_emergency_contact'];
            }
       if(!empty($post['relation_emergency_contact'])){
           $profile['relation_emergency_contact'] = $post['relation_emergency_contact'];
            }
       if(!empty($post['phone_emergency_contact'])){
           $profile['phone_emergency_contact'] = $post['phone_emergency_contact'];
            }
         $profile->save();
       return MyHelper::checkGet($profile);
   }
   public function delete_emergency_contact(Request $request)
    {
        $deletefile = EmployeeEmergencyContact::where(array(
               'id_employee_emergency_contact'=>$request->id_employee_emergency_contact
                )
               )->delete();
            if ($deletefile == 1) {
                $result = [
                    'status'    => 'success',
                    'result'    => ['File has been deleted']
                ];
            } else {
                $result = [
                    'status'    => 'fail',
                    'messages'    => ['File Not Found']
                ];
            }
        return $result;
    }
    //perubahan data 
    public function perubahan_data() {
       $data = EmployeePerubahanData::where('status','Pending')->paginate(10);
       return MyHelper::checkGet($data);
   }
    public function update_perubahan_data(UpdatePerubahanData $request) {
       $data = EmployeePerubahanData::where('id_employee_perubahan_data',$request->id_employee_perubahan_data)->first();
       $data->status = $request->status;
       $data->save();
       if($request->status == 'Success'){
           $update = Employee::join('users','users.id','employees.id_user')
               ->where(array('users.id'=>$data->id_user))
               ->update([
                   $data->key => $data->change_data,
               ]);
       }
       
       return MyHelper::checkGet($data);
   }
   
   //FAQ
   public function faq() {
       $data = EmployeeFaq::paginate(10);
       return MyHelper::checkGet($data);
   }
   public function create_faq(CreateFaq $request) {
       $post = $request->all();
       $profile = EmployeeFaq::create($post);
       return MyHelper::checkGet($profile);
   }
   public function detail_faq(Request $request) {
       $post = $request->all();
       $profile = EmployeeFaq::where(array(
               'id_employee_faq'=>$request->id_employee_faq,
               )
       )->first();
       return MyHelper::checkGet($profile);
   }
   public function update_faq(UpdateFaq $request) {
       $post = $request->all();
       $profile = EmployeeFaq::where(array('id_employee_faq'=>$request->id_employee_faq))->first();
       if(!empty($post['faq_question'])){
           $profile['faq_question'] = $post['faq_question'];
            }
       if(!empty($post['faq_answer'])){
           $profile['faq_answer'] = $post['faq_answer'];
            }
         $profile->save();
       return MyHelper::checkGet($profile);
   }
   public function delete_faq(Request $request)
    {
        $deletefile = EmployeeFaq::where(array(
               'id_employee_faq'=>$request->id_employee_faq
                )
               )->delete();
            if ($deletefile == 1) {
                $result = [
                    'status'    => 'success',
                    'result'    => ['File has been deleted']
                ];
            } else {
                $result = [
                    'status'    => 'fail',
                    'messages'    => ['File Not Found']
                ];
            }
        return $result;
    }
    
    //privacy_policy
    public function privacy_policy(){
        $data = Setting::where('key','privacy_policy_employee')->first();
         return MyHelper::checkGet($data);
    }
  
    public function privacy_policy_update(Request $request){
        if(isset($request->value_text)){
             $salary_formula = Setting::where('key','privacy_policy_employee')->first();
             if($salary_formula){
                 $data = Setting::where('key','privacy_policy_employee')->update([
                  'value_text'=>$request->value_text,
                 
             ]);
             }else{
                 $data = Setting::create([
                 'key'=>'privacy_policy_employee',
                 'value_text'=> $request->value_text
                    
             ]);
             }
              return response()->json(MyHelper::checkCreate($data));
        }
        return response()->json(['status' => 'fail', 'message' => 'Data Incomplete' ]);
    }
}
