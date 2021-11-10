<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Partner;
use App\Lib\MyHelper;
use Modules\BusinessDevelopment\Http\Requests\Permanent\CreateClosePermanentRequest;
use Modules\BusinessDevelopment\Http\Requests\Permanent\CreateClosePermanentActiveRequest;
use Modules\BusinessDevelopment\Http\Requests\Permanent\UpdateClosePermanentRequest;
use Modules\BusinessDevelopment\Http\Requests\Permanent\UpdateClosePermanentActiveRequest;
use Modules\BusinessDevelopment\Http\Requests\Permanent\CreateLampiranClosePermanentRequest;
use Modules\BusinessDevelopment\Entities\PartnersClosePermanent;
use Modules\BusinessDevelopment\Entities\PartnersClosePermanentDocument;
use Modules\BusinessDevelopment\Entities\PartnersClosePermanentOutlet;
use App\Http\Models\Outlet;

class ApiPartnerClosePermanentController extends Controller
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
        $this->saveFile = "file/partners_close_permanent/";
    }

    public function index(Request $request){
        $store = Partner::where(array('id_partner'=>$request->id_partner))->first();
        if($store){
             return response()->json(['status' => 'success','result'=>$store]);
        }
          return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }

    public function closePermanent(Request $request){
        $store = PartnersClosePermanent::where(array('id_partner'=>$request->id_partner))->orderby('created_at','desc')->get();
        if($store){
             return response()->json(['status' => 'success','result'=>$store]);
        }
          return response()->json(['status' => 'success','result'=>[]]);
    }

    public function detail(Request $request){
        $store = PartnersClosePermanent::where(array('id_partners_close_permanent'=>$request->id_partners_close_permanent))->with(['lampiran','partner'])->first();
        if($store){
            $outlet = PartnersClosePermanentOutlet::where(array('partners_close_permanent_outlet.id_partners_close_permanent'=>$store->id_partners_close_permanent))
                               ->join('outlets','outlets.id_outlet','partners_close_permanent_outlet.id_outlet')->count();
            if($outlet > 0){
                $store['outlet'] = PartnersClosePermanentOutlet::where(array('partners_close_permanent_outlet.id_partners_close_permanent'=>$store->id_partners_close_permanent))
                               ->join('outlets','outlets.id_outlet','partners_close_permanent_outlet.id_outlet')->get();
            }else{
                $store['outlet'] = false;
            }
             return response()->json(['status' => 'success','result'=>$store]);
        }
          return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }

    public function reject(Request $request){
        $store = PartnersClosePermanent::where(array('id_partners_close_permanent'=>$request->id_partners_close_permanent))->update([
        'status'=>"Reject"
        ]);
        if($store){
             return response()->json(['status' => 'success','result'=>$store]);
        }
          return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }

    public function permanent(Request $request){
        $store = PartnersClosePermanent::where(array('id_partner'=>$request->id_partner))->orderby('created_at','desc')->first();
        if($store){
             return response()->json(['status' => 'success','result'=>$store]);
        }
          return response()->json(['status' => 'fail','result'=>[]]);
    }

    public function create(CreateClosePermanentRequest $request){
        $store = PartnersClosePermanent::create([
                    "id_partner"   =>  $request->id_partner,
                    "title"        =>  $request->title,
                    "close_date"   =>  date_format(date_create($request->close_date),"Y-m-d H:i:s"),
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }

    public function create_active(CreateClosePermanentActiveRequest $request){
        $store = PartnersClosePermanent::create([
                    "id_partner"   =>  $request->id_partner,
                    "title"        =>  $request->title,
                    "start_date"   =>  date_format(date_create($request->start_date),"Y-m-d H:i:s"),
                    "note"         =>  $request->note
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }

    public function update(UpdateClosePermanentRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
         $store = PartnersClosePermanent::where(array('id_partners_close_permanent'=>$request->id_partners_close_permanent))->update([
                    "title"        =>  $request->title,
                    "note"         =>  $note,
                    "close_date"   =>  date_format(date_create($request->close_date),"Y-m-d H:i:s"),
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }

    public function update_active(UpdateClosePermanentActiveRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
         $store = PartnersClosePermanent::where(array('id_partners_close_permanent'=>$request->id_partners_close_permanent))->update([
                    "title"        =>  $request->title,
                    "note"         =>  $note,
                    "start_date"   =>  date_format(date_create($request->start_date),"Y-m-d H:i:s"),
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }

    public function lampiranCreate(CreateLampiranClosePermanentRequest $request){
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
        $store = PartnersClosePermanentDocument::create([
            "id_partners_close_permanent"   =>  $request->id_partners_close_permanent,
            "title"        =>  $request->title,
            "attachment"   => $attachment,
            "note"         =>  $request->note
        ]);
        return response()->json(MyHelper::checkCreate($store));
    }

    public function lampiranData(Request $request){
        if($request->id_partners_close_permanent){
            $project = PartnersClosePermanentDocument::where('id_partners_close_permanent', $request->id_partners_close_permanent)->orderby('created_at','desc')->get();
            foreach($project as $p => $value){
                if(isset($value['attachment']) && !empty($value['attachment'])){
                    $value['attachment'] = env('STORAGE_URL_API').$value['attachment'];
                }
            }
            return response()->json(['status' => 'success', 'result' => $project]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }

    public function lampiranDelete(Request $request){
        if($request->id_partners_close_permanent_document){
            $project = PartnersClosePermanentDocument::where('id_partners_close_permanent_document', $request->id_partners_close_permanent_document)->delete();
            return MyHelper::checkDelete($project);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }

    public function success(Request $request){
        $store = PartnersClosePermanent::where(array('id_partners_close_permanent'=>$request->id_partners_close_permanent,'status'=>'Process'))->first();
        if($store){
            $store->status = "Waiting";
            $store->save();
             return response()->json(['status' => 'success']);
        }
          return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
   }

    public function successActive(Request $request){
        $store = PartnersClosePermanent::where(array('id_partners_close_permanent'=>$request->id_partners_close_permanent,'status'=>'Process'))->first();
        if($store){
            $store->status = "Waiting";
            $latest = PartnersClosePermanent::where(array('id_partner'=>$store->id_partner,'status'=>'Success','start_date'=>null))->orderby('created_at','desc')->first();
            $outlet = PartnersClosePermanentOutlet::where(array('id_partners_close_permanent'=>$latest->id_partners_close_permanent))->get();
            $store->save();
           foreach ($outlet as $value) {
                   $new_permanent = PartnersClosePermanentOutlet::create(
                           [
                              'id_partners_close_permanent'=>$store->id_partners_close_permanent,
                              'id_outlet'=>$value->id_outlet
                           ]);
               }
             return response()->json(['status' => 'success']);
        }
          return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }

    public function cronInactive(){
        $project = PartnersClosePermanent::where(array('status'=>"Waiting",'start_date'=>null))->get();
        foreach ($project as $value) {
            $closeoutletall = Partner::join('locations','locations.id_partner','partners.id_partner')
            ->where('locations.id_partner', $value->id_partner)
            ->join('cities','cities.id_city','locations.id_city')
            ->join('outlets','outlets.id_city','cities.id_city')
            ->where('outlets.outlet_status','Active')
            ->get();
            foreach ($closeoutletall as $va) {
                $closeoutlet = PartnersClosePermanentOutlet::create(
                    [
                        'id_partners_close_permanent'=>$value['id_partners_close_permanent'],
                        'id_outlet'=>$va['id_outlet']
                    ]);
            }
            $store = PartnersClosePermanent::where(array('id_partners_close_permanent'=>$value['id_partners_close_permanent']))
            ->update([
            'status'=>'Success'
            ]);
            $partner = Partner::where(array('id_partner'=>$value['id_partner']))->update([
                'status'=>'Inactive'
            ]);
            $outlet = PartnersClosePermanentOutlet::where(array('id_partners_close_permanent'=>$value['id_partners_close_permanent']))->get();
            foreach ($outlet as $val) {
                $update = Outlet::where(array('id_outlet'=>$val['id_outlet']))
                ->update([
                    'outlet_status'=>'Inactive'
                ]);
            }
        }
        return response()->json(['status' => 'success']);
    }
    
    public function cronActive(){
        $project = PartnersClosePermanent::where(array('status'=>"Waiting",'close_date'=>null))->get();
        foreach ($project as $value) {
            $store = PartnersClosePermanent::where(array('id_partners_close_permanent'=>$value['id_partners_close_permanent']))
            ->update([
                'status'=>'Success'
            ]);
            $partner = Partner::where(array('id_partner'=>$value['id_partner']))->update([
                'status'=>'Active'
            ]);
            $outlet = PartnersClosePermanentOutlet::where(array('id_partners_close_permanent'=>$value['id_partners_close_permanent']))->get();
            foreach ($outlet as $val) {
                $update = Outlet::where(array('id_outlet'=>$val['id_outlet']))
                ->update([
                    'outlet_status'=>'Active'
                ]);
            }
        }
        return response()->json(['status' => 'success']);
    }
        
        
    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('businessdevelopment::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('businessdevelopment::edit');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
