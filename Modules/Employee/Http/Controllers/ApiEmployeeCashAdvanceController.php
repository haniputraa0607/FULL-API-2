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
use Modules\Employee\Http\Requests\CashAdvance\history;
use App\Http\Models\User;
use Session;
use DB;
use Modules\Employee\Entities\QuestionEmployee;
use Modules\Employee\Entities\EmployeeCashAdvance;
use Modules\Product\Entities\ProductIcount;
use App\Http\Models\Outlet;

class ApiEmployeeCashAdvanceController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "document/employee/cash_advance/"; 
    }
   public function create(Create $request) {
       $post = $request->all();
       $post['id_user'] = Auth::user()->id;
       $post['tax_date'] = $request->date_cash_advance;
       if(!empty($post['attachment'])){
            $file = $request->file('attachment');
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $attachment = MyHelper::encodeImage($file);
            $upload = MyHelper::uploadFile($attachment, $this->saveFile, $ext, strtotime(date('Y-m-d H-i-s')));
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
       $cash_advance = EmployeeCashAdvance::create($post);
       return MyHelper::checkGet($cash_advance);
   }
   public function detail(Detail $request) {
       $cash_advance = EmployeeCashAdvance::where(array('id_employee_cash_advance'=>$request->id_employee_cash_advance))->with(['user','approval_user'])->first();
       if(isset($cash_advance['attachment'])){
           $cash_advance['attachment']= env('STORAGE_URL_API').$cash_advance['attachment'];
       }
       return MyHelper::checkGet($cash_advance);
   }
   public function update(Update $request) {
       $post = $request->all();
        if(!empty($post['attachment'])){
            $file = $request->file('attachment');
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $attachment = MyHelper::encodeImage($file);
            $upload = MyHelper::uploadFile($attachment, $this->saveFile, $ext, strtotime(date('Y-m-d H-i-s')));
            if (isset($upload['status']) && $upload['status'] == "success") {
                    $post['attachment'] = $upload['path'];
                } else {
                    $result = [
                        'status'   => 'fail',
                        'messages' => ['fail upload file']
                    ];
                    return $result;
                }
            }else{
                unset($post['attachment']);
            }
       $cash_advance = EmployeeCashAdvance::where(array('id_employee_cash_advance'=>$request->id_employee_cash_advance))->update($post);
       $cash_advance = EmployeeCashAdvance::where(array('id_employee_cash_advance'=>$request->id_employee_cash_advance))->first();
       return MyHelper::checkGet($cash_advance);
   }
   
   public function pending(history $request){
       if($request->month){
           $start = $request->month.'-01';
           $end = date('Y-m-t', strtotime($start));
       }else{
           $start = date('Y-m-01');
           $end = date('Y-m-t');
       }
       $saldo = EmployeeCashAdvance::join('product_icounts','product_icounts.id_product_icount','employee_cash_advances.id_product_icount')
               ->wherebetween(
               "date_cash_advance",[$start,$end]
       )->where(array(
           'id_user'=>Auth::user()->id,
           'status'=>"Pending"
       ))->select(
              'employee_cash_advances.id_employee_cash_advance','product_icounts.name as name','date_cash_advance','employee_cash_advances.notes','status','price'
       )->orderby('date_cash_advance','desc')->paginate(10);
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
       $saldo = EmployeeCashAdvance::wherebetween(
               "date_cash_advance",[$start,$end]
       )->where(array(
           'id_user'=>Auth::user()->id
       ))->where('status','!=','Pending')->select(
              'employee_cash_advances.id_employee_cash_advance','product_icounts.name as name','date_cash_advance','employee_cash_advances.notes','status','price'
       )->orderby('date_cash_advance','desc')->paginate(10);
       return MyHelper::checkGet($saldo);
   }
   public function name_cash_advance() {
       $post =  Auth::user();
       $outlet = Outlet::leftjoin('locations','locations.id_location','outlets.id_location')->where('id_outlet',$post["id_outlet"])->select('company_type')->first();
       if($outlet['company_type']??''=="PT IMA"){
           $company = 'ima';
       }else{
           $company = 'ims';
       }
       $data = ProductIcount::join('employee_cash_advance_product_icounts','employee_cash_advance_product_icounts.id_product_icount','product_icounts.id_product_icount')
               ->where([
           'is_buyable'=>'true',
           'is_sellable'=>'true',
           'is_deleted'=>'false',
           'is_suspended'=>'false',
           'is_actived'=>'true',
           'company_type'=>$company
       ])->select([
           'product_icounts.id_product_icount',
           'product_icounts.name',
           'product_icounts.code'
       ])->get();
       return MyHelper::checkGet($data);
   }
}
