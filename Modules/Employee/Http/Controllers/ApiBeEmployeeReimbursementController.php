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
   public function list(Request $request) {
       $post = $request->all();
        $employee = EmployeeReimbursement::orderBy('created_at', 'desc')->paginate($request->length ?: 10);
        return MyHelper::checkGet($employee);
   }
   public function detail(Request $request) {
       if(isset($request->id_employee_reimbursement)){
         $employee = EmployeeReimbursement::where('id_employee_reimbursement', $request->id_employee_reimbursement)->first();
         if($employee){
            return response()->json(['status' => 'success','result'=>$employee]);
            }
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
   }
   public function approved(Request $request) {
       $post = $request->all();
       $post['date_validation'] = date('Y-m-d H:i:s');
       $post['id_user_approved'] =  Auth::user()->id;
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
       }
       return MyHelper::checkGet($reimbursement);
   }
}
