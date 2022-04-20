<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
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
use Modules\Employee\Http\Requests\users_create;
use Modules\Employee\Http\Requests\status_approved;
use App\Http\Models\User;
use Session;
use Modules\Employee\Entities\QuestionEmployee;
class ApiRegisterEmployeeController extends Controller
{
   public function create(users_create $request) {
       $post = $request->all();
       if ($post['employee']['pin'] == null) {
            $pin = MyHelper::createRandomPIN(6, 'angka');
            $pin = '777777';
        } else {
            $pin = $post['employee']['pin'];
        }
       $post['employee']['provider'] = MyHelper::cariOperator($post['employee']['phone']);
       $post['employee']['password'] = bcrypt($pin);
       $post['employee']['id_city'] = $post['employee']['id_city_ktp'];
       $post['employee']['level'] = "Admin";
       $user = User::create($post['employee']);
       if($user){
            if($post['employee']){
                $post['employee']['id_user']=$user->id;
                $employee = Employee::create($post['employee']);
            }
            if($post['family']){
                $family = array();
                foreach ($post['family'] as $value) {
                    $value['id_user'] = $user->id;
                    EmployeeFamily::create($value);
                }
            }
            if($post['education']){
                $education = array();
                foreach ($post['education'] as $value) {
                    $value['id_user'] = $user->id;
                    EmployeeEducation::create($value);
                }
            }
            if($post['education_non_formal']){
                $education_non_formal = array();
                foreach ($post['education_non_formal'] as $value) {
                    $value['id_user'] = $user->id;
                    EmployeeEducationNonFormal::create($value);
                }
            }
            if($post['job_experiences']){
                $job_experiences = array();
                foreach ($post['job_experiences'] as $value) {
                    $value['id_user'] = $user->id;
                    EmployeeJobExperience::create($value);
                }
            }
            if($post['questions']){
                $questions = array();
                foreach ($post['questions'] as $value) {
                    $value['id_user'] = $user->id;
                    EmployeeQuestions::create($value);
                }
            }
            
       }
       $user = User::where('id',$user->id)->with(['employee','employee_family','employee_education','employee_education_non_formal','employee_job_experience','employee_question'])->first();
       return MyHelper::checkGet($user);
   }
   public function submit(status_approved $request) {
       $post = $request->all();
       $user = Employee::join('users','users.id','employees.id_user')->where(array(
             'users.phone'=>$post['phone'],
             'employees.status'=>"Candidate",
             "employees.status_approved"=>null
         ))->update(array(
             'status_approved'=>$post['status_approved'],
             'status_step'=>$post['status_step']
         ));
       return MyHelper::checkGet($user);
   }
   public function detail(Request $request) {
        $post = $request->all();
       $user = [];
       
       if(isset($post['username'])){
        $user = User::where('phone',$post['username'])->with(['employee','employee_family','employee_main_family','employee_education','employee_education_non_formal','employee_job_experience','employee_question'])->first();
       }
       return MyHelper::checkGet($user);
   }
   public function update(Request $request) {
       $post = $request->all();
       $user = [];
       if(isset($post['phone'])){
       $user = User::where('phone',$post['phone'])->first();
       if($user){
            if(isset($post['employee'])){
                $post['employee']['id_user']=$user->id;
                $employee = Employee::where('id_user',$user->id)->first();
                if(!empty($employee)){
                    $employee = $this->update_employe($post['employee'],$user->id);
                }else{
                    Employee::create($post['employee']);
                }
                if(isset($post['employee']['id_city_ktp'])){
                    
                $users = User::where('phone',$post['phone'])->update(array(
                    'id_city'=>$post['employee']['id_city_ktp']
                ));
                }
            }
            if(isset($post['family'])){
                $this->update_employe_family($post['family'],$user->id);
            }
            if(isset($post['main_family'])){
                $this->update_employe_main_family($post['main_family'],$user->id);
            }
            if(isset($post['education'])){
                $this->update_employe_education($post['education'],$user->id);
            }
            if(isset($post['education_non_formal'])){
                $this->update_employe_education_non_formal($post['education_non_formal'],$user->id);
            }
            if(isset($post['job_experiences'])){
                $this->update_employe_job_experiences($post['job_experiences'],$user->id);
            }
            if(isset($post['questions'])){
                $this->update_employe_questions($post['questions'],$user->id);
            }
            $user = User::where('id',$user->id)->with(['employee','employee_family','employee_main_family','employee_education','employee_education_non_formal','employee_job_experience','employee_question'])->first();
        }
       }
       return MyHelper::checkGet($user);
   }
   public function update_employe($data,$id_user) {
       $employee = Employee::where('id_user',$id_user)->first();
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
        if(isset($data['height'])){
            $employee->height = $data['height'];
        }
        if(isset($data['weight'])){
            $employee->weight = $data['weight'];
        }
        if(isset($data['age'])){
            $employee->age = $data['age'];
        }
        if(isset($data['place_of_origin'])){
            $employee->place_of_origin = $data['place_of_origin'];
        }
        if(isset($data['job_now'])){
            $employee->job_now = $data['job_now'];
        }
        if(isset($data['companies'])){
            $employee->companies = $data['companies'];
        }
        if(isset($data['blood_type'])){
            $employee->blood_type = $data['blood_type'];
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
        if(isset($data['married_date'])){
            $employee->married_date = $data['married_date'];
        }
        if(isset($data['applied_position'])){
            $employee->applied_position = $data['applied_position'];
        }
        if(isset($data['other_position'])){
            $employee->other_position = $data['other_position'];
        }
        if(isset($data['vacancy_information'])){
            $employee->vacancy_information = $data['vacancy_information'];
        }
        if(isset($data['relatives'])){
            $employee->relatives = $data['relatives'];
        }
        if(isset($data['relative_name'])){
            $employee->relative_name = $data['relative_name'];
        }
        if(isset($data['relative_position'])){
            $employee->relative_position = $data['relative_position'];
        }
        if(isset($data['status_step'])){
            $employee->status_step = $data['status_step'];
        }
        $employee->save();
        return $employee;
   }
   public function update_employe_family($data,$id_user) {
       $array = array();
       foreach($data as $value){
           if(isset($value['id_employee_family'])){
              $family = EmployeeFamily::where('id_employee_family',$value['id_employee_family'])->first();
              if($family){
               if(isset($value['family_members'])){
                    $family->family_members = $value['family_members'];
                }
               if(isset($value['name_family'])){
                    $family->name_family = $value['name_family'];
                }
               if(isset($value['gender_family'])){
                    $family->gender_family = $value['gender_family'];
                }
               if(isset($value['birthplace_family'])){
                    $family->birthplace_family = $value['birthplace_family'];
                }
                if(isset($value['birthday_family'])){
                     $family->birthday_family = $value['birthday_family'];
                 }
                if(isset($value['education_family'])){
                     $family->education_family = $value['education_family'];
                 }
                if(isset($value['job_family'])){
                     $family->job_family = $value['job_family'];
                 }
                 unset($family['id_employee_family']);
                 unset($family['updated_at']);
                 unset($family['created_at']);
                $array[] = $family;
              }
           }else{
               $family = array();
               $family['id_user'] = $value['id_user']=$id_user;
               if(isset($value['family_members'])){
                    $family['family_members'] = $value['family_members'];
                }else{
                    $family['educational_level'] = null;
                }
               if(isset($value['name_family'])){
                    $family['name_family'] = $value['name_family'];
                }else{$family['name_family'] = null;}
               if(isset($value['gender_family'])){
                    $family['gender_family'] = $value['gender_family'];
                }else{$family['gender_family'] = null;}
               if(isset($value['birthplace_family'])){
                    $family['birthplace_family'] = $value['birthplace_family'];
                }else{$family['birthplace_family'] = null;}
               if(isset($value['birthday_family'])){
                    $family['birthday_family'] = $value['birthday_family'];
                }else{$family['birthday_family'] = null;}
               if(isset($value['education_family'])){
                    $family['education_family'] = $value['education_family'];
                }else{$family['education_family'] = null;}
               if(isset($value['job_family'])){
                    $family['job_family'] = $value['job_family'];
                }else{$family['job_family'] = null;}
               $array[] = $family;
           }
       }
       $delete = EmployeeFamily::where('id_user',$id_user)->delete();
      foreach ($array as $va) {
           EmployeeFamily::create(array(
               'id_user'=>$va['id_user'],
               'family_members'=>$va['family_members'],
               'name_family'=>$va['name_family'],
               'gender_family'=>$va['gender_family'],
               'birthplace_family'=>$va['birthplace_family'],
               'birthday_family'=>$va['birthday_family'],
               'education_family'=>$va['education_family'],
               'job_family'=>$va['job_family'],
           ));
        }
       $family = EmployeeFamily::where('id_user',$id_user)->get();
       return $family;
   }
   public function update_employe_main_family($data,$id_user) {
       $array = array();
       foreach($data as $value){
           if(isset($value['id_employee_main_family'])){
              $family = EmployeeMainFamily::where('id_employee_main_family',$value['id_employee_main_family'])->first();
              if($family){
               if(isset($value['family_members'])){
                    $family->family_members = $value['family_members'];
                }
               if(isset($value['name_family'])){
                    $family->name_family = $value['name_family'];
                }
               if(isset($value['gender_family'])){
                    $family->gender_family = $value['gender_family'];
                }
               if(isset($value['birthplace_family'])){
                    $family->birthplace_family = $value['birthplace_family'];
                }
                if(isset($value['birthday_family'])){
                     $family->birthday_family = $value['birthday_family'];
                 }
                if(isset($value['education_family'])){
                     $family->education_family = $value['education_family'];
                 }
                if(isset($value['job_family'])){
                     $family->job_family = $value['job_family'];
                 }
                 unset($family['id_employee_family']);
                 unset($family['updated_at']);
                 unset($family['created_at']);
                $array[] = $family;
              }
           }else{
               $family = array();
               $family['id_user'] = $value['id_user']=$id_user;
               if(isset($value['family_members'])){
                    $family['family_members'] = $value['family_members'];
                }else{
                    $family['educational_level'] = null;
                }
               if(isset($value['name_family'])){
                    $family['name_family'] = $value['name_family'];
                }else{$family['name_family'] = null;}
               if(isset($value['gender_family'])){
                    $family['gender_family'] = $value['gender_family'];
                }else{$family['gender_family'] = null;}
               if(isset($value['birthplace_family'])){
                    $family['birthplace_family'] = $value['birthplace_family'];
                }else{$family['birthplace_family'] = null;}
               if(isset($value['birthday_family'])){
                    $family['birthday_family'] = $value['birthday_family'];
                }else{$family['birthday_family'] = null;}
               if(isset($value['education_family'])){
                    $family['education_family'] = $value['education_family'];
                }else{$family['education_family'] = null;}
               if(isset($value['job_family'])){
                    $family['job_family'] = $value['job_family'];
                }else{$family['job_family'] = null;}
               $array[] = $family;
           }
       }
       $delete = EmployeeMainFamily::where('id_user',$id_user)->delete();
      foreach ($array as $va) {
           EmployeeMainFamily::create(array(
               'id_user'=>$va['id_user'],
               'family_members'=>$va['family_members'],
               'name_family'=>$va['name_family'],
               'gender_family'=>$va['gender_family'],
               'birthplace_family'=>$va['birthplace_family'],
               'birthday_family'=>$va['birthday_family'],
               'education_family'=>$va['education_family'],
               'job_family'=>$va['job_family'],
           ));
        }
       $family = EmployeeMainFamily::where('id_user',$id_user)->get();
       return $family;
   }
   public function update_employe_education($data,$id_user) {
       $array = array();
       foreach($data as $value){
           if(isset($value['id_employee_education'])){
              $education = EmployeeEducation::where('id_employee_education',$value['id_employee_education'])->first();
              if($education){
               if(isset($value['educational_level'])){
                    $education->educational_level = $value['educational_level'];
                }
               if(isset($value['name_school'])){
                    $education->name_school = $value['name_school'];
                }
               if(isset($value['year_education'])){
                    $education->year_education = $value['year_education'];
                }
               if(isset($value['study_program'])){
                    $education->study_program = $value['study_program'];
                }
               if(isset($value['id_city_school'])){
                    $education->id_city_school = $value['id_city_school'];
                }
                 unset($education['id_employee_education']);
                 unset($education['updated_at']);
                 unset($education['created_at']);
                $array[] = $education;
              }
           }else{
               $education = array();
               $education['id_user'] = $value['id_user']=$id_user;
               if(isset($value['educational_level'])){
                    $education['educational_level'] = $value['educational_level'];
                }else{
                    $education['educational_level'] = null;
                }
               if(isset($value['name_school'])){
                    $education['name_school'] = $value['name_school'];
                }else{$education['name_school'] = null;}
               if(isset($value['year_education'])){
                    $education['year_education'] = $value['year_education'];
                }else{$education['year_education'] = null;}
               if(isset($value['study_program'])){
                    $education['study_program'] = $value['study_program'];
                }else{$education['study_program'] = null;}
               if(isset($value['id_city_school'])){
                    $education['id_city_school'] = $value['id_city_school'];
                }else{$education['id_city_school'] = null;}
               $array[] = $education;
           }
       }
       $delete = EmployeeEducation::where('id_user',$id_user)->delete();
      foreach ($array as $va) {
           EmployeeEducation::create(array(
               'id_user'=>$va['id_user'],
               'educational_level'=>$va['educational_level'],
               'name_school'=>$va['name_school'],
               'year_education'=>$va['year_education'],
               'study_program'=>$va['study_program'],
               'id_city_school'=>$va['id_city_school'],
           ));
        }
       $education = EmployeeEducation::where('id_user',$id_user)->get();
       return $education;
   }
   public function update_employe_education_non_formal($data,$id_user) {
       $array = array();
       foreach($data as $value){
           if(isset($value['id_employee_education_non_formal'])){
              $education_non_formal = EmployeeEducationNonFormal::where('id_employee_education_non_formal',$value['id_employee_education_non_formal'])->first();
              if($education_non_formal){
               if(isset($value['course_type'])){
                    $education_non_formal->course_type = $value['course_type'];
                }
               if(isset($value['year_education_non_formal'])){
                    $education_non_formal->year_education_non_formal = $value['year_education_non_formal'];
                }
               if(isset($value['long_term'])){
                    $education_non_formal->long_term = $value['long_term'];
                }
               if(isset($value['certificate'])){
                    $education_non_formal->certificate = $value['certificate'];
                }
               if(isset($value['financed_by'])){
                    $education_non_formal->financed_by = $value['financed_by'];
                }
                 unset($education_non_formal['id_employee_education_non_formal']);
                 unset($education_non_formal['updated_at']);
                 unset($education_non_formal['created_at']);
                $array[] = $education_non_formal;
              }
           }else{
               $education_non_formal = array();
               $education_non_formal['id_user'] = $value['id_user']=$id_user;
               if(isset($value['course_type'])){
                    $education_non_formal['course_type'] = $value['course_type'];
                }else{
                    $education_non_formal['course_type'] = null;
                }
               if(isset($value['year_education_non_formal'])){
                    $education_non_formal['year_education_non_formal'] = $value['year_education_non_formal'];
                }else{$education_non_formal['year_education_non_formal'] = null;}
               if(isset($value['long_term'])){
                    $education_non_formal['long_term'] = $value['long_term'];
                }else{$education_non_formal['long_term'] = null;}
               if(isset($value['certificate'])){
                    $education_non_formal['certificate'] = $value['certificate'];
                }else{$education_non_formal['certificate'] = null;}
               if(isset($value['financed_by'])){
                    $education_non_formal['financed_by'] = $value['financed_by'];
                }else{$education_non_formal['financed_by'] = null;}
               $array[] = $education_non_formal;
           }
       }
       $delete = EmployeeEducationNonFormal::where('id_user',$id_user)->delete();
      foreach ($array as $va) {
           EmployeeEducationNonFormal::create(array(
               'id_user'=>$va['id_user'],
               'course_type'=>$va['course_type'],
               'year_education_non_formal'=>$va['year_education_non_formal'],
               'long_term'=>$va['long_term'],
               'certificate'=>$va['certificate'],
               'financed_by'=>$va['financed_by'],
           ));
        }
       $education_non_formal = EmployeeEducationNonFormal::where('id_user',$id_user)->get();
       return $education_non_formal;
   }
   public function update_employe_job_experiences($data,$id_user) {
       $array = array();
       foreach($data as $value){
           if(isset($value['id_employee_job_experience'])){
              $education_job_experience = EmployeeJobExperience::where('id_employee_job_experience',$value['id_employee_job_experience'])->first();
              if($education_job_experience){
               if(isset($value['company_name'])){
                    $education_job_experience->company_name = $value['company_name'];
                }
               if(isset($value['company_address'])){
                    $education_job_experience->company_address = $value['company_address'];
                }
               if(isset($value['company_position'])){
                    $education_job_experience->company_position = $value['company_position'];
                }
               if(isset($value['industry_type'])){
                    $education_job_experience->industry_type = $value['industry_type'];
                }
               if(isset($value['working_period'])){
                    $education_job_experience->working_period = $value['working_period'];
                }
               if(isset($value['employment_contract'])){
                    $education_job_experience->employment_contract = $value['employment_contract'];
                }
               if(isset($value['total_income'])){
                    $education_job_experience->total_income = $value['total_income'];
                }
               if(isset($value['scope_work'])){
                    $education_job_experience->scope_work = $value['scope_work'];
                }
               if(isset($value['achievement'])){
                    $education_job_experience->achievement = $value['achievement'];
                }
               if(isset($value['reason_resign'])){
                    $education_job_experience->reason_resign = $value['reason_resign'];
                }
                 unset($education_job_experience['id_employee_job_experience']);
                 unset($education_job_experience['updated_at']);
                 unset($education_job_experience['created_at']);
                $array[] = $education_job_experience;
              }
           }else{
               $education_job_experience = array();
               $education_job_experience['id_user'] = $value['id_user']=$id_user;
               if(isset($value['company_name'])){
                    $education_job_experience['company_name'] = $value['company_name'];
                }else{
                    $education_job_experience['company_name'] = null;
                }
               if(isset($value['company_address'])){
                    $education_job_experience['company_address'] = $value['company_address'];
                }else{$education_job_experience['company_address'] = null;}
               if(isset($value['company_position'])){
                    $education_job_experience['company_position'] = $value['company_position'];
                }else{$education_job_experience['company_position'] = null;}
               if(isset($value['industry_type'])){
                    $education_job_experience['industry_type'] = $value['industry_type'];
                }else{$education_job_experience['industry_type'] = null;}
               if(isset($value['working_period'])){
                    $education_job_experience['working_period'] = $value['working_period'];
                }else{$education_job_experience['working_period'] = null;}
               if(isset($value['employment_contract'])){
                    $education_job_experience['employment_contract'] = $value['employment_contract'];
                }else{$education_job_experience['employment_contract'] = null;}
               if(isset($value['total_income'])){
                    $education_job_experience['total_income'] = $value['total_income'];
                }else{$education_job_experience['total_income'] = null;}
               if(isset($value['scope_work'])){
                    $education_job_experience['scope_work'] = $value['scope_work'];
                }else{$education_job_experience['scope_work'] = null;}
               if(isset($value['achievement'])){
                    $education_job_experience['achievement'] = $value['achievement'];
                }else{$education_job_experience['achievement'] = null;}
               if(isset($value['reason_resign'])){
                    $education_job_experience['reason_resign'] = $value['reason_resign'];
                }else{$education_job_experience['reason_resign'] = null;}
               $array[] = $education_job_experience;
           }
       }
       $delete = EmployeeJobExperience::where('id_user',$id_user)->delete();
      foreach ($array as $va) {
           EmployeeJobExperience::create(array(
               'id_user'=>$va['id_user'],
               'company_name'=>$va['company_name'],
               'company_address'=>$va['company_address'],
               'company_position'=>$va['company_position'],
               'industry_type'=>$va['industry_type'],
               'working_period'=>$va['working_period'],
               'employment_contract'=>$va['employment_contract'],
               'total_income'=>$va['total_income'],
               'scope_work'=>$va['scope_work'],
               'achievement'=>$va['achievement'],
               'reason_resign'=>$va['reason_resign'],
           ));
        }
       $education_job_experience = EmployeeJobExperience::where('id_user',$id_user)->get();
       return $education_job_experience;
   }
   public function update_employe_questions($data,$id_user) {
       $array = array();
       foreach($data as $value){
           if(isset($value['id_employee_questions'])){
              $education_questions = EmployeeQuestions::where('id_employee_questions',$value['id_employee_questions'])->first();
              if($education_questions){
               if(isset($value['answer'])){
                    $education_questions->answer = $value['answer'];
                }
                 unset($education_questions['id_employee_questions']);
                 unset($education_questions['updated_at']);
                 unset($education_questions['created_at']);
                $array[] = $education_questions;
              }
           }else{
               $education_questions = array();
               if(isset($value['id_question_employee'])){
                   $ques = QuestionEmployee::where('id_question_employee',$value['id_question_employee'])->first();
                   if($ques){
                       $education_questions['id_user'] = $value['id_user']=$id_user;
                    if(isset($value['question'])){
                         $education_questions['question'] = $value['question'];
                     }else{$education_questions['question'] = null;}
                    $array[] = $education_questions;
                }
              }
           }
       }
       $delete = EmployeeQuestions::where('id_user',$id_user)->delete();
      foreach ($array as $va) {
           EmployeeQuestions::create(array(
               'id_user'=>$va['id_user'],
               'category'=>$va['category'],
               'question'=>$va['question'],
               'answer'=>$va['answer']
           ));
        }
       $education_questions = EmployeeQuestions::where('id_user',$id_user)->get();
       return $education_questions;
   }
   
   public function delete_family($id) {
       $family = EmployeeFamily::find($id)->first();
       if($family){
       $family = EmployeeFamily::find($id)->delete();    
       return response()->json(['status' => 'success', 'messages' => ['Data berhasil dihapus']]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
   }
   public function delete_eductaion($id) {
       $family = EmployeeEducation::find($id)->first();
       if($family){
       $family = EmployeeEducation::find($id)->delete();    
       return response()->json(['status' => 'success', 'messages' => ['Data berhasil dihapus']]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
   }
   public function delete_education_non_formal($id) {
       $family = EmployeeEducationNonFormal::find($id)->first();
       if($family){
       $family = EmployeeEducationNonFormal::find($id)->delete();    
       return response()->json(['status' => 'success', 'messages' => ['Data berhasil dihapus']]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
   }
   public function delete_job_experience($id) {
       $family = EmployeeJobExperience::find($id)->first();
       if($family){
       $family = EmployeeJobExperience::find($id)->delete();    
       return response()->json(['status' => 'success', 'messages' => ['Data berhasil dihapus']]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
   }
   public function delete_questions($id) {
       $family = EmployeeQuestions::find($id)->first();
       if($family){
       $family = EmployeeQuestions::find($id)->delete();    
       return response()->json(['status' => 'success', 'messages' => ['Data berhasil dihapus']]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
   }
}
