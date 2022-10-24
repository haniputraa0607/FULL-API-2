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
use Modules\Employee\Entities\EmployeeRoleIncentive;
use Modules\Employee\Entities\EmployeeRoleIncentiveDefault;
use Modules\Employee\Entities\EmployeeRoleSalaryCut;
use Modules\Employee\Entities\EmployeeRoleSalaryCutDefault;
use Modules\Employee\Entities\EmployeeRoleBasicSalary;
use Modules\Employee\Entities\EmployeeReimbursementProductIcount;
use Modules\Employee\Entities\EmployeeRoleReimbursementProductIcount;

class ApiRoleController extends Controller
{
    
    function index(Request $request) 
    {
    	$post = $request->json()->all();
        $data = Role::Select('roles.*');
        if ($request->json('rule')){
             $this->filterList($data,$request->json('rule'),$request->json('operator')??'and');
        }
        $data = $data->paginate($request->length ?: 10);
        //jika mobile di pagination
        if (!$request->json('web')) {
            $resultMessage = 'Data tidak ada';
            return response()->json(MyHelper::checkGet($data, $resultMessage));
        }
        else{
           
            return response()->json(MyHelper::checkGet($data));
        }
    }
   
    public function filterList($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }
        $where=$operator=='and'?'where':'orWhere';
        $subjects=['hair_stylist_group_name','hair_stylist_group_code'];
         $i = 1;
        foreach ($subjects as $subject) {
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    if($i<=1){
                    $query->where($subject,$rule[0],$rule[1]);
                    }else{
                    $query->$where($subject,$rule[0],$rule[1]);    
                    }
                    $i++;
                }
            }
        }
    }
    public function detail(Request $request)
    {
        if($request->id_role!=''){
            $data = Role::where(array('id_role'=>$request->id_role))->first();
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    
    public function product(Request $request)
    {
        $data = array();
        if($request->id_hairstylist_group){
        $store = Product::select(['products.id_product','product_name'])->get();
        foreach ($store as $value) {
            $global = Product::where(array('products.id_product'=>$value['id_product']))->join('product_global_price','product_global_price.id_product','products.id_product')->first();
            $cek = HairstylistGroupCommission::where(array('id_product'=>$value['id_product'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
            if(!$cek){
                $value['price'] = 0;
                if($global){
                    $value['price'] = $global->product_global_price;
                }
                array_push($data,$value);
            }
        }}
         return response()->json($data);
    }
    public function hs(Request $request)
    {
        $data = array();
        if($request->id_hairstylist_group){
         $query = UserHairStylist::where(array('user_hair_stylist_status'=>'Active'))->get();
         foreach ($query as $value) {
             if($value['id_hairstylist_group']!=$request->id_hairstylist_group){
                 $val = array(
                     'id_user_hair_stylist'=>$value['id_user_hair_stylist'],
                     'user_hair_stylist_code'=>$value['user_hair_stylist_code'],
                     'fullname'=>$value['fullname'],
                 );
                 array_push($data,$value);
             }
         }
        }
         return response()->json(MyHelper::checkGet($data));
    }
    public function invite_hs(InviteHS $request)
    {
        $store = UserHairStylist::where(array('id_user_hair_stylist'=>$request->id_user_hair_stylist))->update([
            'id_hairstylist_group'=>$request->id_hairstylist_group
        ]);
          return response()->json(MyHelper::checkCreate($store));
    }
    public function create_commission(CreateGroupCommission $request)
    {
        if($request->percent == 'on'){
            $percent = 1;
        }else{
            $percent = 0;
        }
        $store = HairstylistGroupCommission::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_product"   =>  $request->id_product,
                    "commission_percent"   =>  $request->commission_percent,
                    "percent"   =>  $percent,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_commission(UpdateGroupCommission $request)
    {
        if(isset($request->percent)){
            $percent = 1;
        }else{
            $percent = 0;
        }
       $store = HairstylistGroupCommission::where(array("id_hairstylist_group"=>  $request->id_hairstylist_group,"id_product"   =>  $request->id_product))->update([
                    "commission_percent"   =>  $request->commission_percent,
                    "percent"   =>  $percent,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function detail_commission(Request $request)
    {
        if($request->id_hairstylist_group_commission!=''){
           $data = HairstylistGroupCommission::where(array('id_hairstylist_group_commission'=>$request->id_hairstylist_group_commission))->join('products','products.id_product','hairstylist_group_commissions.id_product')->join('hairstylist_groups','hairstylist_groups.id_hairstylist_group','hairstylist_group_commissions.id_hairstylist_group')->first();
           return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function commission(Request $request) {
        $post = $request->json()->all();
        $data = HairstylistGroupCommission::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->join('products','products.id_product','hairstylist_group_commissions.id_product')->select('id_hairstylist_group_commission','product_name','product_code','commission_percent','id_hairstylist_group','percent');
        if ($request->json('rule')){
             $this->filterListCommission($data,$request->json('rule'),$request->json('operator')??'and');
        }
        $data = $data->paginate(10);
        return response()->json(MyHelper::checkGet($data));
    }
    public function filterListCommission($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }
        $where=$operator=='and'?'where':'orWhere';
        $subjects=['product_name'];
         $i = 1;
        foreach ($subjects as $subject) {
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    if($i<=1){
                    $query->where($subject,$rule[0],$rule[1]);
                    }else{
                    $query->$where($subject,$rule[0],$rule[1]);    
                    }
                    $i++;
                }
            }
        }
    }
    public function list_hs(Request $request) {
         $post = $request->json()->all();
        if(isset($post['operator'])&&isset($post['value'])){ 
            $operator = '=';
        if($post['operator']=='like'){
            $operator = 'like"';
        }
        if($post['value']!=''){
            if($operator=='='){
             $data =  UserHairStylist::where(array('id_hairstylist_group'=>$post['id_hairstylist_group']))
                ->join('outlets','outlets.id_outlet','user_hair_stylist.id_outlet')
                ->where('fullname',$post['value'])
                ->paginate(10);
            }else{
                 $data =  UserHairStylist::where(array('id_hairstylist_group'=>$post['id_hairstylist_group']))
                ->join('outlets','outlets.id_outlet','user_hair_stylist.id_outlet')
                ->where('fullname','like','%'.$post['value'].'%')
                ->paginate(10);
            }
        }
        }else{
            $data =  UserHairStylist::where(array('id_hairstylist_group'=>$post['id_hairstylist_group']))
                ->join('outlets','outlets.id_outlet','user_hair_stylist.id_outlet')
                ->paginate(10);
        }
        return response()->json(MyHelper::checkGet($data));
    }
    
    public function list_default_incentive(Request $request) {
        $data = array();
        if($request->id_role){
         $query = EmployeeRoleIncentiveDefault::get();
         foreach ($query as $value) {
             $cek = EmployeeRoleIncentive::where(array('id_role'=>$request->id_role,'id_employee_role_default_incentive'=>$value['id_employee_role_default_incentive']))->first();
             if(!$cek){
                 array_push($data,$value);
             }
         }
        }
         return response()->json($data);
    }
    public function list_default_salary_cut(Request $request) {
        $data = array();
         if($request->id_role){
         $query = EmployeeRoleSalaryCutDefault::get();
         foreach ($query as $value) {
             $cek = EmployeeRoleSalaryCut::where(array('id_role'=>$request->id_role,'id_employee_role_default_salary_cut'=>$value['id_employee_role_default_salary_cut']))->first();
             if(!$cek){
                 array_push($data,$value);
             }
         }
        }
         return response()->json($data);
    }
    public function list_default_overtime(Request $request) {
        $data = array();
         if($request->id_hairstylist_group){
         $query = HairstylistGroupOvertimeDefault::get();
         foreach ($query as $value) {
             $cek = HairstylistGroupOvertime::where(array('id_hairstylist_group'=>$request->id_hairstylist_group,'id_hairstylist_group_default_overtimes'=>$value['id_hairstylist_group_default_overtimes']))->first();
             if(!$cek){
                 array_push($data,$value);
             }
         }
        }
         return response()->json($data);
    }
    public function list_default_proteksi(Request $request) {
        $overtime = [];
        if($request->id_hairstylist_group){
             $data = array();
            $proteksi = Setting::where('key','proteksi_hs')->first()['value_text']??[];
            $overtime = json_decode($proteksi,true);
            $group = HairstylistGroupProteksi::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->first();
            $overtime['default_value']    = 0;
            if(isset($group['value'])){
                $overtime['value_group'] = $group['value'];
            }
           return response()->json(MyHelper::checkGet($overtime));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    
    }
    public function basic_salary(Request $request) {
        if($request->id_role){
            $basic = Setting::where('key','basic_salary_employee')->first();;
            $overtime['value'] = $basic['value']??0;
            $group = EmployeeRoleBasicSalary::where(array('id_role'=>$request->id_role))->first();
            $overtime['default_value']    = 0;
            if(isset($group)){
                $overtime['value_role'] = $group['value'];
            }
           return response()->json(MyHelper::checkGet($overtime));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    
    }
    public function basic_salary_create(Request $request) {
         $data = EmployeeRoleBasicSalary::where([
                    "id_role"   =>  $request->id_role,
                ])->first();
        if($data){
            $store = EmployeeRoleBasicSalary::where([
                    "id_role"   =>  $request->id_role,
                    ])->update([
                    "value"   =>  $request->value,
                ]);
        }else{
        $store = EmployeeRoleBasicSalary::create([
                    "id_role"   =>  $request->id_role,
                    "value"   =>  $request->value,
                ]);
        }
        return response()->json(MyHelper::checkCreate($store));
    
    }
    public function reimbursement(Request $request) {
         if($request->id_role){
             $list = array();
             $data = EmployeeReimbursementProductIcount::all();
             foreach ($data as $value) {
                 $cek = EmployeeRoleReimbursementProductIcount::where(array(
                     'id_role'=>$request->id_role,
                     'id_employee_reimbursement_product_icount'=>$value['id_employee_reimbursement_product_icount']
                      ))->first();
                 $value['default']   = 0;
                 if($cek){
                     $value['value_text']   = $cek->value_text;
                     $value['default']   = 1;
                 }
                 array_push($list,$value);
             }
             return MyHelper::checkGet($list);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    
    }
    public function reimbursement_create(Request $request) {
         $data = EmployeeRoleReimbursementProductIcount::where([
                    "id_role"   =>  $request->id_role,
                    "id_employee_reimbursement_product_icount"   =>  $request->id_employee_reimbursement_product_icount,
                ])->first();
        if($data){
            $store = EmployeeRoleReimbursementProductIcount::where([
                    "id_role"   =>  $request->id_role,
                    "id_employee_reimbursement_product_icount"   =>  $request->id_employee_reimbursement_product_icount,
                    ])->update([
                    "value_text"   =>  $request->value_text,
                ]);
        }else{
        $store = EmployeeRoleReimbursementProductIcount::create([
                    "id_role"   =>  $request->id_role,
                    "id_employee_reimbursement_product_icount"   =>  $request->id_employee_reimbursement_product_icount,
                    "value_text"   =>  $request->value_text,
                ]);
        }
        return response()->json(MyHelper::checkCreate($store));
    
    }
        
}
