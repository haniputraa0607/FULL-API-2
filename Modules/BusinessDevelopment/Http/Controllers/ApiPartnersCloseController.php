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
use Modules\BusinessDevelopment\Http\Requests\Close\CreateCloseTemporaryRequest;
use Modules\BusinessDevelopment\Http\Requests\Close\UpdateCloseTemporaryRequest;
use Modules\BusinessDevelopment\Http\Requests\Close\CreateCloseTemporaryActiveRequest;
use Modules\BusinessDevelopment\Http\Requests\Close\UpdateCloseTemporaryActiveRequest;
use Modules\BusinessDevelopment\Http\Requests\Close\SubmitCloseTemporaryRequest;
use Modules\BusinessDevelopment\Http\Requests\Close\CreateLampiranCloseTemporaryRequest;
use function GuzzleHttp\json_decode;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporary;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporaryDocument;

class ApiPartnersCloseController extends Controller
{
     public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/partners_close_temporary/";
    }
    public function create(CreateCloseTemporaryRequest $request){
        $store = PartnersCloseTemporary::create([
                    "id_partner"   =>  $request->id_partner,
                    "title"        =>  $request->title,
                    "close_date"   =>  date_format(date_create($request->close_date),"Y-m-d H:i:s"),
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateCloseTemporaryRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
         $store = PartnersCloseTemporary::where(array('id_partners_close_temporary'=>$request->id_partners_close_temporary))->update([
                    "title"        =>  $request->title,
                    "note"         =>  $note,
                    "close_date"   =>  date_format(date_create($request->close_date),"Y-m-d H:i:s"),
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function create_active(CreateCloseTemporaryActiveRequest $request){
        $store = PartnersCloseTemporary::create([
                    "id_partner"   =>  $request->id_partner,
                    "title"        =>  $request->title,
                    "start_date"   =>  date_format(date_create($request->start_date),"Y-m-d H:i:s"),
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function update_active(UpdateCloseTemporaryActiveRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
         $store = PartnersCloseTemporary::where(array('id_partners_close_temporary'=>$request->id_partners_close_temporary))->update([
                    "title"        =>  $request->title,
                    "note"         =>  $note,
                    "start_date"   =>  date_format(date_create($request->start_date),"Y-m-d H:i:s"),
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function submit(SubmitCloseTemporaryRequest $request){
         $store = PartnersCloseTemporary::where(array('id_partners_close_temporary'=>$request->id_partners_close_temporary))->update([
                    "title"        =>  $request->title,
                    "close_date"   =>  $request->close_date,
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function index(Request $request){
         $store = Partner::where(array('id_partner'=>$request->id_partner))->first();
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }
    public function closeTemporary(Request $request){
         $store = PartnersCloseTemporary::where(array('id_partner'=>$request->id_partner))->orderby('created_at','desc')->get();
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','result'=>[]]);
    }
    public function temporary(Request $request){
         $store = PartnersCloseTemporary::where(array('id_partner'=>$request->id_partner))->orderby('created_at','desc')->first();
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'fail','result'=>[]]);
    }
    public function detail(Request $request){
         $store = PartnersCloseTemporary::where(array('id_partners_close_temporary'=>$request->id_partners_close_temporary))->with(['lampiran'])->first();
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }
    public function reject(Request $request){
         $store = PartnersCloseTemporary::where(array('id_partners_close_temporary'=>$request->id_partners_close_temporary))->update([
         'status'=>"Reject"
         ]);
         if($store){
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
    public function success(Request $request){
         $store = PartnersCloseTemporary::where(array('id_partners_close_temporary'=>$request->id_partners_close_temporary))->first();
         if($store){
             $store->status = "Success";
             $partner = Partner::where(array('id_partner'=>$store->id_partner))->update([
                 'status'=>'Inactive'
             ]);
             $store->save();
             
             $outlet = Partner::join('locations','locations.id_partner','partners.id_partner')
                ->where('locations.id_partner', $store->id_partner)
                ->join('cities','cities.id_city','locations.id_city')
                ->join('outlets','outlets.id_city','cities.id_city')
                ->update(['outlet_status'=>"Inactive"]);
              return response()->json(['status' => 'success','result'=>$outlet]);
         }
           return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }
    public function lampiranCreate(CreateLampiranCloseTemporaryRequest $request){
        $attachment = null;
        if(isset($request->attachment)){
                    $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFile, 'pdf');
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
        $store = PartnersCloseTemporaryDocument::create([
                    "id_partners_close_temporary"   =>  $request->id_partners_close_temporary,
                    "title"        =>  $request->title,
                    "attachment"   => $attachment,
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function lampiranData(Request $request){
        if($request->id_partners_close_temporary){
                $project = PartnersCloseTemporaryDocument::where('id_partners_close_temporary', $request->id_partners_close_temporary)->orderby('created_at','desc')->get();
            return response()->json(['status' => 'success', 'result' => $project]);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function lampiranDelete(Request $request){
         if($request->id_partners_close_temporary_document){
                $project = PartnersCloseTemporaryDocument::where('id_partners_close_temporary_document', $request->id_partners_close_temporary_document)->delete();
            return MyHelper::checkDelete($project);
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
}
