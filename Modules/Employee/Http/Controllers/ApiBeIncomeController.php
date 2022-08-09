<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;
use Modules\Employee\Entities\Employee;
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
Use Modules\Employee\Entities\EmployeeIncome;
Use Modules\Employee\Entities\EmployeeIncomeDetail;

class ApiBeIncomeController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }
   public function index(Request $request) {
        $post = $request->all();
        $employee = EmployeeIncome::where(
            "employee_incomes.status",'!=',"Draft"
            )->where(
            "employee_incomes.status",'!=',"Cancelled"
            )
            ->join('users','users.id','employee_incomes.id_user')
            ->join('employees','employees.id_user','employee_incomes.id_user')
            ->join('outlets','outlets.id_outlet','users.id_outlet');
        if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }
            if($rule == 'and'){
                foreach ($post['rule'] as $condition){
                    if(isset($condition['subject'])){
                        if($condition['subject']=='id_outlet'){
                            $employee = $employee->where('outlets'.$condition['subject'], $condition['parameter']);
                        }else{
                            $employee = $employee->where($condition['subject'], $condition['parameter']);
                        }
                        
                    }
                }
            }else{
                $employee = $employee->where(function ($q) use ($post){
                    foreach ($post['rule'] as $condition){
                        if(isset($condition['subject'])){
                                 if($condition['operator'] == 'like'){
                                      $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                 }else{
                                     if($condition['subject']=='id_outlet'){
                                            $q->orWhere('outlets'.$condition['subject'], $condition['parameter']);
                                        }else{
                                             $q->orWhere($condition['subject'], $condition['parameter']);
                                        }
                                 }
                        }   
                    }
                });
            }
        }
            $employee = $employee->orderBy('employee_incomes.periode', 'desc')
                            ->select(
                            'id_employee_income',
                            'employee_incomes.periode',
                            'employee_incomes.start_date',
                            'employee_incomes.end_date',
                            'employee_incomes.amount',
                            'outlets.outlet_name',
                            'outlets.id_outlet',
                            'users.name',
                            'users.email',
                            'users.phone',
                            'employee_incomes.status',
                        )
                        ->paginate($request->length ?: 10);
        return MyHelper::checkGet($employee);
   }
   public function detail(Request $request){
       $id = $request->id_employee_income??0;
       $outlet = EmployeeIncome::where(array(
           'id_employee_income'=>$id,
           ))
            ->join('users','users.id','employee_incomes.id_user')
            ->join('employees','employees.id_user','employee_incomes.id_user')
            ->join('outlets','outlets.id_outlet','users.id_outlet')
           ->select(
                    'id_employee_income',
                    'employee_incomes.periode',
                    'employee_incomes.start_date',
                    'employee_incomes.end_date',
                    'employee_incomes.amount',
                    'outlets.outlet_name',
                    'outlets.id_outlet',
                    'users.name',
                    'users.email',
                    'users.phone',
                    'employee_incomes.status',
                )
           ->with('employee_income_details')
           ->first();
       return MyHelper::checkGet($outlet);
   }
   public function outlet(){
       $outlet = Outlet::where(array(
           'type'=>'Office',
           'outlet_status'=>'Active',
           ))->orderby('outlet_name','asc')
           ->select('id_outlet','outlet_name')
           ->get();
       return MyHelper::checkGet($outlet);
   }
}
