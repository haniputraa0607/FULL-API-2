<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\User;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use DB;
use Modules\Employee\Entities\RequestEmployee;
use Modules\Users\Entities\Department;

class ApiRequestEmployeeController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }

    public function store(Request $request)
    {
        $post = $request->all();
        if (!empty($post) && !empty($post['id_outlet'])) {
            if (isset($post['id_outlet'])) {
                $data_store['id_outlet'] = $post['id_outlet'];
            }
            if (isset($post['id_department'])) {
                $data_store['id_department'] = $post['id_department'];
            }
            if (isset($post['number_of_request'])) {
                $data_store['number_of_request'] = $post['number_of_request'];
            }
            if (isset($post['status'])) {
                $data_store['status'] = $post['status'];
            }
            if (isset($post['notes'])) {
                $data_store['notes'] = $post['notes'];
            }
            if (isset($post['notes_om'])) {
                $data_store['notes_om'] = $post['notes_om'];
            }
            $data_store['id_user'] = $request->user()->id;
            $cek_outlet = Outlet::where(['id_outlet'=>$data_store['id_outlet']])->first();
            $cek_department = Department::where(['id_department'=>$data_store['id_department']])->first();
            if ($cek_outlet && $cek_department) {
                DB::beginTransaction();
                $store = RequestEmployee::create($data_store); 
                if(!$store) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed add request employee']]);
                }   
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['Outlet or Department not found']]);
            }
            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function detail(Request $request){
        $post = $request->all();

        if(isset($post['id_request_employee']) && !empty($post['id_request_employee'])){
            $req_employee = RequestEmployee::with(['outlet_request','applicant_request','department_request'])->where('id_request_employee', $post['id_request_employee'])->first();
            $req_employee['id_employee'] = json_decode($req_employee['id_employee']??'' , true)['id'];
            return response()->json(['status' => 'success', 'result' => [
                'request_employee' => $req_employee,
            ]]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function listEmployeeOutlet(Request $request){
        $post = $request->all();
        if (isset($post['id_outlet']) && !empty($post['id_outlet']) && isset($post['id_department']) && !empty($post['id_department'])) {
            $list = User::join('employees','employees.id_user','users.id')->join('roles','roles.id_role','users.id_role')->where('users.id_outlet',$post['id_outlet'])->where('roles.id_department',$post['id_department'])->where('employees.status','active')->get()->toArray();
            return $list;
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function update(Request $request)
    {
        $post = $request->all();
        if (isset($post['id_request_employee']) && !empty($post['id_request_employee'])) {
            DB::beginTransaction();
            if (isset($post['id_outlet'])) {
                $data_update['id_outlet'] = $post['id_outlet'];
            }
            if (isset($post['number_of_request'])) {
                $data_update['number_of_request'] = $post['number_of_request'];
            }
            if (isset($post['status'])) {
                $data_update['status'] = $post['status'];
            }
            if (isset($post['id_user'])) {
                $data_store['id_user'] = $post['id_user'];
            }
            if (isset($post['notes'])) {
                $data_update['notes'] = $post['notes'];
            }else{
                $data_update['notes'] = null;
            }
            if (isset($post['id_employee'])) {
                $data_update['id_employee'] = $post['id_employee'];
            }else{
                $data_update['id_employee'] = null;
            }
            $update = RequestEmployee::where('id_request_employee', $post['id_request_employee'])->update($data_update);
            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update request employee']]);
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function index(Request $request){
        $post = $request->all();
        
        $post = $request->all();
        $request_employee = RequestEmployee::with(['outlet_request','department_request','applicant_request']);
        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){      

                        if($condition['subject']=='status'){
                            $condition['parameter'] = $condition['operator'];
                            $condition['operator'] = '=';
                        }elseif($condition['subject']=='outlet_name'){
                            if(!MyHelper::isJoined($request_employee,'outlets')){
                                $request_employee = $request_employee->join('outlets','outlets.id_outlet','=','request_employees.id_outlet');
                            }
                            $condition['subject'] = 'outlets.outlet_name';
                        }elseif($condition['subject']=='department_name'){
                            if(!MyHelper::isJoined($request_employee,'departments')){
                                $request_employee = $request_employee->join('departments','departments.id_department','=','request_employees.id_department');
                            }
                            $condition['subject'] = 'departments.department_name';
                        }
                        
                        if($condition['operator'] == '='){
                            $request_employee = $request_employee->where($condition['subject'], $condition['parameter']);
                        }else{
                            $request_employee = $request_employee->where($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $request_employee = $request_employee->where(function ($q) use ($post, $request_employee){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){

                            if($condition['subject']=='status'){
                                $condition['parameter'] = $condition['operator'];
                                $condition['operator'] = '=';
                            }elseif($condition['subject']=='outlet_name'){
                                if(!MyHelper::isJoined($request_employee,'outlets')){
                                    $request_employee = $request_employee->join('outlets','outlets.id_outlet','=','request_employees.id_outlet');
                                }
                                $condition['subject'] = 'outlets.outlet_name';
                            }elseif($condition['subject']=='department_name'){
                                if(!MyHelper::isJoined($request_employee,'departments')){
                                    $request_employee = $request_employee->join('departments','departments.id_department','=','request_employees.id_department');
                                }
                                $condition['subject'] = 'departments.department_name';
                            }

                            if($condition['operator'] == '='){
                                $q->orWhere($condition['subject'], $condition['parameter']);
                            }else{
                                $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }
        if(isset($post['order']) && isset($post['order_type'])){
            if($post['order']=='outlet_name'){
                if(!MyHelper::isJoined($request_employee,'outlets')){
                    $request_employee = $request_employee->join('outlets','outlets.id_outlet','=','request_employees.id_outlet');
                }
                $request_employee = $request_employee->select('request_employees.*');
                if(isset($post['page'])){
                    $request_employee = $request_employee->orderBy('outlets.outlet_name', $post['order_type'])->paginate($request->length ?: 10);
                }else{
                    $request_employee = $request_employee->orderBy('outlets.outlet_name', $post['order_type'])->get()->toArray();
                }
            }elseif($post['order']=='department_name'){
                if(!MyHelper::isJoined($request_employee,'departments')){
                    $request_employee = $request_employee->join('departments','departments.id_department','=','request_employees.id_department');
                }
                $request_employee = $request_employee->select('request_employees.*');
                if(isset($post['page'])){
                    $request_employee = $request_employee->orderBy('departments.department_name', $post['order_type'])->paginate($request->length ?: 10);
                }else{
                    $request_employee = $request_employee->orderBy('departments.department_name', $post['order_type'])->get()->toArray();
                }
            }else{
                $request_employee = $request_employee->select('request_employees.*');
                if(isset($post['page'])){
                    $request_employee = $request_employee->orderBy('request_employees.'.$post['order'], $post['order_type'])->paginate($request->length ?: 10);
                }else{
                    $request_employee = $request_employee->orderBy('request_employees.'.$post['order'], $post['order_type'])->get()->toArray();
                }
            }
        }else{
            if(isset($post['page'])){
                $request_employee = $request_employee->orderBy('request_employees.created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $request_employee = $request_employee->orderBy('request_employees.created_at', 'desc')->get()->toArray();
            }
        }
        return MyHelper::checkGet($request_employee);
    }

    public function delete(Request $request)
    {
        $id_request_employee  = $request->json('id_request_employee');
        $delete = RequestEmployee::where('id_request_employee', $id_request_employee)->delete();
        return MyHelper::checkDelete($delete);
    }

    public function finish(Request $request)
    {
        $id_request_employee  = $request->json('id_request_employee');
        DB::beginTransaction();
        $update = RequestEmployee::where('id_request_employee', $id_request_employee)->update(['status'=>'Finished']);
        if($update){
            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Failed']]);
        }
    }
}
