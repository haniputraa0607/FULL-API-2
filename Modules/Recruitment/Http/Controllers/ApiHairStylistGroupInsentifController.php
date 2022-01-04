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
use Modules\Recruitment\Http\Requests\CreateInsentif;
use Modules\Recruitment\Http\Requests\InviteHS;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use Modules\Recruitment\Entities\HairstylistGroupInsentif;
use App\Http\Models\Product;
use Modules\Recruitment\Http\Requests\UpdateInsentif;

class ApiHairStylistGroupInsentifController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
//        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
    public function create(CreateInsentif $request)
    {
        $store = HairstylistGroupInsentif::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "name_insentif"   =>  $request->name_insentif,
                    "price_insentif"   =>  $request->price_insentif,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateInsentif $request)
    {
        $store = HairstylistGroupInsentif::where(array('id_hairstylist_group_insentif'=>$request->id_hairstylist_group_insentif))->update([
                    "name_insentif"   =>  $request->name_insentif,
                    "price_insentif"   =>  $request->price_insentif,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group_insentif){
        $store = HairstylistGroupInsentif::where(array('id_hairstylist_group_insentif'=>$request->id_hairstylist_group_insentif))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_hairstylist_group_insentif){
        $store = HairstylistGroupInsentif::where(array('id_hairstylist_group_insentif'=>$request->id_hairstylist_group_insentif))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function index(Request $request) {
         $post = $request->json()->all();
        if(isset($post['operator'])&&isset($post['value'])){ 
            $operator = '=';
        if($post['operator']=='like'){
            $operator = 'like"';
        }
        if($post['value']!=''){
            if($operator=='='){
             $data =  HairstylistGroupInsentif::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->Select('hairstylist_group_insentifs.*')
                ->where('name_insentif',$post['value'])
                ->paginate(10);
            }else{
                $data =  HairstylistGroupInsentif::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->Select('hairstylist_group_insentifs.*')
                ->where('name_insentif','like','%'.$post['value'].'%')
                ->paginate(10);
            }
        }
        }else{
            $data =  HairstylistGroupInsentif::paginate(10);
        }
        return response()->json(MyHelper::checkGet($data));
    }
    
}
