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
                            ->join('cities','cities.id_city','outlets.id_city')
                            ->join('locations','locations.id_city','cities.id_city')
                           ->orderby('outlets.created_at','desc')->get();
           foreach ($project as $value) {
               $cutoff = OutletCutOff::where(array('id_outlet'=>$value['id_outlet']))
                       ->orderby('created_at','desc')
                       ->first();
               $value['cutoff'] = $cutoff;
               $change = OutletChangeOwnership::where(array('id_outlet'=>$value['id_outlet']))
                        ->orderby('created_at','desc')
                        ->first();
               $value['change'] = $change;
               $close = OutletCloseTemporary::where(array('id_outlet'=>$value['id_outlet']))
                        ->orderby('created_at','desc')
                        ->first();
               $value['close'] = $close;
           }
            return response()->json(['status' => 'success', 'result' => $project]);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function ready(Request $request){
         if($request->id_partner){
             $list = array();
                $project = Outlet::where('outlets.outlet_status',"Active")
                            ->join('cities','cities.id_city','outlets.id_city')
                            ->join('locations','locations.id_city','cities.id_city')
                            ->where('locations.id_partner',$request->id_partner)
                            ->select(['outlets.outlet_name','outlets.id_outlet'])
                           ->orderby('outlets.created_at','desc')->get();
           foreach ($project as $value) {
               $cutoff = OutletCutOff::where(array('id_outlet'=>$value['id_outlet']))
                        ->orderby('created_at','desc')
                        ->first();
               $change = OutletChangeOwnership::where(array('id_outlet'=>$value['id_outlet']))
                        ->orderby('created_at','desc')
                        ->first();
                $close = OutletCloseTemporary::where(array('id_outlet'=>$value['id_outlet']))
                        ->orderby('created_at','desc')
                        ->first();
               if(empty($cutoff)&&empty($change)&&empty($close)){
                   array_push($list,$value);
               }
               if(isset($cutoff)&&isset($change)&&isset($close)){
                   if($cutoff->status=="Reject"&&$change->status=="Reject"&&$close->status=="Reject"){
                   array_push($list,$value);
                   }
                   
               }
               if(isset($close)){
                   if($close->status=="Success"&&$close->jenis=="Active"){
                   array_push($list,$value);
                   }
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
        $store = OutletCutOff::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "title"        =>  $request->title,
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note
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
         $store = OutletCutOff::where(array('id_outlet_cut_off'=>$request->id_outlet_cut_off))->join('outlets','outlets.id_outlet','outlet_cut_off.id_outlet')->with(['lampiran'])->first();
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }
    public function rejectCutOff(Request $request){
         $store = OutletCutOff::where(array('id_outlet_cut_off'=>$request->id_outlet_cut_off))->update([
         'status'=>"Reject"
         ]);
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
    public function successCutOff(Request $request){
         $store = OutletCutOff::where(array('id_outlet_cut_off'=>$request->id_outlet_cut_off))->update([
         'status'=>"Waiting"
         ]);
         if($store){
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
            $location = Location::join('cities','cities.id_city','locations.id_city')
                        ->join('outlets','outlets.id_city','cities.id_city')
                        ->where('outlets.id_outlet',$value['id_outlet'])
                        ->update(['locations.status'=>'Inactive','outlets.outlet_status'=>'Inactive']);
            $store = OutletCutOff::where(array('id_outlet_cut_off'=>$value['id_outlet_cut_off']))
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
    
    //Change Ownership
     public function createChange(CreateOutletChangeOwnershipRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
        $store = OutletChangeOwnership::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "to_id_partner"    =>  $request->to_id_partner,
                    "title"        =>  $request->title,
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note
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
         $store = OutletChangeOwnership::where(array('id_outlet_change_ownership'=>$request->id_outlet_change_ownership))->join('outlets','outlets.id_outlet','outlet_change_ownership.id_outlet')->with(['lampiran','to_id_partner'])->first();
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }
    public function rejectChange(Request $request){
         $store = OutletChangeOwnership::where(array('id_outlet_change_ownership'=>$request->id_outlet_change_ownership))->update([
         'status'=>"Reject"
         ]);
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
    public function successChange(Request $request){
         $store = OutletChangeOwnership::where(array('id_outlet_change_ownership'=>$request->id_outlet_change_ownership))->update([
         'status'=>"Waiting"
         ]);
         if($store){
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
