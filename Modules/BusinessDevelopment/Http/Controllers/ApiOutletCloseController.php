<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\PartnersLog;
use Modules\BusinessDevelopment\Entities\Location;
use App\Lib\MyHelper;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Models\City;
use App\Http\Models\Setting;
use Illuminate\Support\Facades\App;
use Modules\Brand\Entities\Brand;
use PDF;
use Storage;
use Modules\BusinessDevelopment\Entities\StepsLog;
use Modules\BusinessDevelopment\Entities\ConfirmationLetter;
use Modules\BusinessDevelopment\Entities\FormSurvey;
use function GuzzleHttp\json_decode;
use Modules\BusinessDevelopment\Entities\OutletCutOff;
use Modules\BusinessDevelopment\Entities\OutletCutOffDocument;
use Modules\BusinessDevelopment\Entities\OutletChangeOwnership;
use Modules\BusinessDevelopment\Entities\OutletChangeOwnershipDocument;
use Modules\BusinessDevelopment\Entities\OutletCloseTemporary;
use Modules\BusinessDevelopment\Entities\OutletManage;
use App\Http\Models\Outlet;
use Modules\BusinessDevelopment\Http\Requests\CutOff\CreateOutletCutOffRequest;
use Modules\BusinessDevelopment\Http\Requests\CutOff\UpdateOutletCutOffRequest;
use Modules\BusinessDevelopment\Http\Requests\CutOff\CreateLampiranCutOffRequest;
use Modules\BusinessDevelopment\Http\Requests\CutOff\CreateOutletChangeOwnershipRequest;
use Modules\BusinessDevelopment\Http\Requests\CutOff\UpdateOutletChangeOwnershipRequest;
use Modules\BusinessDevelopment\Http\Requests\CutOff\CreateLampiranChangeOwnershipRequest;
class ApiOutletCloseController extends Controller
{
     public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFileCutOff = "file/outlet_cutoff/";
        $this->saveFileChangeOwnership = "file/outlet_change/";
    }
    public function index(Request $request){
         if($request->id_partner){
                $project = Outlet::where('locations.id_partner',$request->id_partner)
                            ->join('locations','locations.id_location','outlets.id_location')
                            ->join('cities','cities.id_city','locations.id_city')
                            ->where('locations.status','!=','Candidate')
                            ->where('locations.status','!=','Rejected')
                            ->where('locations.status','!=','Close')
                            ->orderby('outlets.created_at','desc')->get();
           
            return response()->json(['status' => 'success', 'result' => $project,'id_partner'=>$request->id_partner]);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function detail(Request $request){
         if($request->id_outlet){
                $partner = Outlet::where('outlets.id_outlet',$request->id_outlet)
                            ->join('locations','locations.id_location','outlets.id_location')->select('id_partner')->first();
                $project = OutletManage::where('outlet_manage.id_outlet',$request->id_outlet)
                            ->join('outlets','outlets.id_outlet','outlet_manage.id_outlet')
                            ->join('cities','cities.id_city','outlets.id_city')
                            ->select('id_outlet_manage','outlet_name','outlet_code','city_name','outlet_manage.type','status','outlet_manage.created_at')
                            ->orderby('outlet_manage.created_at','desc')->get();
            return response()->json(['status' => 'success', 'result' => $project,'id_partner'=>$partner->id_partner]);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function cek_outlet($manage=null){
            $data = 1;
           if($manage){
               if($manage->type == "Cut Off"){
                   if($manage->status == 'Waiting' || $manage->status == 'Process' || $manage->status == 'Success'){
                       $data = 0;
                   }
               }
               if($manage->type == "Change Ownership"){
                   if($manage->status == 'Waiting' || $manage->status == 'Process'){
                       $data = 0;
                   }
               }
               if($manage->type == "Close Temporary"){
                   if($manage->status == 'Waiting' || $manage->status == 'Process' || $manage->status == 'Success'){
                       $data = 0;
                   }
               }
               if($manage->type == "Active Temporary"){
                   $data = 0;
                   if($manage->status == 'Success'){
                       $data = 1;
                   }
               }
               if($manage->type == "Change Location"){
                   if($manage->status == 'Waiting' || $manage->status == 'Process' ){
                       $data = 0;
                   }
               }
           }
               return $data;
    }
    public function cek_active($manage=null){
            $data = 0;
           if($manage){
               if($manage->type == "Close Temporary"){
                   if($manage->status == 'Success'){
                       $data = 1;
                   }
               }
               if($manage->type == "Active Temporary"){
                   $data = 1;
                   if($manage->status == 'Success' || $manage->status == 'Process' || $manage->status == 'Waiting'){
                       $data = 0;
                   }
               }
           }
               return $data;
    }

    public function ready(Request $request){
         if($request->id_partner){
             $list = array();
                $project = Outlet::where('outlets.outlet_status',"Active")
                            ->join('cities','cities.id_city','outlets.id_city')
                            ->join('locations','locations.id_location','outlets.id_location')
                            ->where('locations.id_partner',$request->id_partner)
                            ->where('locations.status','!=','Candidate')
                            ->where('locations.status','!=','Rejected')
                            ->where('locations.status','!=','Close')
                            ->select(['outlets.outlet_name','outlets.id_outlet','outlets.outlet_code'])
                           ->orderby('outlets.created_at','asc')->get();
           foreach ($project as $value) {
                $manage = OutletManage::where(array('id_outlet'=>$value['id_outlet']))->orderby('created_at','DESC')->first();
                $cek_outlet = $this->cek_outlet($manage);
                if($cek_outlet==1){
                    array_push($list,$value);
                }
           }
            return response()->json(['status' => 'success', 'result' => $list]);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function active(Request $request){
         if($request->id_partner){
             $list = array();
                $project = Outlet::where('outlets.outlet_status',"Inactive")
                            ->join('cities','cities.id_city','outlets.id_city')
                            ->join('locations','locations.id_location','outlets.id_location')
                            ->where('locations.id_partner',$request->id_partner)
                            ->where('locations.status','!=','Candidate')
                            ->where('locations.status','!=','Rejected')
                            ->where('locations.status','!=','Close')
                            ->select(['outlets.outlet_name','outlets.id_outlet','outlets.outlet_code'])
                           ->orderby('outlets.created_at','asc')->get();
           foreach ($project as $value) {
                $manage = OutletManage::where(array('id_outlet'=>$value['id_outlet']))->orderby('created_at','DESC')->first();
                $cek_active = $this->cek_active($manage);
                if($cek_active==1){
                    array_push($list,$value);
                }
           }
            return response()->json(['status' => 'success', 'result' => $list]);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function partner(Request $request){
         if($request->id_partner){
                $project = Partner::where('status',"Active")->where('id_partner','!=',$request->id_partner)->select(['id_partner','name','phone'])->get();
            return response()->json(['status' => 'success', 'result' => $project]);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    //CutOff
    public function createCutOff(CreateOutletCutOffRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
        $manage = OutletManage::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "type"         =>  "Cut Off",
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
        ]);
        $store = OutletCutOff::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "title"        =>  $request->title,
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note,
                    'id_outlet_manage'=>$manage->id_outlet_manage
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function updateCutOff(UpdateOutletCutOffRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
         $store = OutletCutOff::where(array('id_outlet_cut_off'=>$request->id_outlet_cut_off))->update([
                   "title"        =>  $request->title,
                    "date"      =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function detailCutOff(Request $request){
         $store = OutletCutOff::where(array('id_outlet_manage'=>$request->id_outlet_manage))->join('outlets','outlets.id_outlet','outlet_cut_off.id_outlet')->with(['lampiran'])->first();
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }
    public function rejectCutOff(Request $request){
         $store = OutletCutOff::where(array('id_outlet_cut_off'=>$request->id_outlet_cut_off))->first();
         if($store){
             $manage = OutletManage::where(array('id_outlet_manage'=>$store->id_outlet_manage))->update([
            'status'=>"Reject"
            ]);
             $store = OutletCutOff::where(array('id_outlet_cut_off'=>$request->id_outlet_cut_off))->update([
            'status'=>"Reject"
            ]);
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
    public function successCutOff(Request $request){
          $store = OutletCutOff::where(array('id_outlet_cut_off'=>$request->id_outlet_cut_off))->first();
         if($store){
             $manage = OutletManage::where(array('id_outlet_manage'=>$store->id_outlet_manage))->update([
            'status'=>"Waiting"
            ]);
             $store = OutletCutOff::where(array('id_outlet_cut_off'=>$request->id_outlet_cut_off))->update([
            'status'=>"Waiting"
            ]);
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
    public function lampiranCreateCutOff(CreateLampiranCutOffRequest $request){
        $attachment = null;
        if(isset($request->attachment)){
                    $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFileCutOff, 'pdf');
                     if (isset($upload['status']) && $upload['status'] == "success") {
                             $attachment = $upload['path'];
                         } else {
                             $result = [
                                 'status'   => 'fail',
                                 'messages' => ['fail upload file']
                             ];
                             return $result;
                         }
                 }
        $store = OutletCutOffDocument::create([
                    "id_outlet_cut_off"   =>  $request->id_outlet_cut_off,
                    "title"        =>  $request->title,
                    "attachment"   => $attachment,
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function lampiranDataCutOff(Request $request){
        if($request->id_outlet_cut_off){
            $project = OutletCutOffDocument::where('id_outlet_cut_off', $request->id_outlet_cut_off)->orderby('created_at','desc')->get();
            return response()->json(['status' => 'success', 'result' => $project]);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function lampiranDeleteCutOff(Request $request){
         if($request->id_outlet_cut_off_document){
                $project = OutletCutOffDocument::where('id_outlet_cut_off_document', $request->id_outlet_cut_off_document)->delete();
            return MyHelper::checkDelete($project);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function cronCutOff(){
        $log = MyHelper::logCron('Cron Cut Off Outlet');
        try {
        $outlet = OutletCutOff::where(array('status'=>"Waiting"))->wheredate('date','<=',date('Y-m-d H:i:s'))->get();
        foreach ($outlet as $value) {
            $location = Location::join('outlets','outlets.id_location','locations.id_location')
                        ->where('outlets.id_outlet',$value['id_outlet'])
                        ->update(['locations.status'=>'Inactive','outlets.outlet_status'=>'Inactive']);
            $store = OutletCutOff::where(array('id_outlet_cut_off'=>$value['id_outlet_cut_off']))
                    ->first();
            $store->status = 'Success';
            $store->save();
            $manage = OutletManage::where(array('id_outlet_manage'=>$store->id_outlet_manage))->update([
            'status'=>"Success"
            ]);
        }
          $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }
    
    //Change Ownership
     public function createChange(CreateOutletChangeOwnershipRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
         $manage = OutletManage::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "type"         =>  "Change Ownership",
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
        ]);
        $store = OutletChangeOwnership::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "to_id_partner"    =>  $request->to_id_partner,
                    "title"        =>  $request->title,
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note,
                    "id_outlet_manage"=>$manage->id_outlet_manage
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function updateChange(UpdateOutletChangeOwnershipRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
         $store = OutletChangeOwnership::where(array('id_outlet_change_ownership'=>$request->id_outlet_change_ownership))->update([
                   "title"        =>  $request->title,
                    "date"      =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function detailChange(Request $request){
         $store = OutletChangeOwnership::where(array('id_outlet_manage'=>$request->id_outlet_manage))->join('outlets','outlets.id_outlet','outlet_change_ownership.id_outlet')->with(['lampiran','to_id_partner'])->first();
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }
    public function rejectChange(Request $request){
         $store = OutletChangeOwnership::where(array('id_outlet_change_ownership'=>$request->id_outlet_change_ownership))->first();
         if($store){
             $manage = OutletManage::where(array('id_outlet_manage'=>$store->id_outlet_manage))->update([
            'status'=>"Reject"
            ]);
             $store = OutletChangeOwnership::where(array('id_outlet_change_ownership'=>$request->id_outlet_change_ownership))->update([
            'status'=>"Reject"
            ]);
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
    public function successChange(Request $request){
          $store = OutletChangeOwnership::where(array('id_outlet_change_ownership'=>$request->id_outlet_change_ownership))->first();
         if($store){
             $manage = OutletManage::where(array('id_outlet_manage'=>$store->id_outlet_manage))->update([
            'status'=>"Waiting"
            ]);
             $store = OutletChangeOwnership::where(array('id_outlet_change_ownership'=>$request->id_outlet_change_ownership))->update([
            'status'=>"Waiting"
            ]);
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
    public function lampiranCreateChange(CreateLampiranChangeOwnershipRequest $request){
        $attachment = null;
        if(isset($request->attachment)){
                    $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFileCutOff, 'pdf');
                     if (isset($upload['status']) && $upload['status'] == "success") {
                             $attachment = $upload['path'];
                         } else {
                             $result = [
                                 'status'   => 'fail',
                                 'messages' => ['fail upload file']
                             ];
                             return $result;
                         }
                 }
        $store = OutletChangeOwnershipDocument::create([
                    "id_outlet_change_ownership"   =>  $request->id_outlet_change_ownership,
                    "title"        =>  $request->title,
                    "attachment"   => $attachment,
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function lampiranDataChange(Request $request){
        if($request->id_outlet_change_ownership){
                $project = OutletChangeOwnershipDocument::where('id_outlet_change_ownership', $request->id_outlet_change_ownership)->orderby('created_at','desc')->get();
            return response()->json(['status' => 'success', 'result' => $project]);
        }
            return response()->json(['fail' => 'success', 'result' => []]);
    }
    public function lampiranDeleteChange(Request $request){
         if($request->id_outlet_change_ownership_document){
                $project = OutletChangeOwnershipDocument::where('id_outlet_change_ownership_document', $request->id_outlet_change_ownership_document)->delete();
            return MyHelper::checkDelete($project);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function cronChange(){
         $log = MyHelper::logCron('Cron Change Ownership Outlet');
        try {
            $outlet = OutletChangeOwnership::where(array('status'=>"Waiting"))->wheredate('date','<=',date('Y-m-d H:i:s'))->get();
            foreach ($outlet as $value) {
                $location = Location::join('outlets','outlets.id_location','locations.id_location')
                            ->where('outlets.id_outlet',$value['id_outlet'])->first();
                $location->id_partner = $value['to_id_partner'];
                $location->save();
                $store = OutletChangeOwnership::where(array('id_outlet_change_ownership'=>$value['id_outlet_change_ownership']))
                        ->update([
                            'status'=>'Success'
                        ]);
            }
           $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }
}