<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;
use App\Http\Models\Province;
use App\Http\Models\Outlet;
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
use Modules\Employee\Http\Requests\InputFile\UpdateFile;
use Modules\Employee\Http\Requests\update_pin;
use Modules\Employee\Http\Requests\CreatePerubahanData;
use App\Http\Models\User;
use Session;
use DB;
use Modules\Employee\Entities\QuestionEmployee;
use Modules\Employee\Entities\EmployeeReimbursement;
use Modules\Employee\Entities\EmployeeFile;
use Modules\Employee\Entities\EmployeeEmergencyContact;
use Modules\Employee\Entities\EmployeePerubahanData;
use Modules\Employee\Entities\EmployeeFaq;
use Modules\Employee\Entities\EmployeeFaqLog;
use Modules\Users\Entities\SettingUser;
use Modules\Employee\Entities\EmployeeNotAvailable;
use Modules\Employee\Entities\EmployeeTimeOff;
use App\Jobs\ReminderEmployeeAttendance;

class ApiEmployeeProfileController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "document/employee/file/"; 
    }
   public function info() {
       $profile = Employee::join('users','users.id','employees.id_user')
               ->where(array('users.id'=>Auth::user()->id))
               ->select([
                   'name',
                   'email',
                   'gender',
                   'birthplace',
                   'birthday',
                   'phone',
                   'marital_status',
                   'religion',
                   'card_number',
                   'address_ktp',
                   'address_domicile',
                   'blood_type'
               ])
               ->first();
       if($profile){
           $profile->validity_period = 'Permanen';
           $profile->id_card_type = "KTP";
        }
       return MyHelper::checkGet($profile);
   }
   public function payroll() {
       $profile = Employee::join('users','users.id','employees.id_user')
               ->leftjoin('bank_name','bank_name.id_bank_name','employees.id_bank_name')
               ->where(array('users.id'=>Auth::user()->id))
               ->select([
                   'bpjs_ketenagakerjaan',
                   'bpjs_kesehatan',
                   'npwp',
                   'bank_name.bank_name',
                   'bank_account_number',
                   'bank_account_name',
               ])
               ->first();
       return MyHelper::checkGet($profile);
   }
   public function ketenagakerjaan() {
       $profile = Employee::join('users','users.id','employees.id_user')
               ->leftjoin('outlets','outlets.id_outlet','users.id_outlet')
               ->leftjoin('roles','roles.id_role','users.id_role')
               ->leftjoin('departments','departments.id_department','roles.id_department')
               ->where(array('users.id'=>Auth::user()->id))
               ->first();
       $validity_period = '';
       if(isset($profile->start_date)&&$profile->start_date < date('Y-m-d')){
          $awal  = date_create($profile->start_date);
          $akhir = date_create();
          $diff = date_diff( $awal, $akhir );
          $validity_period = $diff->y.' Tahun '.$diff->m.' Bulan '.$diff->d.' Hari';
       }
       $response = array(
           'id' => $profile->id,
           'barcode'=>$profile->code,
           'companies'=>$profile->outlet_name,
           'branch'=>'Pusat',
           'departement'=>$profile->department_name,
           'position'=>$profile->role_name,
           'status_employee'=>"Karyawan Kontrak",
           'start_date'=>$profile->start_date,
           'end_date'=>$profile->end_date,
           'validity_period'=>$validity_period
       );
       return MyHelper::checkGet($response);
   }
   public function emergency_contact() {
       $data = EmployeeEmergencyContact::where(array('id_user'=>Auth::user()->id))->get();
       return MyHelper::checkGet($data);
   }
   
   //files
   public function category_file() {
       $data = Setting::where('key','file_employee')->first();
       if($data){
           $data = json_decode($data['value_text']);
       }
       return MyHelper::checkGet($data);
   }
   public function file() {
       $data = EmployeeFile::where('id_user',Auth::user()->id)->paginate(10);
       return MyHelper::checkGet($data);
   }
   public function create_file(CreateFile $request) {
       $post = $request->all();
       $post['id_user'] = Auth::user()->id;
       if(!empty($post['attachment'])){
           $file = $request->file('attachment');
            $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFile, $file->getClientOriginalExtension());
            if (isset($upload['status']) && $upload['status'] == "success") {
                    $post['attachment'] = $upload['path'];
                } else {
                    $result = [
                        'status'   => 'fail',
                        'messages' => ['fail upload file']
                    ];
                    return $result;
                }
            }
       $profile = EmployeeFile::create($post);
       return MyHelper::checkGet($profile);
   }
   public function detail_file(Request $request) {
       $post = $request->all();
       $profile = EmployeeFile::where(array(
               'id_employee_file'=>$request->id_employee_file,
               'id_user'=>Auth::user()->id
               )
       )->first();
       return MyHelper::checkGet($profile);
   }
   public function update_file(UpdateFile $request) {
       $post = $request->all();
       $profile = EmployeeFile::where(array('id_employee_file'=>$request->id_employee_file))->first();
       if(!empty($post['attachment'])){
           $file = $request->file('attachment');
            $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFile, $file->getClientOriginalExtension());
            if (isset($upload['status']) && $upload['status'] == "success") {
                    $profile['attachment'] = $upload['path'];
                } else {
                    $result = [
                        'status'   => 'fail',
                        'messages' => ['fail upload file']
                    ];
                    return $result;
                }
            }
       if(!empty($post['category'])){
           $profile['category'] = $post['category'];
            }
       if(!empty($post['notes'])){
           $profile['notes'] = $post['notes'];
            }
         $profile->save();
       return MyHelper::checkGet($profile);
   }
   public function delete_file(Request $request)
    {
        $deletefile = EmployeeFile::where(array(
               'id_employee_file'=>$request->id_employee_file,
               'id_user'=>Auth::user()->id
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
    
    //update pin
    public function update_pin(update_pin $request) {
       $post = $request->all();
       $profile = User::where(array('id'=>Auth::user()->id))->update([
           'password'=> bcrypt($request->new_password)
       ]);
       return MyHelper::checkGet($profile);
   }
   
   //perubahan data
   public function category_perubahan_data() {
       $data = Setting::where('key','request-perubahan-data-employee')->first();
       if($data){
           $data = json_decode($data['value_text']);
       }
       return MyHelper::checkGet($data);
   }
   public function create_perubahan_data(CreatePerubahanData $request) {
       $post = $request->all();
       $post['id_user'] = Auth::user()->id;
       $profile = EmployeePerubahanData::create($post);
       return MyHelper::checkGet($profile);
   }
   //faq
   public function faq(Request $request) {
       $data = EmployeeFaq::orderby('faq_question','asc')
               ->where(function ($query) use ($request) {
                   if($request->search != ''){
                    $query->where('faq_question', 'like', "%".$request->search."%");   
                   }
                })->get();
       return MyHelper::checkGet($data);
   }
   public function faq_terpopuler(Request $request) {
       $data = EmployeeFaqLog::join(
               'employee_faqs','employee_faqs.id_employee_faq','employee_faq_logs.id_employee_faq'
       )->take(6)->get();
       return MyHelper::checkGet($data);
   }
   //privasi
   public function privacy_policy(){
        $data = Setting::where('key','privacy_policy_employee')->first();
         return MyHelper::checkGet($data);
    }
  
    //Office Employee
    public function total_employee(){
      $profile = Employee::join('users','users.id','employees.id_user')
               ->leftjoin('outlets','outlets.id_outlet','users.id_outlet')
               ->leftjoin('roles','roles.id_role','users.id_role')
               ->leftjoin('departments','departments.id_department','roles.id_department')
               ->where(array('users.id_outlet'=>Auth::user()->id_outlet))
               ->count();
         return MyHelper::checkGet($profile);
    }
    public function list_employee(){
      $profile = Employee::join('users','users.id','employees.id_user')
               ->leftjoin('outlets','outlets.id_outlet','users.id_outlet')
               ->leftjoin('roles','roles.id_role','users.id_role')
               ->leftjoin('departments','departments.id_department','roles.id_department')
               ->where(array('users.id_outlet'=>Auth::user()->id_outlet))
               ->select([
                   'id',
                   'name',
                   'phone',
                   'users.email',
                   'department_name',
               ])
               ->get();
       foreach ($profile as $value) {
           $value['wa'] = "https://wa.me/".$value['phone'];
       }
         return MyHelper::checkGet($profile);
    }
    public function cuti_employee(){
      $profile = Employee::join('users','users.id','employees.id_user')
               ->leftjoin('outlets','outlets.id_outlet','users.id_outlet')
               ->leftjoin('roles','roles.id_role','users.id_role')
               ->leftjoin('departments','departments.id_department','roles.id_department')
               ->join('employee_time_off','employee_time_off.id_employee','users.id')
               ->where(array(
                   'users.id_outlet'=>Auth::user()->id_outlet
                   ))
               ->whereNotNull('employee_time_off.approve_at')
               ->WhereNull('employee_time_off.reject_at')
               ->wheredate('employee_time_off.date',date('Y-m-d'))
               ->select([
                   'id',
                   'name',
                   'phone',
                   'department_name'
               ])
               ->get();
         return MyHelper::checkGet($profile);
    }
    public function detail_employee(Request $request){  
      $profile = Employee::join('users','users.id','employees.id_user')
               ->leftjoin('outlets','outlets.id_outlet','users.id_outlet')
               ->leftjoin('roles','roles.id_role','users.id_role')
               ->leftjoin('departments','departments.id_department','roles.id_department')
               ->where(array(
                   'users.id'=>$request->id_user
                   ))
               ->select([
                   'id',
                   'name',
                   'email',
                   'employees.id_employee',
                   'phone',
                   'role_name',
                   'department_name'
               ])
               ->first();
      $profile['wa'] = "https://wa.me/".$profile['phone'];
         return MyHelper::checkGet($profile);
    }

    public function getReminderAttendance(Request $request){
        $post = $request->all();
        $employee = $request->user();

        //reminder clock in
        $clock_in = [
            'type' => 'clock_in',
            'value' => SettingUser::where('id',$employee->id)->where('key','reminder_clock_in')->first()['value'] ?? 'off',
        ];

        $clock_out = [
            'type' => 'clock_out',
            'value' => SettingUser::where('id',$employee->id)->where('key','reminder_clock_out')->first()['value'] ?? 'off',
        ];

        $result = [
            'clock_in' => $clock_in,
            'clock_out' => $clock_out,
        ];
        return MyHelper::checkGet($result);
    }

    public function reminderAttendance(Request $request){
        $post = $request->all();
        $employee = $request->user();
        $outlet = $employee->outlet()->select('id_outlet','outlet_name', 'id_city')->first();

        $send = [
            'id' => $employee['id'],
            'value' => $post['value'] ?? 'off'
        ];

        if($post['type'] == 'clock_in'){
            $send['key'] = 'reminder_clock_in';
            
        }elseif($post['type'] == 'clock_out'){
            $send['key'] = 'reminder_clock_out';
        }else{
            return response()->json([
                'status'=>'fail',
                'messages'=>['Tipe reminder salah']
            ]);
        }
        DB::beginTransaction();
        $store = SettingUser::updateOrCreate(['id'=>$send['id'],'key'=>$send['key']],['value'=>$send['value']]);
        if(!$store){
            DB::rollBack();
            return response()->json([
                'status' => 'fail', 
                'messages' => ['Gagal menyimpan pengingat clock in/clock out']
            ]);
        }

        DB::commit();
        return response()->json(['status' => 'success', 'messages' => ['Berhasil menyimpan pengingat clock in/clock out']]);
    }
    
    public function cronReminder(){
        $log = MyHelper::logCron('Reminder Employee Clock In and Clock Out');
        try{
            $time_reminder = Setting::where('key', 'time_rimender_employee_attendance')->first()['value']??5;
            $reminder = SettingUser::where(function($q){
                $q->where('key', 'reminder_clock_in');
                $q->orWhere('key', 'reminder_clock_out');
            })->where('value','on')->get()->toArray();

            foreach($reminder ?? [] as $key => $rem){
                $send = [
                    'time_reminder' => $time_reminder,
                    'value' => $rem
                ];
                $queue = ReminderEmployeeAttendance::dispatch($send);
            }

            $log->success('success');
            return response()->json(['status' => 'success']);

        }catch (\Exception $e) {
            $log->fail($e->getMessage());
        }    
    }

}
