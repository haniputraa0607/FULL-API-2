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
use Modules\Employee\Entities\EmployeeCashAdvanceProductIcount;

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
              ->join('product_icounts','product_icounts.id_product_icount','employee_cash_advances.id_product_icount') 
              ->where('employee_cash_advances.status','!=','Success')
               ->where('employee_cash_advances.status','!=','Approve')
               ->where('employee_cash_advances.status','!=','Rejected')
               ->orderby('employee_cash_advances.created_at','desc')
               ->select('employee_cash_advances.*','users.name as user_name','users.email','employees.code','product_icounts.name as name');
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
              ->join('product_icounts','product_icounts.id_product_icount','employee_cash_advances.id_product_icount') 
              ->where('id_manager',Auth::user()->id)
               ->where('employee_cash_advances.status','!=','Success')
               ->where('employee_cash_advances.status','!=','Approve')
               ->where('employee_cash_advances.status','!=','Rejected')
                ->select('employee_cash_advances.*','users.name as user_name','users.email','employees.code','product_icounts.name as name');    
      }else{
      $employee = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')
               ->join('employees','employees.id_user','employee_cash_advances.id_user')
              ->join('product_icounts','product_icounts.id_product_icount','employee_cash_advances.id_product_icount') 
              ->where('employee_cash_advances.status','!=','Success')
               ->where('employee_cash_advances.status','!=','Approve')
               ->where('employee_cash_advances.status','!=','Rejected')
               ->select('employee_cash_advances.*','users.name as user_name','users.email','employees.code','product_icounts.name as name');    
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
              ->join('product_icounts','product_icounts.id_product_icount','employee_cash_advances.id_product_icount') 
              ->where('employee_cash_advances.status','!=','Pending')
               ->join('users as users_approved','users_approved.id','employee_cash_advances.id_user_approved')
               ->select('employee_cash_advances.*','users.name as user_name','users.email','employees.code','users_approved.name as user_approved','product_icounts.name as name');
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
               ->join('product_icounts','product_icounts.id_product_icount','employee_cash_advances.id_product_icount')
               ->where('id_employee_cash_advance', $request->id_employee_cash_advance)
               ->leftjoin('users as users_approved','users_approved.id','employee_cash_advances.id_user_approved')
               ->select('employee_cash_advances.*','users.name as user_name','users.email','employees.code','users_approved.name as user_approved','product_icounts.name as name','id_manager')
               ->with(['document','icount'])
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
                     $getData = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')
                        ->where('id_employee_cash_advance',$post['id_employee_cash_advance'])
                        ->select('users.phone')
                        ->first();
                        if (\Module::collections()->has('Autocrm')) {
                              $autocrm = app($this->autocrm)->SendAutoCRM(
                                  'Employee Cash Advance Update',
                                  $getData->phone,
                                  [
                                      'document_type'=> $post['update_type'],
                                  ], null, false, false, 'employee', null, null, null, null,
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
                    $createDoc = EmployeeCashAdvanceDocument::create([
                        'id_employee_cash_advance' => $post['id_employee_cash_advance'],
                        'document_type' => $post['data_document']['document_type'],
                        'process_date' => date('Y-m-d H:i:s'),
                        'id_approved' => Auth::user()->id??null,
                        'process_notes' => $post['data_document']['process_notes']??null,
                        'attachment' => $path??null
                    ]);
                    if(!$createDoc){
                        return response()->json(MyHelper::checkCreate($createDoc));
                    }
                }
                if($post['update_type'] == "Finance Approval"){
                    $update = EmployeeCashAdvance::where('id_employee_cash_advance', $post['id_employee_cash_advance'])->update([
                         'status' => $post['update_type'],
                         'id_user_approved' => $post['id_user_approved']?? Auth::user()->id,
                             ]);
                    $icount = $this->approved($request);
                }
                return response()->json(MyHelper::checkUpdate($post));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
   }
   public function approved($request) {
       $post = $request->all();
       $post['date_validation'] = date('Y-m-d H:i:s');
       $post['id_user_approved'] =  $post['id_user_approved'] ?? Auth::user()->id;
       $cash_advance = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')->where(array('id_employee_cash_advance'=>$request->id_employee_cash_advance))->first();
       if($post['update_type'] == "Finance Approval"){
           $getData = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')
                        ->where('id_employee_cash_advance',$post['id_employee_cash_advance'])
                        ->select('users.phone')
                        ->first();
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
                       'id_purchase_deposit_request'=>$initBranch['PurchaseDepositRequestID'],
                       'value_detail'=> json_encode($initBranch),
                       'status' => "Realisasi",
                   ]);
               }
               if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Cash Advance Approved',
                            $getData->phone,
                            [
                                'document_type'=> $post['update_type'],
                            ], null, false, false, 'employee', null, null, null, null,
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
       return $cash_advance;
   }
   public function icount(Request $request) {
       $post = $request->all();
       if(isset($request->id_employee_cash_advance)){
            $post['date_validation'] = date('Y-m-d H:i:s');
            $post['id_user_approved'] =  $post['id_user_approved'] ?? Auth::user()->id;
            $cash_advance = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')->where(array('id_employee_cash_advance'=>$request->id_employee_cash_advance))->first();
            if($cash_advance){
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
                            'id_purchase_deposit_request'=>$initBranch['PurchaseDepositRequestID'],
                            'value_detail'=> json_encode($initBranch),
                            'status' => "Realisasi",
                        ]);
                        return response()->json(MyHelper::checkUpdate($update));
                    }else{
                         return response()->json(['status' => 'fail', 'messages' => [$initBranch['response']['Message']??'Failed send request to icount']]);
                    }
            }
       }
        return response()->json(['status' => 'fail', 'messages' => ['Data not found']]);
   }
   public function callbackcash_advance(CallbackIcountCashAdvance $request){
        if($request->status == "Success"){
            $request->status = "Success";
            $datas = EmployeeCashAdvance::where(array('id_purchase_deposit_request'=>$request->PurchaseDepositRequestID,'status'=>'Success'))->first();
            if($datas){
             return response()->json(['status' => 'success','code'=>1]);   
            }
            $datas = EmployeeCashAdvance::where(array('id_purchase_deposit_request'=>$request->PurchaseDepositRequestID,'status'=>'Realisasi'))->first();
            if($datas){
                 $data = EmployeeCashAdvance::where(array('id_purchase_deposit_request'=>$request->PurchaseDepositRequestID))->update([
                'status'=>$request->status,
                'date_disburse'=>$request->date_disburse,
                'date_send_cash_advance'=>date('Y-m-d H:i:s')
            ]);
                    EmployeeCashAdvanceIcount::create([
                   'id_employee_cash_advance'=>$datas->id_employee_cash_advance,
                   'status'=>$request->status,
                   'value_detail'=>json_encode($request->all()),
                   'id_purchase_deposit_request'=>$request->PurchaseDepositRequestID
               ]);
                    return response()->json(['status' => 'success','code'=>$data]);
            }
        }else{
            $request->status = "Failed";
        }
        $datas = EmployeeCashAdvance::where(array('id_purchase_deposit_request'=>$request->PurchaseDepositRequestID))->first();
           $data = 0;
        if($datas){
             EmployeeCashAdvanceIcount::create([
                'id_employee_cash_advance'=>$datas->id_employee_cash_advance,
                'status'=>$request->status,
                'value_detail'=>json_encode($request->all()),
                'id_purchase_deposit_request'=>$request->PurchaseDepositRequestID
            ]);
        }
        return response()->json(['status' => 'success','code'=>$data]); 
    }
    public function reject(Request $request) {
       $post = $request->json()->all();
        if(isset($post['id_employee_cash_advance']) && !empty($post['id_employee_cash_advance'])){
             $getData = EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')
                        ->where('id_employee_cash_advance',$post['id_employee_cash_advance'])
                        ->select('users.phone')
                        ->first();
             $detail = EmployeeCashAdvance::where('id_employee_cash_advance',$post['id_employee_cash_advance'])
                        ->update([
                            'status'=>'Rejected'
                        ]);
             if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Employee Cash Advance Rejected',
                        $getData->phone,
                        [], null, false, false, 'employee', null, null, null, null,
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
     public function dropdown() {
       $data = ProductIcount::leftjoin('employee_cash_advance_product_icounts','employee_cash_advance_product_icounts.id_product_icount','product_icounts.id_product_icount')
                ->where([
                    'is_buyable'=>'true',
                    'is_sellable'=>'true',
                    'is_deleted'=>'false',
                    'is_suspended'=>'false',
                    'is_actived'=>'true'
                ])
               ->wherenull('employee_cash_advance_product_icounts.id_product_icount')
               ->select([
                    'product_icounts.id_product_icount',
                    'product_icounts.name',
                    'code'
                ])->get();
       return MyHelper::checkGet($data);
   }
    public function list_dropdown(Request $request) {
       
       $data = EmployeeCashAdvanceProductIcount::join('product_icounts','product_icounts.id_product_icount','employee_cash_advance_product_icounts.id_product_icount')
                ->select([
                    'id_employee_cash_advance_product_icount',
                    'employee_cash_advance_product_icounts.name',
                    'product_icounts.id_product_icount',
                    'product_icounts.name as name_icount',
                    'product_icounts.code',
                    'product_icounts.company_type',
                ])->paginate($request->length ?: 10);
       return MyHelper::checkGet($data);
   }
    public function create_dropdown(Request $request) {
       
       $data = null;
       if(isset($request->id_product_icount)&&isset($request->name)){
           $data = EmployeeCashAdvanceProductIcount::where(['id_product_icount'=>$request->id_product_icount])->first();
           if(!$data){
            $data = EmployeeCashAdvanceProductIcount::create(['id_product_icount'=>$request->id_product_icount,'name'=>$request->name]);    
           }
       }
       return MyHelper::checkGet($data);
   }
    public function delete_dropdown(Request $request) {
       
       $data = null;
       if(isset($request->id_employee_cash_advance_product_icount)){
           $data = EmployeeCashAdvanceProductIcount::where(['id_employee_cash_advance_product_icount'=>$request->id_employee_cash_advance_product_icount])->delete();
       }
       return MyHelper::checkGet($data);
   }
}
