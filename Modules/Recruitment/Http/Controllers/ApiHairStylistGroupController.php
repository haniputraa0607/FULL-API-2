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
use Modules\Recruitment\Http\Requests\CreateGroupCommission;
use Modules\Recruitment\Http\Requests\UpdateGroupCommission;
use Modules\Recruitment\Http\Requests\InviteHS;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use App\Http\Models\Product;
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
    public function index_old()
    {
        $data = HairstylistGroup::all();
        return MyHelper::checkGet($data);
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group!=''){
            $data = HairstylistGroup::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->first();
            if($data){
                $data['commission'] = HairstylistGroupCommission::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->join('products','products.id_product','hairstylist_group_commissions.id_product')->select('id_hairstylist_group_commission','product_name','product_code','commission_percent','id_hairstylist_group')->get();
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
        $store = Product::select(['id_product','product_name'])->get();
        foreach ($store as $value) {
            $cek = HairstylistGroupCommission::where(array('id_product'=>$value['id_product'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
            if(!$cek){
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
                 array_push($data,$value);
             }
         }
        }
         return response()->json($data);
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
        $store = HairstylistGroupCommission::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_product"   =>  $request->id_product,
                    "commission_percent"   =>  $request->commission_percent,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_commission(UpdateGroupCommission $request)
    {
       $store = HairstylistGroupCommission::where(array("id_hairstylist_group"=>  $request->id_hairstylist_group,"id_product"   =>  $request->id_product))->update([
                    "commission_percent"   =>  $request->commission_percent,
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

}
