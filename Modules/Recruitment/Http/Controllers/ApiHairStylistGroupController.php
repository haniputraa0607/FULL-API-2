<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\UserHairStylistDocuments;
use Modules\Recruitment\Http\Requests\user_hair_stylist_create;
use Modules\Recruitment\Http\Requests\CreateGroup;
use Modules\Recruitment\Http\Requests\UpdateGroup;
use Modules\Recruitment\Http\Requests\CreateGroupCommission;
use Modules\Recruitment\Http\Requests\UpdateGroupCommission;
use Modules\Recruitment\Http\Requests\InviteHS;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use Modules\Recruitment\Entities\HairstylistGroupCommissionDynamic;
use App\Http\Models\Product;
use Modules\Recruitment\Entities\HairstylistGroupInsentifDefault;
use Modules\Recruitment\Entities\HairstylistGroupOvertimeDefault;
use Modules\Recruitment\Entities\HairstylistGroupPotonganDefault;
use Modules\Recruitment\Entities\HairstylistGroupInsentif;
use Modules\Recruitment\Entities\HairstylistGroupOvertime;
use Modules\Recruitment\Entities\HairstylistGroupPotongan;
use Modules\Recruitment\Entities\HairstylistGroupProteksi;
use App\Http\Models\Setting;
use DB;

