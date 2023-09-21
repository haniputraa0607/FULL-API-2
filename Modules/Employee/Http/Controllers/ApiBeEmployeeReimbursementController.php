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
use Modules\Employee\Http\Requests\Reimbursement\BE\Approved;
use App\Http\Models\User;
use Session;
use Modules\Employee\Entities\QuestionEmployee;
use Modules\Employee\Entities\EmployeeReimbursement;
use App\Lib\Icount;
use App\Http\Models\Outlet;
use Modules\Product\Entities\ProductIcount;
use Modules\Employee\Http\Requests\Reimbursement\BE\CallbackIcountReimbursement;
use Validator;
use Modules\Employee\Entities\EmployeeReimbursementIcount;
use Modules\Employee\Entities\EmployeeReimbursementDocument;
use Modules\Employee\Entities\EmployeeReimbursementProductIcount;
use Modules\Employee\Http\Requests\Reimbursement\BE\CreateBalance;
use Modules\Employee\Http\Requests\Reimbursement\BE\UpdateBalance;

class ApiBeEmployeeReimbursementController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "document/employee/reimbursement/"; 
    }
    public function index(Request $request) {
      $post = $request->all();
      $employee = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')
               ->join('product_icounts','product_icounts.id_product_icount','employee_reimbursements.id_product_icount')
               ->join('employees','employees.id_user','employee_reimbursements.id_user')
               ->where('employee_reimbursements.status','!=','Successed')
               ->where('employee_reimbursements.status','!=','Approved')
               ->where('employee_reimbursements.status','!=','Rejected')
               ->orderby('employee_reimbursements.created_at','desc')
               ->select('employee_reimbursements.*','users.name as user_name','users.email','employees.code','product_icounts.name as name_product');
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
      $employee = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')
               ->join('product_icounts','product_icounts.id_product_icount','employee_reimbursements.id_product_icount')
               ->join('employees','employees.id_user','employee_reimbursements.id_user')
               ->where('id_manager',Auth::user()->id)
               ->where('employee_reimbursements.status','Pending')
                ->select('employee_reimbursements.*','users.name as user_name','users.email','employees.code','product_icounts.name as name_product');    
      }else{
      $employee = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')
               ->join('product_icounts','product_icounts.id_product_icount','employee_reimbursements.id_product_icount')
               ->join('employees','employees.id_user','employee_reimbursements.id_user')
               ->where('employee_reimbursements.status','Pending')
               ->select('employee_reimbursements.*','users.name as user_name','users.email','employees.code','product_icounts.name as name_product');    
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
      $employee = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')
               ->join('employees','employees.id_user','employee_reimbursements.id_user')
               ->join('product_icounts','product_icounts.id_product_icount','employee_reimbursements.id_product_icount')
               ->where('employee_reimbursements.status','!=','Pending')
               ->join('users as users_approved','users_approved.id','employee_reimbursements.id_user_approved')
               ->select('employee_reimbursements.*','users.name as user_name','users.email','employees.code','users_approved.name as user_approved','product_icounts.name as name_product');
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
//        $employee = EmployeeReimbursement::orderBy('created_at', 'desc')->paginate($request->length ?: 10);
//        return MyHelper::checkGet($employee);
//   }
   public function detail(Request $request) {
       if(isset($request->id_employee_reimbursement)){
         $employee = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')
               ->join('employees','employees.id_user','employee_reimbursements.id_user')
                 ->join('product_icounts','product_icounts.id_product_icount','employee_reimbursements.id_product_icount')
               ->where('id_employee_reimbursement', $request->id_employee_reimbursement)
               ->leftjoin('users as users_approved','users_approved.id','employee_reimbursements.id_user_approved')
               ->select('employee_reimbursements.*','users.name as user_name','users.email','employees.code','users_approved.name as user_approved','product_icounts.name as name_product','id_manager')
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
        if(isset($post['id_employee_reimbursement']) && !empty($post['id_employee_reimbursement'])){
              $getData = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')->where('id_employee_reimbursement', $post['id_employee_reimbursement'])->first();
                if(!empty($post['data_document']['attachment'])){
                    $upload = MyHelper::uploadFile($post['data_document']['attachment'], 'document/employee/', $post['data_document']['ext'], $post['id_employee_reimbursement'].'_'.str_replace(" ","_", $post['data_document']['document_type']));
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $path = $upload['path'];
                    }else {
                        return response()->json(['status' => 'fail', 'messages' => ['Failed upload document']]);
                    }
                }
                $update = array();
                
                    if(isset($post['update_type'])){
                     $update = EmployeeReimbursement::where('id_employee_reimbursement', $post['id_employee_reimbursement'])->update([
                         'status' => $post['update_type'],
                             ]);
                     $getData = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')
                        ->where('id_employee_reimbursement',$post['id_employee_reimbursement'])
                        ->select('users.phone')
                        ->first();
                        if (\Module::collections()->has('Autocrm')) {
                           $autocrm = app($this->autocrm)->SendAutoCRM(
                               'Employee Reimbursement Update',
                               $getData->phone,
                               [
                                   'document_type'=> $post['update_type'],
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
                  
                if(!empty($post['data_document'])){
                    $createDoc = EmployeeReimbursementDocument::create([
                        'id_employee_reimbursement' => $post['id_employee_reimbursement'],
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
   public function reject(Request $request) {
       $post = $request->json()->all();
        if(isset($post['id_employee_reimbursement']) && !empty($post['id_employee_reimbursement'])){
             $detail = EmployeeReimbursement::where('id_employee_reimbursement',$post['id_employee_reimbursement'])
                        ->update([
                            'status'=>'Rejected'
                        ]);
             if (\Module::collections()->has('Autocrm')) {
                 $getData = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')
                        ->where('id_employee_reimbursement',$post['id_employee_reimbursement'])
                        ->select('users.phone')
                        ->first();
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Employee Reimbursement Rejected',
                        $getData->phone,
                        [], null, null, null, null, null, null, null, null,
                    );
                    // return $autocrm;
                    if (!$autocrm) {
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Failed to send']
                        ]);
                    }
                }
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
   }
   public function approved(Approved $request) {
       $post = $request->all();
       $post['date_validation'] = date('Y-m-d H:i:s');
       $post['id_user_approved'] =  $post['id_user_approved'] ?? Auth::user()->id;
       $reimbursement = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->update($post);
       $reimbursement = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')->where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->first();
       if($post['status'] == "Approved"){
            $data_send = [
                    "reimbursement" => EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->first(),
                    "employee" => Employee::where('id_user',$reimbursement['id_user'])->first(),
                    "item"=> ProductIcount::where('id_product_icount',$reimbursement['id_product_icount'])->first(),
                    "outlet" => Outlet::where('id_outlet',$reimbursement["id_outlet"])->first(),
                    "location" => Outlet::leftjoin('locations','locations.id_location','outlets.id_location')->where('id_outlet',$reimbursement["id_outlet"])->first(),
                ];
              $initBranch = Icount::EmployeeReimbursement($data_send, $data_send['location']['company_type']??null);
               if($initBranch['response']['Status']=='1' && $initBranch['response']['Message']=='success'){
                   $initBranch = $initBranch['response']['Data'][0];
                   $update = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->update([
                       'id_purchase_invoice'=>$initBranch['PurchaseInvoiceID'],
                       'value_detail'=> json_encode($initBranch)
                   ]);
               }
               if (\Module::collections()->has('Autocrm')) {
                   $getData = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')
                        ->where('id_employee_reimbursement',$post['id_employee_reimbursement'])
                        ->select('users.phone')
                        ->first();
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Reimbursement Approved',
                            $getData->phone,
                            [
                                'document_type'=> $post['status'],
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
       return MyHelper::checkGet($reimbursement);
   }
   public function callbackreimbursement(CallbackIcountReimbursement $request){
        if($request->status == "Success"){
            $request->status = "Success";
        }else{
            $request->status = "Failed";
        }
        $data = EmployeeReimbursement::where(array('id_purchase_invoice'=>$request->PurchaseInvoiceID))->update([
            'status'=>$request->status,
            'date_disburse'=>$request->date_disburse,
            'date_send_reimbursement'=>date('Y-m-d H:i:s')
        ]);
        EmployeeReimbursementIcount::create([
            'status'=>$request->status,
            'value_detail'=>json_encode($request->all()),
            'id_purchase_invoice'=>$request->PurchaseInvoiceID
        ]);
        return response()->json(['status' => 'success','code'=>$data]); 
    }
    public function dropdown() {
       $data = ProductIcount::leftjoin('employee_reimbursement_product_icounts','employee_reimbursement_product_icounts.id_product_icount','product_icounts.id_product_icount')
                ->where([
                    'is_buyable'=>'true',
                    'is_sellable'=>'true',
                    'is_deleted'=>'false',
                    'is_suspended'=>'false',
                    'is_actived'=>'true'
                ])
               ->wherenull('employee_reimbursement_product_icounts.id_product_icount')
               ->select([
                    'product_icounts.id_product_icount',
                    'product_icounts.name',
                    'code'
                ])->get();
       return MyHelper::checkGet($data);
   }
    public function list_dropdown(Request $request) {
       
       $data = EmployeeReimbursementProductIcount::join('product_icounts','product_icounts.id_product_icount','employee_reimbursement_product_icounts.id_product_icount')
                ->select([
                    'employee_reimbursement_product_icounts.*',
                    'product_icounts.id_product_icount',
                    'product_icounts.name as name_icount',
                    'product_icounts.code',
                    'product_icounts.company_type',
                ])->paginate($request->length ?: 10);
       return MyHelper::checkGet($data);
   }
    public function create_dropdown(CreateBalance $request) {
       
           $data = EmployeeReimbursementProductIcount::where(['id_product_icount'=>$request->id_product_icount])->first();
           if(!$data){
            $data = EmployeeReimbursementProductIcount::create([
                'id_product_icount'=>$request->id_product_icount,
                'name'=>$request->name,
                'max_approve_date'=>$request->max_approve_date,
                'reset_date'=>$request->reset_date,
                'value_text'=>$request->value_text,
                'type'=>$request->type,
                'month'=>$request->month,
                ]);    
           }
       return MyHelper::checkGet($data);
   }
    public function update_dropdown(UpdateBalance $request) {
       
            $data = EmployeeReimbursementProductIcount::where('id_employee_reimbursement_product_icount',$request->id_employee_reimbursement_product_icount)->update([
                'id_product_icount'=>$request->id_product_icount,
                'name'=>$request->name,
                'max_approve_date'=>$request->max_approve_date,
                'reset_date'=>$request->reset_date,
                'value_text'=>$request->value_text,
                'type'=>$request->type,
                'month'=>$request->month,
                ]);    
       return MyHelper::checkGet($data);
   }
    public function detail_dropdown(Request $request) {
       
       $data = null;
       if(isset($request->id_employee_reimbursement_product_icount)){
           $data = EmployeeReimbursementProductIcount::where(['id_employee_reimbursement_product_icount'=>$request->id_employee_reimbursement_product_icount])
                    ->join('product_icounts','product_icounts.id_product_icount','employee_reimbursement_product_icounts.id_product_icount')
                ->select([
                    'employee_reimbursement_product_icounts.*',
                    'product_icounts.id_product_icount',
                    'product_icounts.name as name_icount',
                    'product_icounts.code',
                    'product_icounts.company_type',
                ])->first();
       }
       return MyHelper::checkGet($data);
   }
    public function delete_dropdown(Request $request) {
       
       $data = null;
       if(isset($request->id_employee_reimbursement_product_icount)){
           $data = EmployeeReimbursementProductIcount::where(['id_employee_reimbursement_product_icount'=>$request->id_employee_reimbursement_product_icount])->delete();
       }
       return MyHelper::checkGet($data);
   }
   public function approved_apps($request) {
       $post = $request;
       $post['date_validation'] = date('Y-m-d H:i:s');
       $post['id_user_approved'] =  $post['id_user_approved'] ?? Auth::user()->id;
       $reimbursement = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->update($post);
       $reimbursement = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')->where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->first();
       if($post['status'] == "Approved"){
            $data_send = [
                    "reimbursement" => EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->first(),
                    "employee" => Employee::where('id_user',$reimbursement['id_user'])->first(),
                    "item"=> ProductIcount::where('id_product_icount',$reimbursement['id_product_icount'])->first(),
                    "outlet" => Outlet::where('id_outlet',$reimbursement["id_outlet"])->first(),
                    "location" => Outlet::leftjoin('locations','locations.id_location','outlets.id_location')->where('id_outlet',$reimbursement["id_outlet"])->first(),
                ];
              $initBranch = Icount::EmployeeReimbursement($data_send, $data_send['location']['company_type']??null);
               if($initBranch['response']['Status']=='1' && $initBranch['response']['Message']=='success'){
                   $initBranch = $initBranch['response']['Data'][0];
                   $update = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->update([
                       'id_purchase_invoice'=>$initBranch['PurchaseInvoiceID'],
                       'value_detail'=> json_encode($initBranch)
                   ]);
               }
               if (\Module::collections()->has('Autocrm')) {
                   $getData = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')
                        ->where('id_employee_reimbursement',$post['id_employee_reimbursement'])
                        ->select('users.phone')
                        ->first();
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Reimbursement Approved',
                            $getData->phone,
                            [
                                'document_type'=> $post['status'],
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
       return MyHelper::checkGet($reimbursement);
   }
}
