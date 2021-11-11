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
use Modules\BusinessDevelopment\Entities\OutletCloseTemporary;
use Modules\BusinessDevelopment\Entities\OutletCloseTemporaryDocument;
use App\Http\Models\Outlet;
use Modules\BusinessDevelopment\Http\Requests\Outlet_CLose\CreateOutletCloseTemporaryRequest;
use Modules\BusinessDevelopment\Http\Requests\Outlet_CLose\UpdateOutletCloseTemporaryRequest;
use Modules\BusinessDevelopment\Http\Requests\Outlet_CLose\CreateLampiranCloseTemporaryRequest;

class ApiOutletCloseTemporaryController extends Controller
{
     public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFileCloseTemporary = "file/outlet_close_temporary/";
    }
    public function index(Request $request){
        $project = Outlet::join('cities','cities.id_city','outlets.id_city')
                    ->join('locations','locations.id_city','cities.id_city')
                    ->orderby('outlets.created_at','desc')->select('outlets.id_outlet','locations.id_location')->get();
               
        foreach ($project as $value) {
            $update = Outlet::where(array('id_outlet'=>$value['id_outlet']))->update([
                'id_location'=>$value['id_location']
                ]);
        }
         return response()->json(['status' => 'success', 'result' => $project]);
    }
    
    //Close Outlet
    public function createClose(CreateOutletCloseTemporaryRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
        $store = OutletCloseTemporary::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "title"        =>  $request->title,
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function updateClose(UpdateOutletCloseTemporaryRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
         $store = OutletCloseTemporary::where(array('id_outlet_close_temporary'=>$request->id_outlet_close_temporary))->update([
                   "title"        =>  $request->title,
                    "date"      =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function indexClose(Request $request){
         $store = OutletCloseTemporary::where(array('outlet_close_temporary.id_outlet'=>$request->id_outlet))->orderby('created_at','desc')->get();
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }
    public function detailClose(Request $request){
         $store = OutletCloseTemporary::where(array('id_outlet_close_temporary'=>$request->id_outlet_close_temporary))->join('outlets','outlets.id_outlet','outlet_close_temporary.id_outlet')->with(['lampiran'])->first();
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }
    public function rejectClose(Request $request){
         $store = OutletCloseTemporary::where(array('id_outlet_close_temporary'=>$request->id_outlet_close_temporary))->update([
         'status'=>"Reject"
         ]);
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
    public function successClose(Request $request){
         $store = OutletCloseTemporary::where(array('id_outlet_close_temporary'=>$request->id_outlet_close_temporary))->update([
         'status'=>"Waiting"
         ]);
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
    public function lampiranCreateClose(CreateLampiranCloseTemporaryRequest $request){
        $attachment = null;
        if(isset($request->attachment)){
                    $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFileCloseTemporary, 'pdf');
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
        $store = OutletCloseTemporaryDocument::create([
                    "id_outlet_close_temporary"   =>  $request->id_outlet_close_temporary,
                    "title"        =>  $request->title,
                    "attachment"   => $attachment,
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function lampiranDataClose(Request $request){
        if($request->id_outlet_close_temporary){
                $project = OutletCloseTemporaryDocument::where('id_outlet_close_temporary', $request->id_outlet_close_temporary)->orderby('created_at','desc')->get();
            return response()->json(['status' => 'success', 'result' => $project]);
        }
            return response()->json(['fail' => 'success', 'result' => []]);
    }
    public function lampiranDeleteClose(Request $request){
         if($request->id_outlet_close_temporary_document){
                $project = OutletCloseTemporaryDocument::where('id_outlet_close_temporary_document', $request->id_outlet_close_temporary_document)->delete();
            return MyHelper::checkDelete($project);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function cronClose(){
        $outlet = OutletCloseTemporary::where(array('status'=>"Waiting"))->get();
        foreach ($outlet as $value) {
            Location::join('outlets','outlets.id_location','locations.id_location')
                        ->where('outlets.id_outlet',$value['id_outlet'])
                        ->update(['locations.status'=>'Inactive','outlets.outlet_status'=>'Inactive']);
            $store = OutletCloseTemporary::where(array('id_outlet_close_temporary'=>$value['id_outlet_close_temporary']))
                    ->update([
                        'status'=>'Success'
                    ]);
        }
        return response()->json(['status' => 'success']);
    }
}