class ApiHairStylistGroupController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
//        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
    public function create(CreateGroup $request)
    {
        $store = HairstylistGroup::create([
                    "hair_stylist_group_name"   =>  $request->hair_stylist_group_name,
                    "hair_stylist_group_code"   =>  $request->hair_stylist_group_code,
                    "hair_stylist_group_description"   =>  $request->hair_stylist_group_description,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateGroup $request)
    {
        $store = HairstylistGroup::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->update([
                    "hair_stylist_group_name"   =>  $request->hair_stylist_group_name,
                    "hair_stylist_group_code"   =>  $request->hair_stylist_group_code,
                    "hair_stylist_group_description"   =>  $request->hair_stylist_group_description,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    function index(Request $request) 
    {
    	$post = $request->json()->all();
        $data = HairstylistGroup::Select('hairstylist_groups.*');
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
    function list_group(Request $request) 
    {
        $data = HairstylistGroup::all();;
            return response()->json(MyHelper::checkGet($data));
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
        if($request->id_hairstylist_group!=''){
            $data = HairstylistGroup::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->first();
            if($data){
                $data['commission'] = HairstylistGroupCommission::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->join('products','products.id_product','hairstylist_group_commissions.id_product')->select('id_hairstylist_group_commission','product_name','product_code','commission_percent','id_hairstylist_group','percent')->get();
                $data['hs'] = UserHairStylist::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->join('outlets','outlets.id_outlet','user_hair_stylist.id_outlet')->get();
            }
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
            }
        }
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

        if(isset($request->type)){
            if($request->type == 'Static'){
                $dynamic = 0;
            }else{
                $dynamic = 1;
            }
        }
        DB::beginTransaction();

        $store = HairstylistGroupCommission::create([
            "id_hairstylist_group"   =>  $request->id_hairstylist_group,
            "id_product"   =>  $request->id_product,
            "commission_percent"   =>  $request->commission_percent,
            "percent"   =>  $percent,
            "dynamic"   =>  $dynamic,
        ]);
        if(!$store){
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Failed update commission product']]);
        }
        if($dynamic==1){
            $dynamic_rule = [];
            $check_unique = [];
            foreach($request->dynamic_rule ?? [] as $data_rule){
                $dynamic_rule[] = [
                    'id_hairstylist_group_commission' => $store->id_hairstylist_group_commission,
                    'qty' => $data_rule['qty'],
                    'value' => $data_rule['value'],
                ];
                if(isset($check_unique[$data_rule['qty']])){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Duplicated range']]);
                }else{
                    $check_unique[$data_rule['qty']] = $data_rule;
                }
            }
            
            $create = HairstylistGroupCommissionDynamic::insert($dynamic_rule);
        }
        DB::commit();
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_commission(UpdateGroupCommission $request)
    {
        if(isset($request->percent)){
            $percent = 1;
        }else{
            $percent = 0;
        }
        
        if(isset($request->type)){
            if($request->type == 'Static'){
                $dynamic = 0;
            }else{
                $dynamic = 1;
            }
        }
        DB::beginTransaction();
        $store = HairstylistGroupCommission::where(array("id_hairstylist_group_commission"=>  $request->id_hairstylist_group_commission))
                ->update([
                    "commission_percent"   =>  $dynamic == 0 ? $request->commission_percent : null,
                    "percent"   =>  $percent,
                    "dynamic"   =>  $dynamic,
                ]);

        if(!$store){
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Failed update commission product']]);
        }

        if($dynamic==1){
            $delete = HairstylistGroupCommissionDynamic::where('id_hairstylist_group_commission',$request->id_hairstylist_group_commission)->delete();
            $dynamic_rule = [];
            $check_unique = [];
            foreach($request->dynamic_rule ?? [] as $data_rule){
                $dynamic_rule[] = [
                    'id_hairstylist_group_commission' => $request->id_hairstylist_group_commission,
                    'qty' => $data_rule['qty'],
                    'value' => $data_rule['value'],
                ];
                if(isset($check_unique[$data_rule['qty']])){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Duplicated range']]);
                }else{
                    $check_unique[$data_rule['qty']] = $data_rule;
                }
            }
            
            $create = HairstylistGroupCommissionDynamic::insert($dynamic_rule);
        }else{
            $delete = HairstylistGroupCommissionDynamic::where('id_hairstylist_group_commission',$request->id_hairstylist_group_commission)->delete();
        }
        DB::commit();
        return response()->json(MyHelper::checkCreate($store));
    }
    public function detail_commission(Request $request)
    {
        if($request->id_hairstylist_group_commission!=''){
            $data = HairstylistGroupCommission::where(array('id_hairstylist_group_commission'=>$request->id_hairstylist_group_commission))->join('products','products.id_product','hairstylist_group_commissions.id_product')->join('hairstylist_groups','hairstylist_groups.id_hairstylist_group','hairstylist_group_commissions.id_hairstylist_group')->with(['dynamic_rule'=> function($d){$d->orderBy('qty','desc');}])->first();
            if($data){
                $product = Product::where(array('products.id_product'=>$data['id_product']))->join('product_global_price','product_global_price.id_product','products.id_product')->first();
                $data['product_price'] = 0;
                if($product){
                    $data['product_price'] = $product->product_global_price;
                }
                
                if($data['dynamic_rule']){
                    $dynamic_rule = [];
                    $count = count($data['dynamic_rule']) - 1;
                    foreach($data['dynamic_rule'] as $key => $value){
                        if($count==$key || $count==0){
                            $for_null = $value['qty']-1;
                            if($count!=0){
                                $dynamic_rule[] = [
                                    'id_hairstylist_group_commission_dynamic' => $value['id_hairstylist_group_commission_dynamic'],
                                    'qty' => $value['qty'].' - '.$data['dynamic_rule'][$key-1]['qty'],
                                    'value' => $value['value']
                                ];
                            }else{
                                $dynamic_rule[] = [
                                    'id_hairstylist_group_commission_dynamic' => $value['id_hairstylist_group_commission_dynamic'],
                                    'qty' => '>= '.$value['qty'],
                                    'value' => $value['value']
                                ];
                            }
                            $dynamic_rule[] = [
                                    'id_hairstylist_group_commission_dynamic' => null,
                                    'qty' => '0 - '.$for_null,
                                    'value' => 0
                            ];
                        }else{
                            if($key==0){
                                $dynamic_rule[] = [
                                    'id_hairstylist_group_commission_dynamic' => $value['id_hairstylist_group_commission_dynamic'],
                                    'qty' => '>= '.$value['qty'],
                                    'value' => $value['value']
                                ];
                            }else{
                                $before = $data['dynamic_rule'][$key-1]['qty'] - 1;
                                if($before == $value['qty']){
                                    $qty = $value['qty'];
                                }else{
                                    $qty = $value['qty'].' - '.$before;
                                }
                                $dynamic_rule[] = [
                                    'id_hairstylist_group_commission_dynamic' => $value['id_hairstylist_group_commission_dynamic'],
                                    'qty' => $qty,
                                    'value' => $value['value']
                                ];
                            }
                        }
                    }
                    $data['dynamic_rule_list'] = $dynamic_rule;
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function commission(Request $request) {
        $post = $request->json()->all();
        $data = HairstylistGroupCommission::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->join('products','products.id_product','hairstylist_group_commissions.id_product')->select('id_hairstylist_group_commission','product_name','product_code','commission_percent','id_hairstylist_group','percent','dynamic');
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
    
    public function list_default_insentif(Request $request) {
        $data = array();
        if($request->id_hairstylist_group){
         $query = HairstylistGroupInsentifDefault::get();
         foreach ($query as $value) {
             $cek = HairstylistGroupInsentif::where(array('id_hairstylist_group'=>$request->id_hairstylist_group,'id_hairstylist_group_default_insentifs'=>$value['id_hairstylist_group_default_insentifs']))->first();
             if(!$cek){
                 array_push($data,$value);
             }
         }
        }
         return response()->json($data);
    }
    public function list_default_potongan(Request $request) {
        $data = array();
         if($request->id_hairstylist_group){
         $query = HairstylistGroupPotonganDefault::get();
         foreach ($query as $value) {
             $cek = HairstylistGroupPotongan::where(array('id_hairstylist_group'=>$request->id_hairstylist_group,'id_hairstylist_group_default_potongans'=>$value['id_hairstylist_group_default_potongans']))->first();
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
    public function setting_potongan(Request $request) {
        $potongan = HairstylistGroupPotonganDefault::get();
        return MyHelper::checkGet($potongan);
    }
    public function setting_insentif(Request $request) {
        $insentif = HairstylistGroupInsentifDefault::get();
        return MyHelper::checkGet($insentif);
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
        public function create_proteksi(Request $request)
    {
        $data = HairstylistGroupProteksi::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    ])->first();
        if($data){
            if(isset($request->value)){
                $store = HairstylistGroupProteksi::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group
                        ])->update([
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = HairstylistGroupProteksi::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    ])->first();
                if($store){
                  $store = HairstylistGroupProteksi::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    ])->delete();  
                }else{
                  $store = 1;  
                }
            }
        }else{
            if(isset($request->value)){
                $store = HairstylistGroupProteksi::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = 1;
            }
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    
}
