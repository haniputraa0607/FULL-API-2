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
use Modules\Employee\Http\Requests\CashAdvance\Create;
use Modules\Employee\Http\Requests\CashAdvance\Detail;
use Modules\Employee\Http\Requests\CashAdvance\Update;
use Modules\Employee\Http\Requests\CashAdvance\Delete;
use Modules\Employee\Http\Requests\CashAdvance\BE\Approved;
use App\Http\Models\User;
use Session;
use Modules\Employee\Entities\QuestionEmployee;
use Modules\Employee\Entities\EmployeeCashAdvance;
use App\Lib\Icount;
use App\Http\Models\Outlet;
use Modules\Product\Entities\ProductIcount;
use Modules\Employee\Http\Requests\CashAdvance\BE\CallbackIcountCashAdvance;
use Validator;
use Modules\Employee\Entities\EmployeeCashAdvanceIcount;
use Modules\Employee\Entities\EmployeeCashAdvanceDocument;

class ApiBeEmployeeCashAdvanceController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "document/employee/cash_advance/"; 
    }
    public function index(Request $request) {
      $post = $request->all();
      $employee = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')
               ->join('employees','employees.id_user','employee_cash_advances.id_user')
               ->where('employee_cash_advances.status','!=','Successed')
               ->where('employee_cash_advances.status','!=','Approved')
               ->where('employee_cash_advances.status','!=','Rejected')
               ->orderby('employee_cash_advances.created_at','desc')
               ->select('employee_cash_advances.*','users.name as user_name','users.email','employees.code','title');
       if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }
            if($rule == 'and'){
                foreach ($post['rule'] as $condition){
                    if(isset($condition['subject'])){               
                        $employee = $employee->where($condition['subject'], $condition['parameter']);
                    }
                }
            }else{
                $employee = $employee->where(function ($q) use ($post){
                    foreach ($post['rule'] as $condition){
                        if(isset($condition['subject'])){
                                 if($condition['operator'] == 'like'){
                                      $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                 }else{
                                      $q->orWhere($condition['subject'], $condition['parameter']);
                                 }
                        }
                    }
                });
            }
        }
        $employee = $employee->paginate($request->length ?: 10);
       return MyHelper::checkGet($employee);
   }
   public function manager(Request $request) {
      $post = $request->all();
      if(Auth::user()->level == "Admin"){
      $employee = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')
               ->join('employees','employees.id_user','employee_cash_advances.id_user')
               ->where('id_manager',Auth::user()->id)
               ->where('employee_cash_advances.status','Pending')
                ->select('employee_cash_advances.*','users.name as user_name','users.email','employees.code','title');    
      }else{
      $employee = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')
               ->join('employees','employees.id_user','employee_cash_advances.id_user')
               ->where('employee_cash_advances.status','Pending')
               ->select('employee_cash_advances.*','users.name as user_name','users.email','employees.code','title');    
      }
       if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }
            if($rule == 'and'){
                foreach ($post['rule'] as $condition){
                    if(isset($condition['subject'])){               
                        $employee = $employee->where($condition['subject'], $condition['parameter']);
                    }
                }
            }else{
                $employee = $employee->where(function ($q) use ($post){
                    foreach ($post['rule'] as $condition){
                        if(isset($condition['subject'])){
                                 if($condition['operator'] == 'like'){
                                      $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                 }else{
                                      $q->orWhere($condition['subject'], $condition['parameter']);
                                 }
                        }
                    }
                });
            }
        }
        $employee = $employee->paginate($request->length ?: 10);
       return MyHelper::checkGet($employee);
   }
    public function list(Request $request) {
      $post = $request->all();
      $employee = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')
               ->join('employees','employees.id_user','employee_cash_advances.id_user')
               ->where('employee_cash_advances.status','!=','Pending')
               ->join('users as users_approved','users_approved.id','employee_cash_advances.id_user_approved')
               ->select('employee_cash_advances.*','users.name as user_name','users.email','employees.code','users_approved.name as user_approved','title');
       if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }
            if($rule == 'and'){
                foreach ($post['rule'] as $condition){
                    if(isset($condition['subject'])){               
                        $employee = $employee->where($condition['subject'], $condition['parameter']);
                    }
                }
            }else{
                $employee = $employee->where(function ($q) use ($post){
                    foreach ($post['rule'] as $condition){
                        if(isset($condition['subject'])){
                                 if($condition['operator'] == 'like'){
                                      $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                 }else{
                                      $q->orWhere($condition['subject'], $condition['parameter']);
                                 }
                        }
                    }
                });
            }
        }
        $employee = $employee->paginate($request->length ?: 10);
       return MyHelper::checkGet($employee);
   }
