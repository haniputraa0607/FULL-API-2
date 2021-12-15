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
                            ->join('locations','locations.id_location','outlets.id_location')
                            ->join('cities','cities.id_city','locations.id_city')
                           ->orderby('outlets.created_at','desc')->get();
           foreach ($project as $value) {
               $cutoff = OutletCutOff::where(array('id_outlet'=>$value['id_outlet']))
                       ->orderby('created_at','desc')
                       ->first();
               $date_cutoff = 0;
               if(isset($cutoff)){
                   if($cutoff->status!='Reject'){
                       $date_cutoff = strtotime($cutoff->created_at);
                   }
               }
               $change = OutletChangeOwnership::where(array('id_outlet'=>$value['id_outlet']))
                        ->orderby('created_at','desc')
                        ->first();
               $date_change = 0;
               if(isset($change)){
                   if($change->status!='Reject'||$change->status!='Success'){
                       $date_change = strtotime($change->created_at);
                   }
               }
               $close = OutletCloseTemporary::where(array('id_outlet'=>$value['id_outlet']))
                        ->orderby('created_at','desc')
                        ->first();
               $date_close = 0;
               $name_close = null;
               $url_detail_close = null;
               if(isset($close)){
                    $enkripsi = MyHelper::createSlug($close->id_outlet,$close->created_at);
                    $name_close = 'Detail Close';
                    $url_detail_close = $close->id_outlet;
                   if($close->jenis =='Close'){
                       if($close->status != 'Reject'){
                        $date_close = strtotime($close->created_at);   
                       }
                   }else{
                       if($close->status!='Success'){
                           $date_close = strtotime($close->created_at);
                       }
                   }
               }
               $status = 'Active';
               $status_warna = 0;
               $name = null;
               $url_detail = null;
               $id_url_detail = null;
               if($date_cutoff>=$date_change){
                   if($date_cutoff>=$date_close){
                       if($date_change>0){
                           $status = $cutoff->status.' Cut Off';
                           if($cutoff->status == "Success"){
                             $status_warna = 2; 
                             $name = 'Detail Cut Off';
                            $url_detail = 'Cut Off';
                            $id_url_detail = $cutoff->id_outlet_cut_off;
                           }elseif($cutoff->status == "Process"){
                             $status_warna = 1;
                             $name = 'Detail Cut Off';
                             $url_detail = 'Cut Off';
                            $id_url_detail = $cutoff->id_outlet_cut_off;
                           }elseif($cutoff->status == "Waiting"){
                               $status_warna = 1;
                               $name = 'Detail Cut Off';
                               $url_detail = 'Cut Off';
                               $id_url_detail = $cutoff->id_outlet_cut_off;
                           }
                       }
                   }else{
                       if($close->jenis=='Close'){
                        $status= $close->status.' Close Temporary';
                            if($close->status == "Success"){
                             $status_warna = 2; 
                           }elseif($close->status == "Process"){
                             $status_warna = 1;   
                           }elseif($close->status == "Waiting"){
                               $status_warna = 1; 
                           }
                       }else{
                          $status = $close->status.' Aktivation Outlet';
                          if($close->status == "Process"){
                             $status_warna = 1;   
                           }elseif($close->status == "Waiting"){
                               $status_warna = 1; 
                           }
                       }
                   }
               }else{
                   if($date_change>=$date_close){
                       if($change->status == 'Process'){
                       $status = $change->status.' Change Ownership';
                       $status_warna = 1; 
                        $name = 'Detail Change';
                        $url_detail = 'Change';
                        $id_url_detail = $change->id_outlet_change_ownership;
                       }elseif($change->status == 'Waiting'){
                           $status = $change->status.' Change Ownership';
                           $status_warna = 1; 
                           $name = 'Detail Change';
                           $url_detail = 'Change';
                           $id_url_detail = $change->id_outlet_change_ownership;
                       }
                   }else{
                       if($close->jenis=='Close'){
                       $status = $close->status.' Close Temporary';
                       if($close->status == "Success"){
                             $status_warna = 2; 
                           }elseif($close->status == "Process"){
                             $status_warna = 1;   
                           }elseif($close->status == "Waiting"){
                               $status_warna = 1; 
                           }
                       }else{
                           $status = $close->status.' Aktivation Outlet';
                           if($close->status == "Process"){
                             $status_warna = 1;   
                           }elseif($close->status == "Waiting"){
                               $status_warna = 1; 
                           }
                       }
                   }
               }
               $value['status_outlet']= $status;
               $value['status_warna']= $status_warna;
               $value['name_button'] = $name;
               $value['url_detail'] = $url_detail;
               $value['name_button_close'] = $name_close;
               $value['url_detail_close'] = $url_detail_close;
               $value['id_url_detail'] = $id_url_detail;
           }
            return response()->json(['status' => 'success', 'result' => $project,'id_partner'=>$request->id_partner]);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function cek_outlet($id_outlet=null){
           $cutoff = OutletCutOff::where(array('id_outlet'=>$id_outlet))
                       ->orderby('created_at','desc')
                       ->first();
               $date_cutoff = 0;
               if(isset($cutoff)){
                   if($cutoff->status!='Reject'){
                       $date_cutoff = strtotime($cutoff->created_at);
                   }
               }
               $change = OutletChangeOwnership::where(array('id_outlet'=>$id_outlet))
                        ->orderby('created_at','desc')
                        ->first();
               $date_change = 0;
               if(isset($change)){
                   if($change->status!='Reject'||$change->status!='Success'){
                       $date_change = strtotime($change->created_at);
                   }
               }
               $close = OutletCloseTemporary::where(array('id_outlet'=>$id_outlet))
                        ->orderby('created_at','desc')
                        ->first();
               $date_close = 0;
               if(isset($close)){
                   if($close->jenis =='Close'){
                       if($close->status != 'Reject'){
                        $date_close = strtotime($close->created_at);   
                       }
                   }else{
                       if($close->status!='Success'){
                           $date_close = strtotime($close->created_at);
                       }
                   }
               }
                $data = 1;
               if($date_cutoff>=$date_change){
                   if($date_cutoff>=$date_close){
                       if($date_change>0){
                           if($cutoff->status == "Success"){
                              $data = 0;
                           }elseif($cutoff->status == "Process"){
                              $data = 0;
                           }elseif($cutoff->status == "Waiting"){
                                $data = 0;
                           }
                       }
                   }else{
                       if($close->jenis=='Close'){
                      
                            if($close->status == "Success"){
                              $data = 0;
                           }elseif($close->status == "Process"){
                             $data = 0;
                           }elseif($close->status == "Waiting"){
                                $data = 0;
                           }
                       }else{
                          
                          if($close->status == "Process"){
                             $data = 0;
                           }elseif($close->status == "Waiting"){
                               $data = 0;
                           }
                       }
                   }
               }else{
                   if($date_change>=$date_close){
                       if($change->status == 'Process'){
                       $data = 0;
                       }elseif($change->status == 'Waiting'){
                            $data = 0;
                       }
                   }else{
                       if($close->jenis=='Close'){
                     
                       if($close->status == "Success"){
                              $data = 0;
                           }elseif($close->status == "Process"){
                              $data = 0;  
                           }elseif($close->status == "Waiting"){
                                $data = 0;
                           }
                       }else{
                          
                           if($close->status == "Process"){
                              $data = 0;
                           }elseif($close->status == "Waiting"){
                               $data = 0;
                           }
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
                            ->select(['outlets.outlet_name','outlets.id_outlet','outlets.outlet_code'])
                           ->orderby('outlets.created_at','asc')->get();
           foreach ($project as $value) {
                $cek_outlet = $this->cek_outlet($value['id_outlet']);
                if($cek_outlet==1){
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
            $location = Location::join('outlets','outlets.id_location','locations.id_location')
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
