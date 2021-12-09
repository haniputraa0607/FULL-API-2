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
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;

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
    public function index()
    {
        $data = HairstylistGroup::all();
        return MyHelper::checkGet($data);
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group!=''){
            $data = HairstylistGroup::where(array('id_hairstylist_group'=>$request->id_hairstylist_group))->first();
        return MyHelper::checkGet($data);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }

}