//   public function list(Request $request) {
//       $post = $request->all();
//        $employee = EmployeeCashAdvance::orderBy('created_at', 'desc')->paginate($request->length ?: 10);
//        return MyHelper::checkGet($employee);
//   }
   public function detail(Request $request) {
       if(isset($request->id_employee_cash_advance)){
         $employee = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')
               ->join('employees','employees.id_user','employee_cash_advances.id_user')
               ->where('id_employee_cash_advance', $request->id_employee_cash_advance)
               ->leftjoin('users as users_approved','users_approved.id','employee_cash_advances.id_user_approved')
               ->select('employee_cash_advances.*','users.name as user_name','users.email','employees.code','users_approved.name as user_approved','title','id_manager')
               ->with(['document'])
               ->first();
         if($employee){
            return response()->json(['status' => 'success','result'=>$employee]);
            }
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
   }
   public function update(Request $request) {
       $post = $request->json()->all();
        $update = array();
        if(isset($post['id_employee_cash_advance']) && !empty($post['id_employee_cash_advance'])){
              $getData = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')->where('id_employee_cash_advance', $post['id_employee_cash_advance'])->first();
                if(!empty($post['data_document']['attachment'])){
                    $upload = MyHelper::uploadFile($post['data_document']['attachment'], 'document/employee/', $post['data_document']['ext'], $post['id_employee_cash_advance'].'_'.str_replace(" ","_", $post['data_document']['document_type']));
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $path = $upload['path'];
                    }else {
                        return response()->json(['status' => 'fail', 'messages' => ['Failed upload document']]);
                    }
                }
                $update = array();
                
                    if(isset($post['update_type'])){
                     $update = EmployeeCashAdvance::where('id_employee_cash_advance', $post['id_employee_cash_advance'])->update([
                         'status' => $post['update_type'],
                             ]);
                     }
                  
                if(!empty($post['data_document'])){
                    $createDoc = EmployeeCashAdvanceDocument::create([
                        'id_employee_cash_advance' => $post['id_employee_cash_advance'],
                        'document_type' => $post['data_document']['document_type'],
                        'process_date' => date('Y-m-d H:i:s'),
                        'id_approved' => Auth::user()->id??null,
                        'process_notes' => $post['data_document']['process_notes'],
                        'attachment' => $path??null
                    ]);
                    if(!$createDoc){
                        return response()->json(MyHelper::checkCreate($createDoc));
                    }
                }
                
                return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
   }
   public function approved(Approved $request) {
       $post = $request->all();
       $post['date_validation'] = date('Y-m-d H:i:s');
       $post['id_user_approved'] =  $post['id_user_approved'] ?? Auth::user()->id;
       $cash_advance = EmployeeCashAdvance::where(array('id_employee_cash_advance'=>$request->id_employee_cash_advance))->update($post);
       $cash_advance = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')->where(array('id_employee_cash_advance'=>$request->id_employee_cash_advance))->first();
       if($post['status'] == "Approved"){
            $data_send = [
                    "cash_advance" => EmployeeCashAdvance::where(array('id_employee_cash_advance'=>$request->id_employee_cash_advance))->first(),
                    "employee" => Employee::where('id_user',$cash_advance['id_user'])->first(),
                    "item"=> ProductIcount::where('id_product_icount',$cash_advance['id_product_icount'])->first(),
                    "outlet" => Outlet::where('id_outlet',$cash_advance["id_outlet"])->first(),
                    "location" => Outlet::leftjoin('locations','locations.id_location','outlets.id_location')->where('id_outlet',$cash_advance["id_outlet"])->first(),
                ];
              $initBranch = Icount::EmployeeCashAdvance($data_send, $data_send['location']['company_type']??null);
               if($initBranch['response']['Status']=='1' && $initBranch['response']['Message']=='success'){
                   $initBranch = $initBranch['response']['Data'][0];
                   $update = EmployeeCashAdvance::where(array('id_employee_cash_advance'=>$request->id_employee_cash_advance))->update([
                       'id_purchase_invoice'=>$initBranch['PurchaseInvoiceID'],
                       'value_detail'=> json_encode($initBranch)
                   ]);
               }
       }
       return MyHelper::checkGet($cash_advance);
   }
   public function callbackcash_advance(CallbackIcountCashAdvance $request){
        if($request->status == "Success"){
            $request->status = "Success";
        }else{
            $request->status = "Failed";
        }
        $data = EmployeeCashAdvance::where(array('id_purchase_invoice'=>$request->PurchaseInvoiceID))->update([
            'status'=>$request->status,
            'date_disburse'=>$request->date_disburse,
            'date_send_cash_advance'=>date('Y-m-d H:i:s')
        ]);
        EmployeeCashAdvanceIcount::create([
            'status'=>$request->status,
            'value_detail'=>json_encode($request->all()),
            'id_purchase_invoice'=>$request->PurchaseInvoiceID
        ]);
        return response()->json(['status' => 'success','code'=>$data]); 
    }
}
