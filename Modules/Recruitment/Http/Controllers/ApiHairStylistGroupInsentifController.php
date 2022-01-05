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
use Modules\Recruitment\Entities\HairstylistGroupInsentifRumus;
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
        if($store){
            $store = HairstylistGroupInsentif::where(array('id_hairstylist_group_insentif'=>$request->id_hairstylist_group_insentif))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
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
        $rumus = HairstylistGroupInsentifRumus::where(array('id_hairstylist_group_insentif'=>$request->id_hairstylist_group_insentif))->delete();
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
    public function list_insentif(Request $request) {
        if($request->id_hairstylist_group){
            $data = array();
            $insentif = HairstylistGroupInsentif::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->get();
            foreach ($insentif as $value) {
                $insen = HairstylistGroupInsentifRumus::where(array('id_hairstylist_group_insentif'=>$value['id_hairstylist_group_insentif'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                if(!$insen){
                    array_push($data,$value);
                }
            }
            return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function create_rumus_insentif(Request $request) {
        if($request->id_hairstylist_group && $request->id_hairstylist_group_insentif){
             $cek = HairstylistGroupInsentifRumus::where(array('id_hairstylist_group_insentif'=>$request->id_hairstylist_group_insentif,'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
             if(!$cek){
                $store = HairstylistGroupInsentifRumus::create([
                                'id_hairstylist_group_insentif'=>$request->id_hairstylist_group_insentif,
                                'id_hairstylist_group'=>$request->id_hairstylist_group
                            ]);
             return response()->json(MyHelper::checkCreate($store)); 
             }
             return response()->json(['status' => 'success', 'messages' => ['Success Data']]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function list_rumus_insentif(Request $request) {
        if($request->id_hairstylist_group){
             $data = HairstylistGroupInsentifRumus::where(array('hairstylist_group_insentif_rumus.id_hairstylist_group'=>$request->id_hairstylist_group))->join('hairstylist_group_insentifs','hairstylist_group_insentifs.id_hairstylist_group_insentif','hairstylist_group_insentif_rumus.id_hairstylist_group_insentif')->get();
             return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_rumus_insentif(Request $request) {
        if($request->id_hairstylist_group_insentif_rumus){
             $data = HairstylistGroupInsentifRumus::where(array('id_hairstylist_group_insentif_rumus'=>$request->id_hairstylist_group_insentif_rumus))->delete();
             return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
}
