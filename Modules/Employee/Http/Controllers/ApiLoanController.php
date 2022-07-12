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
use App\Http\Models\User;
use Session;
use App\Lib\Icount;
use App\Http\Models\Outlet;
use Modules\Product\Entities\ProductIcount;
use Validator;
use Modules\Employee\Entities\EmployeeCategoryLoan;
use Modules\Employee\Entities\EmployeeLoan;
use Modules\Employee\Entities\EmployeeLoanReturn;
use Modules\Employee\Http\Requests\Income\Loan\CreateLoan;
use Modules\Employee\Http\Requests\Income\Loan\CreateLoanIcount;
use Modules\Employee\Entities\EmployeeSalesPayment;

class ApiLoanController extends Controller
{
    public function createCategory(Request $request){
        $post = $request->json()->all();
        $save = EmployeeCategoryLoan::create($post);
        return response()->json(MyHelper::checkUpdate($save));
    }

    public function listCategory(Request $request){
        if(!empty($post['id_employee_category_loan'])){
            $data = EmployeeCategoryLoan::where('id_employee_category_loan', $post['id_employee_category_loan'])->first();
        }else{
            $data = EmployeeCategoryLoan::get()->toArray();
        }

        return response()->json(MyHelper::checkGet($data));
    }

    public function updateCategory(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_employee_category_loan'])){
            $save = EmployeeCategoryLoan::where('id_employee_category_loan', $post['id_employee_category_loan'])->update([
                'employee_category_name' => $post['employee_category_name']
            ]);
            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function deleteCategory(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_employee_category_loan'])){
            $save = EmployeeCategoryLoan::where('id_employee_category_loan', $post['id_employee_category_loan'])->delete();
            return response()->json(MyHelper::checkDelete($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
    public function hs(Request $request){
            $save = User::join('employees','employees.id_user','users.id')
                    ->where(array('level'=>"Admin"))
                    ->wherenotnull('id_role')
                    ->select('id','name','code')
                    ->get();
            return response()->json(MyHelper::checkGet($save));
      
    }
    public function index(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_employee_loan'])){
            $data = EmployeeLoan::join('users','users.id','employee_loans.id_user')
                    ->join('employees','employees.id_user','users.id')
                    ->join('employee_category_loans','employee_category_loans.id_employee_category_loan','employee_loans.id_employee_category_loan')
                    ->where('id_employee_loan', $post['id_employee_loan'])
                    ->with(['loan'])
                    ->select(['employee_loans.*','employees.code','users.name','employee_category_loans.name_category_loan'])
                    ->first();
        }else{
            $data = EmployeeLoan::join('users','users.id','employee_loans.id_user')
                    ->join('employees','employees.id_user','users.id')
                    ->join('employee_category_loans','employee_category_loans.id_employee_category_loan','employee_loans.id_employee_category_loan')
                    ->select(['employee_loans.*','employees.code','users.name','employee_category_loans.name_category_loan'])
                    ->orderby('employee_loans.created_at','DESC')
                    ->get()->toArray();
        }

        return response()->json(MyHelper::checkGet($data));
    }

    public function create(CreateLoan $request){
        $post = $request->json()->all();
        $save = EmployeeLoan::create($post);
        $date = (int) MyHelper::setting('delivery_income', 'value')??25;
        $now = date('d', strtotime($post['effective_date']));
        $i = 0;
        $amount = $post['amount']/$post['installment'];
        if($date<$now){
            $i = 1;
            $post['installment'] = $post['installment']+1;
        }
        if($save){
            for ($i;$i<$post['installment'];$i++){
                EmployeeLoanReturn::create([
                    'id_employee_loan'=>$save['id_employee_loan'],
                    'return_date'=>date('Y-m-'.$date,strtotime("+".$i."month")),
                    'amount_return'=>$amount,
                    'status_return'=>"Pending"
                ]);
            }
        }
        return response()->json(MyHelper::checkUpdate($save));
    }
    public function create_icount(CreateLoanIcount $request){
        $create = EmployeeSalesPayment::create([
            'BusinessPartnerID'=>$request->BusinessPartnerID,
            'SalesInvoiceID'=>$request->SalesInvoiceID,
            'amount'=>$request->amount,
        ]);
        return response()->json(MyHelper::checkUpdate($create));
    }
    public function index_sales_payment() {
        
    }
}
