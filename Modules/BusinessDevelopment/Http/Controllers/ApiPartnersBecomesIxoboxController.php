<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Partner;
use App\Lib\MyHelper;
use Modules\BusinessDevelopment\Entities\PartnersBecomesIxobox;
use Modules\BusinessDevelopment\Entities\PartnersBecomesIxoboxDocument;
use Modules\BusinessDevelopment\Entities\PartnersBecomesIxoboxOutlet;
use App\Http\Models\Outlet;
use Modules\BusinessDevelopment\Http\Requests\becomes\CreateBecomesIxoboxRequest;
use Modules\BusinessDevelopment\Http\Requests\becomes\CreateBecomesIxoboxActiveRequest;
use Modules\BusinessDevelopment\Http\Requests\becomes\UpdateBecomesIxoboxRequest;
use Modules\BusinessDevelopment\Http\Requests\becomes\UpdateBecomesIxoboxActiveRequest;
use Modules\BusinessDevelopment\Http\Requests\becomes\CreateLampiranBecomesIxoboxRequest;
use DB;
 
class ApiPartnersBecomesIxoboxController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/partners_becomes_ixobox/";
    }

    public function index(Request $request)
    {
        $store = Partner::where(array('id_partner'=>$request->id_partner))->first();
        if($store){
             return response()->json(['status' => 'success','result'=>$store]);
        }
          return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }

    public function becomesIxobox(Request $request){
        $store = PartnersBecomesIxobox::where(array('id_partner'=>$request->id_partner))->orderby('created_at','desc')->get();
        if($store){
             return response()->json(['status' => 'success','result'=>$store]);
        }
          return response()->json(['status' => 'success','result'=>[]]);
    }

    public function becomes(Request $request){
        $store = PartnersBecomesIxobox::where(array('id_partner'=>$request->id_partner))->orderby('created_at','desc')->first();
        if($store){
             return response()->json(['status' => 'success','result'=>$store]);
        }
          return response()->json(['status' => 'fail','result'=>[]]);
    }

    public function create(CreateBecomesIxoboxRequest $request){
        $store = PartnersBecomesIxobox::create([
                    "id_partner"   =>  $request->id_partner,
                    "title"        =>  $request->title,
                    "close_date"   =>  date_format(date_create($request->close_date),"Y-m-d H:i:s"),
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }

    public function create_active(CreateBecomesIxoboxActiveRequest $request){
        $store = PartnersBecomesIxobox::create([
                    "id_partner"   =>  $request->id_partner,
                    "title"        =>  $request->title,
                    "start_date"   =>  date_format(date_create($request->start_date),"Y-m-d H:i:s"),
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }

    public function detail(Request $request){
        $store = PartnersBecomesIxobox::where(array('id_partners_becomes_ixobox'=>$request->id_partners_becomes_ixobox))->with(['lampiran','partner'])->first();
        if($store){
            $outlet = PartnersBecomesIxoboxOutlet::where(array('partners_becomes_ixobox_outlet.id_partners_becomes_ixobox'=>$store->id_partners_becomes_ixobox))
                               ->join('outlets','outlets.id_outlet','partners_becomes_ixobox_outlet.id_outlet')->count();
            if($outlet > 0){
                $store['outlet'] = PartnersBecomesIxoboxOutlet::where(array('partners_becomes_ixobox_outlet.id_partners_becomes_ixobox'=>$store->id_partners_becomes_ixobox))
                               ->join('outlets','outlets.id_outlet','partners_becomes_ixobox_outlet.id_outlet')->get();
            }else{
                $store['outlet'] = false;
            }
             return response()->json(['status' => 'success','result'=>$store]);
        }
          return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }

    public function reject(Request $request){
        $store = PartnersBecomesIxobox::where(array('id_partners_becomes_ixobox'=>$request->id_partners_becomes_ixobox))->update([
        'status'=>"Reject"
        ]);
        if($store){
             return response()->json(['status' => 'success','result'=>$store]);
        }
          return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }

    public function lampiranData(Request $request){
        if($request->id_partners_becomes_ixobox){
            $project = PartnersBecomesIxoboxDocument::where('id_partners_becomes_ixobox', $request->id_partners_becomes_ixobox)->orderby('created_at','desc')->get();
            foreach($project as $p => $value){
                if(isset($value['attachment']) && !empty($value['attachment'])){
                    $value['attachment'] = env('STORAGE_URL_API').$value['attachment'];
                }
            }
            return response()->json(['status' => 'success', 'result' => $project]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }

    public function update(UpdateBecomesIxoboxRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
         $store = PartnersBecomesIxobox::where(array('id_partners_becomes_ixobox'=>$request->id_partners_becomes_ixobox))->update([
                    "title"        =>  $request->title,
                    "note"         =>  $note,
                    "close_date"   =>  date_format(date_create($request->close_date),"Y-m-d H:i:s"),
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }

    public function update_active(UpdateBecomesIxoboxActiveRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
         $store = PartnersBecomesIxobox::where(array('id_partners_becomes_ixobox'=>$request->id_partners_becomes_ixobox))->update([
                    "title"        =>  $request->title,
                    "note"         =>  $note,
                    "start_date"   =>  date_format(date_create($request->start_date),"Y-m-d H:i:s"),
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }

    public function lampiranDelete(Request $request){
        if($request->id_partners_becomes_ixobox_document){
            $project = PartnersBecomesIxoboxDocument::where('id_partners_becomes_ixobox_document', $request->id_partners_becomes_ixobox_document)->delete();
            return MyHelper::checkDelete($project);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }

    public function lampiranCreate(CreateLampiranBecomesIxoboxRequest $request){
        $attachment = null;
        if(isset($request->attachment)){
            $upload = MyHelper::uploadFile($request['attachment'], $this->saveFile, 'pdf');
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
        $store = PartnersBecomesIxoboxDocument::create([
            "id_partners_becomes_ixobox"   =>  $request->id_partners_becomes_ixobox,
            "title"        =>  $request->title,
            "attachment"   => $attachment,
            "note"         =>  $request->note
        ]);
        return response()->json(MyHelper::checkCreate($store));
    }

    public function success(Request $request){
        $store = PartnersBecomesIxobox::where(array('id_partners_becomes_ixobox'=>$request->id_partners_becomes_ixobox,'status'=>'Process'))->first();
        if($store){
            $store->status = "Waiting";
            $store->save();
             return response()->json(['status' => 'success']);
        }
          return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
   }

    public function successActive(Request $request){
        $store = PartnersBecomesIxobox::where(array('id_partners_becomes_ixobox'=>$request->id_partners_becomes_ixobox,'status'=>'Process'))->first();
        if($store){
            $store->status = "Waiting";
            $latest = PartnersBecomesIxobox::where(array('id_partner'=>$store->id_partner,'status'=>'Success','start_date'=>null))->orderby('created_at','desc')->first();
            $outlet = PartnersBecomesIxoboxOutlet::where(array('id_partners_becomes_ixobox'=>$latest->id_partners_becomes_ixobox))->get();
            $store->save();
           foreach ($outlet as $value) {
                   $new_permanent = PartnersBecomesIxoboxOutlet::create(
                           [
                              'id_partners_becomes_ixobox'=>$store->id_partners_becomes_ixobox,
                              'id_outlet'=>$value->id_outlet
                           ]);
               }
             return response()->json(['status' => 'success']);
        }
          return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }

    public function cronBecomeIxobox(){
        $log = MyHelper::logCron('Partner Becomes Ixobox');
        try{
            $project = PartnersBecomesIxobox::where(array('status'=>"Waiting",'start_date'=>null))->get();
            DB::beginTransaction();
            foreach ($project as $value) {
                $store = PartnersBecomesIxobox::where(array('id_partners_becomes_ixobox'=>$value['id_partners_becomes_ixobox']))
                ->update([
                    'status'=>'Success'
                ]);
                $partner = Partner::where(array('id_partner'=>$value['id_partner']))->update([
                    'ownership_status'=>'Central'
                ]);
            }
            DB::commit();
            $log->success('success');
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }

}
