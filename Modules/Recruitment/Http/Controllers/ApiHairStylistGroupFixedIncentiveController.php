<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\UserHairStylistDocuments;
use Image;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use Modules\Recruitment\Entities\HairstylistGroupFixedIncentive;
use Modules\Recruitment\Entities\HairstylistGroupFixedIncentiveDefault;
use Modules\Recruitment\Entities\HairstylistGroupFixedIncentiveDetailDefault;
use App\Http\Models\Product;
use Modules\Recruitment\Http\Requests\Fixed_incentive\CreateDefault;
use Modules\Recruitment\Http\Requests\Fixed_incentive\UpdateDefault;
use Modules\Recruitment\Http\Requests\Fixed_incentive\Type2;
use Modules\Recruitment\Http\Requests\Fixed_incentive\CreateFixedIncentive;
class ApiHairStylistGroupFixedIncentiveController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }
    public function create(CreateFixedIncentive $request)
    {
        $data = HairstylistGroupFixedIncentive::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_fixed_incentive_detail"   =>  $request->id_hairstylist_group_default_fixed_incentive_detail,
                ])->first();
        if($data){
            if(isset($request->value)){
                $store = HairstylistGroupFixedIncentive::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_fixed_incentive_detail"   =>  $request->id_hairstylist_group_default_fixed_incentive_detail,
                ])->update([
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = HairstylistGroupFixedIncentive::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_fixed_incentive_detail"   =>  $request->id_hairstylist_group_default_fixed_incentive_detail,
                ])->first();
                if($store){
                  $store = HairstylistGroupFixedIncentive::where([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_fixed_incentive_detail"   =>  $request->id_hairstylist_group_default_fixed_incentive_detail,
                ])->delete();  
                }else{
                  $store = 1;  
                }
            }
        }else{
            if(isset($request->value)){
                $store = HairstylistGroupFixedIncentive::create([
                    "id_hairstylist_group"   =>  $request->id_hairstylist_group,
                    "id_hairstylist_group_default_fixed_incentive_detail"   =>  $request->id_hairstylist_group_default_fixed_incentive_detail,
                    "value"   =>  $request->value,
                ]);
            }else{
                $store = 1;
            }
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateFixedIncentive $request)
    {
        $store = HairstylistGroupFixedIncentive::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))->update([
                    "value"   =>  $request->value,
                ]);
        if($store){
            $store = HairstylistGroupFixedIncentive::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail(Request $request)
    {
        if($request->id_hairstylist_group_overtime){
        $store = HairstylistGroupFixedIncentive::where(array('id_hairstylist_group_overtime'=>$request->id_hairstylist_group_overtime))
                    ->join('hairstylist_group_default_fixed_incentive','hairstylist_group_default_fixed_incentive.id_hairstylist_group_default_fixed_incentive','hairstylist_group_fixed_incentive.id_hairstylist_group_default_fixed_incentive')
                    ->select('hairstylist_group_default_fixed_incentive.name','hairstylist_group_fixed_incentive.*')
                    ->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete(Request $request)
    {
        if($request->id_hairstylist_group_default_fixed_incentive && $request->id_hairstylist_group ){
        $store = HairstylistGroupFixedIncentive::where(array('id_hairstylist_group_default_fixed_incentive'=>$request->id_hairstylist_group_default_fixed_incentive,'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
        if($store){
        $store = HairstylistGroupFixedIncentive::where(array('id_hairstylist_group_default_fixed_incentive'=>$request->id_hairstylist_group_default_fixed_incentive,'id_hairstylist_group'=>$request->id_hairstylist_group))->delete();
        }else{
            $store = 1;
        }
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function index(Request $request) {
        $overtime = [];
        if($request->id_hairstylist_group){
            $data = array();
            $overtime = HairstylistGroupFixedIncentiveDefault::with(['detail'])->get();
            foreach ($overtime as $value) {
                $last = count($value['detail']);
                $x = 0;
                $i = 0;
                foreach ($value['detail'] as $va) {
                  $insen = HairstylistGroupFixedIncentive::where(array('id_hairstylist_group_default_fixed_incentive_detail'=>$va['id_hairstylist_group_default_fixed_incentive_detail'],'id_hairstylist_group'=>$request->id_hairstylist_group))->first();
                    $va['default_value'] = $va['value'];
                    $va['default']    = 0;
                    if($insen){
                       $va['value']      = $insen->value; 
                       $va['default']    = 1;
                    }
                if($value['type']=="Multiple"){
                      $i+1;
                   if($last == 1){
                        $value['ranges'] = " >= ".$value['range'];
                        $data[] = array(
                            'id_employee_role_default_fixed_incentive_detail'=>null,
                            'id_employee_role_default_fixed_incentive'=>null,
                            'value'=>0,  
                            'range'=>0,  
                            'ranges'=>$value['range'].' - 0',  
                            'default'=>1
                          );
                    }elseif(++$i === $last) {
                       $x--;
                    $va['ranges'] = $x.' - '.$va['range'];
                    $va['range'] = $va['range'] - 1;
                    if($va['range']>=0){
                    $value['detail'][] = array(
                      'id_hairstylist_group_default_fixed_incentive_detail'=>null,
                      'id_hairstylist_group_default_fixed_incentive'=>null,
                      'value'=>0,  
                      'default_value'=>0,  
                      'default'=>0,  
                      'range'=>0,  
                      'ranges'=>$va['range'].' - 0',  
                    );
                    }
                  }else{
                      if($i == 1){
                          $x = $va['range'];
                          $va['ranges'] = ">= ".$va['range'];
                      }else{
                     $b = $va['range'];
                     $x--;
                     $va['ranges'] = $x.' - '.$va['range'];
                      $x = $b;
                      }
                  }
                }
              }
            }
           return response()->json(MyHelper::checkGet($overtime));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
   
    public function list_rumus_overtime(Request $request) {
        if($request->id_hairstylist_group){
             $list = array();
             $data = HairstylistGroupFixedIncentiveDefault::all();
             foreach ($data as $value) {
                 $cek = HairstylistGroupFixedIncentive::where(array('id_hairstylist_group'=>$request->id_hairstylist_group,'id_hairstylist_group_default_fixed_incentive'=>$value['id_hairstylist_group_default_fixed_incentive']))->first();
                 if($cek){
                     $value['value']   = $cek->value;
                     $value['formula'] = $cek->formula;
                     $value['code']    = $cek->code;
                 }
                 array_push($list,$value);
             }
             return MyHelper::checkGet($list);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    
    public function create_default(CreateDefault $request)
    {
        $store = HairstylistGroupFixedIncentiveDefault::create([
                    'name_fixed_incentive' => $request->name_fixed_incentive,
                    'status' => $request->status,
                    'type' => $request->type,
                    'formula'=> $request->formula,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function update_default(UpdateDefault $request)
    {
        $store = HairstylistGroupFixedIncentiveDefault::where(array('id_hairstylist_group_default_fixed_incentive'=>$request->id_hairstylist_group_default_fixed_incentive))->update([
                    'name_fixed_incentive' => $request->name_fixed_incentive,
                    'status' => $request->status,
                    'type' => $request->type,
                    'formula'=> $request->formula,
                ]);
        if($store){
            $store = HairstylistGroupFixedIncentiveDefault::where(array('id_hairstylist_group_default_fixed_incentive'=>$request->id_hairstylist_group_default_fixed_incentive))->first();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Error Data']]);
    }
    public function detail_default(Request $request)
    {
        if($request->id_hairstylist_group_default_fixed_incentive){
        $store = HairstylistGroupFixedIncentiveDefault::where(array('id_hairstylist_group_default_fixed_incentive'=>$request->id_hairstylist_group_default_fixed_incentive))->with(['detail'])->first();
        return MyHelper::checkGet($store);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function delete_default(Request $request)
    {
        if($request->id_hairstylist_group_default_fixed_incentive){
        $store = HairstylistGroupFixedIncentiveDefault::where(array('id_hairstylist_group_default_fixed_incentive'=>$request->id_hairstylist_group_default_fixed_incentive))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    function index_default(Request $request) 
    {
    	$post = $request->json()->all();
        $data = HairstylistGroupFixedIncentiveDefault::get();
            return response()->json(MyHelper::checkGet($data));
    }
    function index_default_detail(Request $request) 
    {
    	$post = $request->json()->all();
        $store = HairstylistGroupFixedIncentiveDefault::where(array('id_hairstylist_group_default_fixed_incentive'=>$post['id_hairstylist_group_default_fixed_incentive']))->with(['detail'])->first();
        if($store){
            if($store->type == 'Single'){
                $data = HairstylistGroupFixedIncentiveDetailDefault::where('id_hairstylist_group_default_fixed_incentive',$post['id_hairstylist_group_default_fixed_incentive'])->first();
            }else{
                $data = HairstylistGroupFixedIncentiveDetailDefault::where('id_hairstylist_group_default_fixed_incentive',$post['id_hairstylist_group_default_fixed_incentive'])->orderby('range','desc')->get();
                $last = count($data);
                $x = 0;
                $i = 0;
                foreach ($data as $key => $value) {
                    $i+1;
                   if($last == 1){
                        $value['ranges'] = " >= ".$value['range'];
                        $data[] = array(
                            'id_employee_role_default_fixed_incentive_detail'=>null,
                            'id_employee_role_default_fixed_incentive'=>null,
                            'value'=>0,  
                            'range'=>0,  
                            'ranges'=>$value['range'].' - 0',  
                            'default'=>1
                          );
                    }elseif(++$i === $last) {
                       $x--;
                    $value['ranges'] = $x.' - '.$value['range'];
                    $value['range'] = $value['range'] - 1;
                    if($value['range']>=0){
                    $data[] = array(
                      'id_hairstylist_group_default_fixed_incentive_detail'=>null,
                      'id_hairstylist_group_default_fixed_incentive'=>null,
                      'value'=>0,  
                      'range'=>0,  
                      'ranges'=>$value['range'].' - 0',  
                      'default'=>1
                    );
                    }
                  }else{
                      if($i == 1){
                          $x = $value['range'];
                          $value['ranges'] = " >= ".$value['range'];
                      }else{
                     $b = $value['range'];
                     $x--;
                     $value['ranges'] = $x.' - '.$value['range'];
                      $x = $b;
                      }
                  }
                }
            }
            return response()->json(MyHelper::checkGet($data));
        }
         return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
       
    }
    public function sortDate($a, $b) 
    {
        if ($a['range'] == $b['range']) return 0;
        return $a['range'] > $b['range'] ?-1:1;
    }
    function type1(Request $request) {
        $store = HairstylistGroupFixedIncentiveDetailDefault::where([
                    'id_hairstylist_group_default_fixed_incentive' => $request->id_hairstylist_group_default_fixed_incentive,
                    
                ])->first();
        if($store){
             $store = HairstylistGroupFixedIncentiveDetailDefault::where([
                    'id_hairstylist_group_default_fixed_incentive' => $request->id_hairstylist_group_default_fixed_incentive
                ])->update([
                    'value' => $request->value,
                ]);
        }else{
            $store = HairstylistGroupFixedIncentiveDetailDefault::create([
                    'id_hairstylist_group_default_fixed_incentive' => $request->id_hairstylist_group_default_fixed_incentive,
                    'value' => $request->value,
                ]);
        }
        return response()->json(MyHelper::checkCreate($store));
    }
    function type2(Type2 $request) {
        $store = HairstylistGroupFixedIncentiveDetailDefault::create([
                    'id_hairstylist_group_default_fixed_incentive' => $request->id_hairstylist_group_default_fixed_incentive,
                    'range' => $request->range,
                    'value'=> $request->value,
                ]);
        return response()->json(MyHelper::checkCreate($store));
    }
    public function delete_detail(Request $request)
    {
        if($request->id_hairstylist_group_default_fixed_incentive_detail ){
        $store = HairstylistGroupFixedIncentiveDetailDefault::where(array('id_hairstylist_group_default_fixed_incentive_detail'=>$request->id_hairstylist_group_default_fixed_incentive_detail))->delete();
        return response()->json(MyHelper::checkCreate($store));
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    
}
