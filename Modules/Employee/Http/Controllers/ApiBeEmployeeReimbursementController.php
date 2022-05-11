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
   public function approved(Approved $request) {
       $post = $request->all();
       $post['date_validation'] = date('Y-m-d H:i:s');
       $post['id_user_approved'] =  Auth::user()->id;
       $reimbursement = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->update($post);
       $reimbursement = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$request->id_employee_reimbursement))->first();
       return MyHelper::checkGet($reimbursement);
   }
}
