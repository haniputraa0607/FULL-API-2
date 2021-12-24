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
use Modules\Recruitment\Http\Requests\CreatePotongan;
use Modules\Recruitment\Http\Requests\InviteHS;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use Modules\Recruitment\Entities\HairstylistGroupPotongan;
use App\Http\Models\Product;
use Modules\Recruitment\Http\Requests\UpdatePotongan;

class ApiHairStylistGroupPotonganController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
//        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
    public function create(CreatePotongan $request)
    {
        $store = HairstylistGroupPotongan::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "name_potongan"   =>  $request->name_potongan,
                    "price_potongan"   =>  $request->price_potongan,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdatePotongan $request)
    {
        $store = HairstylistGroupPotongan::where(array('id_hairstylist_group_potongan'=>$request->id_hairstylist_group_potongan))->update([
                    "name_potongan"   =>  $request->name_potongan,
                    "price_potongan"   =>  $request->price_potongan,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group_potongan){
        $store = HairstylistGroupPotongan::where(array('id_hairstylist_group_potongan'=>$request->id_hairstylist_group_potongan))->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_hairstylist_group_potongan){
        $store = HairstylistGroupPotongan::where(array('id_hairstylist_group_potongan'=>$request->id_hairstylist_group_potongan))->delete();
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
             $data =  HairstylistGroupPotongan::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))
                ->where('name_potongan',$post['value'])
                ->paginate(10);
            }else{
                $data =  HairstylistGroupPotongan::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))
                ->where('name_potongan','like','%'.$post['value'].'%')
                ->paginate(10);
            }
        }
        }else{
            $data =  HairstylistGroupPotongan::paginate(10);
        }
        return response()->json(MyHelper::checkGet($data));
    }
    
}
