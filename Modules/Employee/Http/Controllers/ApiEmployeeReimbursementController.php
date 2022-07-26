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
use App\Http\Models\User;
use Session;
use DB;
use Modules\Employee\Entities\QuestionEmployee;
use Modules\Employee\Entities\EmployeeReimbursement;
use Modules\Product\Entities\ProductIcount;
class ApiEmployeeReimbursementController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "document/employee/reimbursement/"; 
    }
   public function create(Create $request) {
       $post = $request->all();
       $post['id_user'] = Auth::user()->id;
       $post['due_date'] = date('Y-m-d H:i:s',strtotime('+1 months'));
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
       $reimbursement = EmployeeReimbursement::create($post);
       return MyHelper::checkGet($reimbursement);
   }
   public function detail(Detail $request) {
       $reimbursement = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->with(['user','approval_user'])->first();
       return MyHelper::checkGet($reimbursement);
   }
   public function update(Update $request) {
       $post = $request->all();
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
       $reimbursement = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->update($post);
       $reimbursement = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->first();
       return MyHelper::checkGet($reimbursement);
   }
   public function name_reimbursement() {
       $data = ProductIcount::where([
           'is_buyable'=>'true',
           'is_sellable'=>'true',
           'is_deleted'=>'false',
           'is_suspended'=>'false',
       ])->select([
           'id_product_icount',
           'name',
           'code'
       ])->get();
       return MyHelper::checkGet($data);
   }
   public function saldo_reimbursement(history $request){
       $saldo = EmployeeReimbursement::where(array(
           'id_user'=>Auth::user()->id,
           'status'=>"Approved"
       ))->select(DB::raw('
                        sum(CASE WHEN
                   status = "Approved"  THEN price*qty ELSE 0
                   END) as saldo
                ')
            )->first();
       return MyHelper::checkGet($saldo);
   }
   public function pending(history $request){
       if($request->month){
           $start = $request->month.'-01';
           $end = date('Y-m-t', strtotime($start));
       }else{
           $start = date('Y-m-01');
           $end = date('Y-m-t');
       }
       $saldo = EmployeeReimbursement::join('product_icounts','product_icounts.id_product_icount','employee_reimbursements.id_product_icount')
               ->wherebetween(
               "date_reimbursement",[$start,$end]
       )->where(array(
           'id_user'=>Auth::user()->id,
           'status'=>"Pending"
       ))->select(
              'employee_reimbursements.id_employee_reimbursement','name','date_reimbursement','employee_reimbursements.notes','status','price'
       )->orderby('date_reimbursement','desc')->paginate(10);
       return MyHelper::checkGet($saldo);
   }
   public function history(history $request){
       if($request->month){
           $start = $request->month.'-01';
           $end = date('Y-m-t', strtotime($start));
       }else{
           $start = date('Y-m-01');
           $end = date('Y-m-t');
       }
       $saldo = EmployeeReimbursement::join('product_icounts','product_icounts.id_product_icount','employee_reimbursements.id_product_icount')
               ->wherebetween(
               "date_reimbursement",[$start,$end]
       )->where(array(
           'id_user'=>Auth::user()->id
       ))->where('status','!=','Pending')->select(
              'employee_reimbursements.id_employee_reimbursement','name','date_reimbursement','employee_reimbursements.notes','status','price'
       )->orderby('date_reimbursement','desc')->paginate(10);
       return MyHelper::checkGet($saldo);
   }
}
