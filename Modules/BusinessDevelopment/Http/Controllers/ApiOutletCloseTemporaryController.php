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
use Modules\BusinessDevelopment\Entities\OutletCloseTemporaryLocation;
use Modules\BusinessDevelopment\Entities\OutletCloseTemporaryLocationOld;
use Modules\BusinessDevelopment\Entities\OutletCloseTemporaryStep;
use Modules\BusinessDevelopment\Entities\OutletCloseTemporaryFormSurvey;
use Modules\BusinessDevelopment\Entities\OutletCloseTemporaryConfirmationLetter;
use App\Http\Models\Outlet;
use Modules\BusinessDevelopment\Http\Requests\OutletClose\CreateOutletCloseTemporaryRequest;
use Modules\BusinessDevelopment\Http\Requests\OutletClose\CreateOutletActiveRequest;
use Modules\BusinessDevelopment\Http\Requests\OutletClose\UpdateOutletCloseTemporaryRequest;
use Modules\BusinessDevelopment\Http\Requests\OutletClose\CreateLampiranCloseTemporaryRequest;
use Modules\BusinessDevelopment\Http\Requests\OutletClose\UpdateOutletCloseTemporaryActiveRequest;
use Modules\BusinessDevelopment\Entities\OutletManage;
class ApiOutletCloseTemporaryController extends Controller
{
     public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFileActiveFollowUp = "file/outlet_close_temporary/follow_up/";
        $this->saveFileCloseTemporary = "file/outlet_close_temporary/";
        $this->confirmation = "file/outlet_close_temporary/confirmation/";
        $this->form_survey = "file/outlet_close_temporary/form_survey/";
    }
    public function index(Request $request){
        $project = Outlet::join('locations','locations.id_location','outlets.id_location')
                    ->orderby('outlets.created_at','desc')->select('outlets.id_outlet','locations.id_location')->get();
               
        foreach ($project as $value) {
            $update = Outlet::where(array('id_outlet'=>$value['id_outlet']))->update([
                'id_location'=>$value['id_location']
                ]);
        }
         return response()->json(['status' => 'success', 'result' => $project]);
    }
    
    //Close Outlet
    public function createActive(CreateOutletActiveRequest $request){
        $note = null;
        $store = null;
        if(isset($request->note)){
            $note = $request->note;
        }
        $manage = OutletManage::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "type"         =>  "Active Temporary",
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
        ]);
         $store = OutletCloseTemporary::create([
                   "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "jenis"        =>  "Active",
                    "title"        =>  $request->title,
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note,
                    'id_outlet_manage'=>$manage->id_outlet_manage
                ]);   
        
            return response()->json(MyHelper::checkCreate($store));
    }
    public function createClose(CreateOutletCloseTemporaryRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
        $manage = OutletManage::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "type"         =>  "Close Temporary",
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
        ]);
        $store = OutletCloseTemporary::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "title"        =>  $request->title,
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note,
                    'id_outlet_manage'=>$manage->id_outlet_manage
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
    public function updateCloseActive(UpdateOutletCloseTemporaryActiveRequest $request){
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
                $store = OutletCloseTemporary::where(array('id_outlet_close_temporary'=>$request->id_outlet_close_temporary))->update([
                   "title"        =>  $request->title,
                    "date"      =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "note"         =>  $note
                ]);
                $location = OutletCloseTemporaryLocation::where(array('id_outlet_close_temporary_location'=>$request->id_outlet_close_temporary_location))->update([
                    "name"    =>  $request->nameLocation,
                    "address"        =>  $request->addressLocation,
                    "id_city"    =>  $request->id_cityLocation,
                    "longitude"        =>  $request->longitudeLocation,
                    "latitude"         => $request->latitudeLocation
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function indexClose(Request $request){
         $store = OutletCloseTemporary::where(array('outlet_close_temporary.id_outlet'=>$request->id_outlet))->orderby('created_at','desc')->get();
         $outlet = Outlet::where('id_outlet',$request->id_outlet)->join('cities','cities.id_city','outlets.id_city')->join('locations','locations.id_location','outlets.id_location')->first();
         return response()->json(['status' => 'success','result'=>array(
             'outlet'=>$outlet,
             'list'=>$store
         )]);
    }
    public function detailClose(Request $request){
         $store = OutletCloseTemporary::where(array('id_outlet_manage'=>$request->id_outlet_manage))
                 ->join('outlets','outlets.id_outlet','outlet_close_temporary.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_location','outlets.id_location')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->select('outlet_close_temporary.*','outlets.*','locations.id_location','locations.id_brand','partners.gender','partners.name as name_partner')
                 ->first();
         if($store){
             if($store->jenis == 'Active'&&$store->jenis_active == 'Change Location'){
                 $location = OutletCloseTemporaryLocation::where(array('id_outlet_close_temporary'=>$store->id_outlet_close_temporary))->first();
                 $step = OutletCloseTemporaryStep::where(array('id_outlet_close_temporary'=>$store->id_outlet_close_temporary))->get();
                 $form = OutletCloseTemporaryFormSurvey::where(array('id_outlet_close_temporary'=>$store->id_outlet_close_temporary))->get();
                 $letter = OutletCloseTemporaryConfirmationLetter::where(array('id_outlet_close_temporary'=>$store->id_outlet_close_temporary))->first();
                 $store->lokasi = $location;
                 $store->step = $step;
                 $store->form = $form;
                 $store->letter = $letter;
                 if($store->status=='Success'){
                     $store->lokasi_old = OutletCloseTemporaryLocationOld::where(array('id_outlet_close_temporary'=>$store->id_outlet_close_temporary))->first();
                 }
                 return response()->json(['status' => 'success','result'=>$store]);
             }
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
        $log = MyHelper::logCron('Cron Close Outlet Close Temporary');
        try {
        $outlet = OutletCloseTemporary::where(array('status'=>"Waiting",'jenis'=>'Close'))->wheredate('date','<=',date('Y-m-d H:i:s'))->get();
        foreach ($outlet as $value) {
            Location::join('outlets','outlets.id_location','locations.id_location')
                        ->where('outlets.id_outlet',$value['id_outlet'])
                        ->update(['locations.status'=>'Inactive','outlets.outlet_status'=>'Inactive']);
            $store = OutletCloseTemporary::where(array('id_outlet_close_temporary'=>$value['id_outlet_close_temporary']))
                    ->update([
                        'status'=>'Success'
                    ]);
            $close = OutletManage::where(array('id_outlet_manage'=>$value['id_outlet_manage']))
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
    public function cronActive(){
        $log = MyHelper::logCron('Cron Active Outlet Close Temporary,No Change Location');
        try {
        $outlet = OutletCloseTemporary::where(array('status'=>"Waiting",'jenis'=>'Active'))->wheredate('date','<=',date('Y-m-d H:i:s'))->get();
        foreach ($outlet as $value) {
            Location::join('outlets','outlets.id_location','locations.id_location')
                        ->where('outlets.id_outlet',$value['id_outlet'])
                        ->update(['locations.status'=>'Active','outlets.outlet_status'=>'Active']);
            $store = OutletCloseTemporary::where(array('id_outlet_close_temporary'=>$value['id_outlet_close_temporary']))
                    ->update([
                        'status'=>'Success'
                    ]);
            $active = OutletManage::where(array('id_outlet_manage'=>$value['id_outlet_manage']))
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
    public function cronChangeLocation(){
        $log = MyHelper::logCron('Cron Active Outlet Close Temporary, Change Location');
        try {
           $outlet = OutletCloseTemporary::where(array('status'=>"Waiting",'jenis'=>'Active','jenis_active'=>'Change Location'))->wheredate('date','<=',date('Y-m-d H:i:s'))->get();
           foreach ($outlet as $value) {
               $location = OutletCloseTemporaryLocation::where(array('id_outlet_close_temporary'=>$value['id_outlet_close_temporary']))->join('cities','cities.id_city','outlet_close_temporary_location.id_city')->first();
               $lokasi = Location::where(array('id_location'=>$location->id_location))->first();
               $lokasi['id_outlet_close_temporary'] = $value['id_outlet_close_temporary'];
               $old_lokasi = OutletCloseTemporaryLocationOld::create([
                    'name'=>$lokasi->name,
                    'address'=>$lokasi->address,
                    'mall'=>$lokasi->mall,
                    'id_location'=>$lokasi->id_location,
                    'id_outlet_close_temporary'=>$lokasi->id_outlet_close_temporary,
                    'id_city'=>$lokasi->id_city,
                    'latitude'=>$lokasi->latitude,
                    'longitude'=>$lokasi->longitude,
                    'id_brand'=>$lokasi->id_brand,
                    'location_large'=>$lokasi->location_large,
                    'rental_price'=>$lokasi->rental_price,
                    'service_charge'=>$lokasi->service_charge,
                    'promotion_levy'=>$lokasi->promotion_levy,
                    'renovation_cost'=>$lokasi->renovation_cost,
                    'partnership_fee'=>$lokasi->partnership_fee,
                    'start_date'=>$lokasi->start_date,
                    'end_date'=>$lokasi->end_date,
                    'notes'=>$lokasi->notes,
               ]);
               $update_location = Location::where(array('id_location'=>$location->id_location))->update([
                    'name'=>$location->name,
                    'address'=>$location->address,
                    'mall'=>$location->mall,
                    'id_location'=>$location->id_location,
                    'id_city'=>$location->id_city,
                    'latitude'=>$location->latitude,
                    'longitude'=>$location->longitude,
                    'id_brand'=>$location->id_brand,
                    'location_large'=>$location->location_large,
                    'rental_price'=>$location->rental_price,
                    'service_charge'=>$location->service_charge,
                    'promotion_levy'=>$location->promotion_levy,
                    'renovation_cost'=>$location->renovation_cost,
                    'partnership_fee'=>$location->partnership_fee,
                    'start_date'=>$location->start_date,
                    'end_date'=>$location->end_date,
                    'notes'=>$location->notes,
                    'status'=>'Active',
               ]);
               $outlets = Outlet::where(array('id_outlet'=>$value['id_outlet']))->update([
                   'outlet_status'=>'Active',
                   'id_city'=>$location->id_city,
                   'outlet_name'=>$location->name,
                   'outlet_address'=>$location->outlet_address,
                   'outlet_latitude'=>$location->outlet_latitude,
                   'outlet_longitude'=>$location->outlet_longitude,
                   'outlet_postal_code'=>$location->outlet_postal_code,
               ]);
               $value['status'] = 'Success';
               $value->save();
           } 
            $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }
    //step 
    public function updateStatus(Request $request) {
        $outlet = OutletCloseTemporary::where(array('id_outlet_close_temporary'=>$request->id_outlet_close_temporary))->update([
                'status_steps'=>$request->status_steps,
                'status'=>$request->status
                ]);
        return response()->json(MyHelper::checkCreate($outlet));
    }
    public function updatelokasi(Request $request) {
       $data=[];
       if(isset($request->id_outlet_close_temporary_location)){
       if(isset($request->start_date)){
        $data['start_date'] = $request->start_date;
       }
       if(isset($request->end_date)){
        $data['end_date'] = $request->end_date;
       }
       if(isset($request->name)){
        $data['name'] = $request->name;
       }
       if(isset($request->address)){
        $data['address'] = $request->address;
       }
       if(isset($request->id_city)){
        $data['id_city'] = $request->id_city;
       }
       if(isset($request->id_brand)){
        $data['id_brand'] = $request->id_brand;
       }
       if(isset($request->location_large)){
        $data['location_large'] = $request->location_large;
       }
       if(isset($request->rental_price)){
        $data['rental_price'] = $request->rental_price;
       }
       if(isset($request->service_charge)){
        $data['service_charge'] = $request->service_charge;
       }
       if(isset($request->promotion_levy)){
        $data['promotion_levy'] = $request->promotion_levy;
       }
       if(isset($request->renovation_cost)){
        $data['renovation_cost'] = $request->renovation_cost;
       }
       if(isset($request->partnership_fee)){
        $data['partnership_fee'] = $request->partnership_fee;
       }
       if(isset($request->income)){
        $data['income'] = $request->income;
       }
       if(isset($request->mall)){
        $data['mall'] = $request->mall;
       }
       if(isset($request->notes)){
        $data['notes'] = $request->notes;
       }
        $outlet = OutletCloseTemporaryLocation::where(array('id_outlet_close_temporary_location'=>$request->id_outlet_close_temporary_location))->update($data);
        return response()->json(MyHelper::checkCreate($outlet));
       }
       return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function createFollowUp(Request $request) {
        $attachment = null;
            if(isset($request->attachment)){
                    $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFileActiveFollowUp, 'pdf');
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
        $outlet = OutletCloseTemporaryStep::create([
                'id_outlet_close_temporary'=>$request->id_outlet_close_temporary,
                'follow_up'=>$request->follow_up,
                'note'=>$request->note,
                'attachment'=>$attachment
                ]);
        return response()->json(MyHelper::checkCreate($outlet));
    }
    public function createFormSurvey(Request $request){
        $post = $request->all();
        if(isset($post['id_outlet_close_temporary']) && !empty($post['id_outlet_close_temporary'])){
            DB::beginTransaction();
            $data_store = [
                "id_outlet_close_temporary" => $post["id_outlet_close_temporary"],
                "survey" => $post["value"],
                "surveyor" => $post["surveyor"],
                "potential" => $post["potential"],
                "note" => $post["note"],
                "survey_date" => $post["date"],
            ];
            $store = OutletCloseTemporaryFormSurvey::create($data_store);
            if (!$store) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add form survey data']]);
            }
            DB::commit();
            $data_update = [
                'attachment' => $this->pdfSurvey($post["id_outlet_close_temporary"]),
            ];
            $update = OutletCloseTemporaryFormSurvey::where('id_outlet_close_temporary', $post['id_outlet_close_temporary'])->update($data_update);
            if(!$update){
                return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
            }
            else{
                return response()->json(['status' => 'success']);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function pdfSurvey($id){
        $form_survey = OutletCloseTemporaryFormSurvey::where('id_outlet_close_temporary', $id)->first();
        $outlet = Outlet::join('outlet_close_temporary','outlet_close_temporary.id_outlet','outlets.id_outlet')
                    ->where(array('outlet_close_temporary.id_outlet_close_temporary'=>$id))
                    ->first();
        $value = json_decode($form_survey['survey']??'' , true);
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;
        foreach($value as $v){
            foreach($v['value'] as $val){
                if($val['answer']=='a'){
                    $a = $a + 1;
                }elseif($val['answer']=='b'){
                    $b = $b + 1;
                }elseif($val['answer']=='c'){
                    $c = $c + 1;
                }elseif($val['answer']=='d'){
                    $d = $d + 1;
                }
            }
        }
        $alphas = range('A', 'Z');
        $total = ($a*4) + ($b*3) + ($c*2) + ($d*1);
        $location = Location::where('id_location', $outlet->id_location)->first();
        $brand = Brand::where('id_brand', $location['id_brand'])->first();
        $data = [
            'logo' => $brand['logo_brand'],
            'location' => $location['name'],
            'surveyor' => $form_survey['surveyor'],
            'brand' => $brand['name_brand'],
            'date' => $this->letterDate($form_survey['survey_date']),
            'abjad' => $alphas,
            'no_abjad' => 0,
            'no' => 1,
            'total_a' => $a,
            'total_b' => $b,
            'total_c' => $c,
            'total_d' => $d,
            'total' => $total,
            'note' => $form_survey['note'],
            'potential' => $form_survey['potential'],
            'value' => $value,
        ];
        // return view('businessdevelopment::form_survey', $data);
        $name = strtolower(str_replace(' ', '_', strtotime(date('Y-m-d H:i:s'))));
        $path = $this->form_survey.'form_survey_'.$name.'.pdf';
        $pdf = PDF::loadView('businessdevelopment::form_survey', $data );
        Storage::put($path, $pdf->output(),'public');
        return $path;
    }
    public function letterDate($date){
        $bulan = array (1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
        $pecah = explode('-', $date);
        return $date_latter = $pecah[2].' '.$bulan[intval($pecah[1])].' '.$pecah[0];
    }
       public function createConfirLetter(Request $request){
        $post = $request->all();
        if(isset($post['id_outlet_close_temporary']) && !empty($post['id_outlet_close_temporary'])){
            $cek_partner = OutletCloseTemporary::join('partners','partners.id_partner','outlet_close_temporary.id_partner')
                           ->where(array('outlet_close_temporary.id_outlet_close_temporary'=>$post['id_outlet_close_temporary']))
                           ->select('partners.*')
                           ->first();
            if($cek_partner){
                DB::beginTransaction();
                $creatConf = [
                    "id_outlet_close_temporary"   => $post['id_outlet_close_temporary'],
                    "no_letter"   => $post['no_letter'],
                    "location"   => $post['location'],
                    "date"   => date("Y-m-d"),
                ];
                $data['partner'] = $cek_partner;
                $data['letter'] = $creatConf;
                $data['location'] = OutletCloseTemporaryLocation::where(['id_outlet_close_temporary'=>$post['id_outlet_close_temporary']])->first();
                $data['city'] = City::where(['id_city'=>$data['location']['id_city']])->first();
                $waktu = $this->timeTotal(explode('-',  date('Y-m-d', strtotime($data['location']['start_date']))),explode('-',  date('Y-m-d', strtotime($data['location']['end_date']))));
                $send['data'] = [
                    'pihak_dua' => $this->pihakDua($data['partner']['name'],$data['partner']['gender']),
                    'ttd_pihak_dua' => $data['partner']['name'],
                    'lokasi_surat' => $data['letter']['location'],
                    'tanggal_surat' => $this->letterDate($data['letter']['date']),
                    'no_surat' => $data['letter']['no_letter'],
                    'location_mall' => strtoupper($data['location']['mall']),
                    'location_city' => strtoupper($data['city']['city_name']),
                    'address' => $data['location']['address'],
                    'large' => $data['location']['location_large'],
                    'partnership_fee' => $this->rupiah($data['location']['partnership_fee']),
                    'partnership_fee_string' => $this->stringNominal($data['location']['partnership_fee']).' Rupiah',
                    'dp' => $this->rupiah($data['location']['partnership_fee']*0.2),
                    'dp_string' => $this->stringNominal($data['location']['partnership_fee']*0.2).' Rupiah',
                    'dp2' => $this->rupiah($data['location']['partnership_fee']*0.3),
                    'dp2_string' => $this->stringNominal($data['location']['partnership_fee']*0.3).' Rupiah',
                    'final' => $this->rupiah($data['location']['partnership_fee']*0.5),
                    'final_string' => $this->stringNominal($data['location']['partnership_fee']*0.5).' Rupiah',
                    'sisa_waktu' => $waktu,
                ];
                 if(isset($data['location']['notes']) && !empty($data['location']['notes'])){
                    $send['data']['angsuran'] = $data['location']['notes'];
                }
                $content = Setting::where('key','confirmation_letter_tempalate')->get('value_text')->first()['value_text'];
                $pdf_contect['content'] = $this->textReplace($content,$send['data']);
                // return $pdf_contect['content'];
                $no = str_replace('/', '_', $post['no_letter']);
                $path = $this->confirmation.'confirmation_'.$no.'.pdf';
                $pdf = PDF::loadView('businessdevelopment::confirmation', $pdf_contect );
                Storage::put($path, $pdf->output(),'public');
                $creatConf['attachment'] = $path;
                $store = OutletCloseTemporaryConfirmationLetter::create($creatConf);
                if(!$store) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed create confirmation letter']]);
                }
            } else{
                return response()->json(['status' => 'fail', 'messages' => ['Id Partner not found']]);
            }
            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
            
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
     public function pihakDua($name, $gender){
        if($gender=='Man'){
            $gender_name = 'BAPAK';
        }elseif($gender=='Woman'){
            $gender_name = 'IBU';
        }
        return $pihakDua = $gender_name.' '.strtoupper($name);
    }
    public function rupiah($nominal){
        $rupiah = number_format($nominal ,0, ',' , '.' );
        return $rupiah.',-';
    }

    public function stringNominal($angka) {
        $bilangan = array('','Satu','Dua','Tiga','Empat','Lima','Enam','Tujuh','Delapan','Sembilan','Sepuluh','Sebelas');
        if ($angka < 12) {
            return $bilangan[$angka];
        } else if ($angka < 20) {
            return $bilangan[$angka - 10] . ' Belas';
        } else if ($angka < 100) {
            $hasil_bagi = ($angka / 10);
            $hasil_mod = $angka % 10;
            return trim(sprintf('%s Puluh %s', $bilangan[$hasil_bagi], $bilangan[$hasil_mod]));
        } else if ($angka < 200) {
            return sprintf('Seratus %s', $this->stringNominal($angka - 100));
        } else if ($angka < 1000) {
            $hasil_bagi = ($angka / 100);
            $hasil_mod = $angka % 100;
            return trim(sprintf('%s Ratus %s', $bilangan[$hasil_bagi], $this->stringNominal($hasil_mod)));
        } else if ($angka < 2000) {
            return trim(sprintf('Seribu %s', $this->stringNominal($angka - 1000)));
        } else if ($angka < 1000000) {
            $hasil_bagi = ($angka / 1000);
            $hasil_mod = $angka % 1000;
            return sprintf('%s Ribu %s', $this->stringNominal($hasil_bagi), $this->stringNominal($hasil_mod));
        } else if ($angka < 1000000000) {
            $hasil_bagi = ($angka / 1000000);
            $hasil_mod = $angka % 1000000;
            return trim(sprintf('%s Juta %s', $this->stringNominal($hasil_bagi), $this->stringNominal($hasil_mod)));
        } else if ($angka < 1000000000000) {
            $hasil_bagi = ($angka / 1000000000);
            $hasil_mod = fmod($angka, 1000000000);
            return trim(sprintf('%s Milyar %s', $this->stringNominal($hasil_bagi), $this->stringNominal($hasil_mod)));
        } else if ($angka < 1000000000000000) {
            $hasil_bagi = $angka / 1000000000000;
            $hasil_mod = fmod($angka, 1000000000000);
            return trim(sprintf('%s Triliun %s', $this->stringNominal($hasil_bagi), $this->stringNominal($hasil_mod)));
        } else {
            return 'Data Salah';
        }
    }
    public function timeTotal($start_date,$end_date){
        if($end_date[2]==$start_date[2] && $end_date[1]==$start_date[1]){
            $tahun = $end_date[0]-$start_date[0];
            $total_waktu = $tahun.' tahun';
        }elseif($end_date[1]==$start_date[1]){
            $selisih_tanggal = $end_date[2]-$start_date[2];
            if($start_date[1]==2){
                if($start_date[0]%4==0){
                    $jumlah_hari = 29;
                }else{
                    $jumlah_hari =28;
                }
            }elseif($start_date[1]==4 || $start_date[1]==6 || $start_date[1]==9 || $start_date[1]==11){
                $jumlah_hari = 30;
            }else{
                $jumlah_hari = 31;
            }
            if($selisih_tanggal>0){
                $tahun = $end_date[0]-$start_date[0];
                $tanggal = $end_date[2]-$start_date[2];
            }else{
                $awal = intval($start_date[2]);
                $akhir = intval($end_date[2]);
                $tahun = ($end_date[0]-$start_date[0])-1;
                $tanggal = ($jumlah_hari-$awal)+$akhir;
            }
            $total_waktu = $tahun.' tahun '.$tanggal.' hari';
        }elseif($end_date[2]==$start_date[2]){
            $selisih_bulan = $end_date[1]-$start_date[1];
            if($selisih_bulan>0){
                $tahun = $end_date[0]-$start_date[0];
                $bulan = $end_date[1]-$start_date[1];
            }else{
                $awal = intval($start_date[1]);
                $akhir = intval($end_date[1]);
                $tahun = ($end_date[0]-$start_date[0])-1;
                $bulan = (12-$awal)+$akhir;
            }
            $total_waktu = $tahun.' tahun '.$bulan.' bulan';
        }else{
            $selisih_bulan = $end_date[1]-$start_date[1];
            $selisih_tanggal = $end_date[2]-$start_date[2];
            if($start_date[1]==2){
                if($start_date[0]%4==0){
                    $jumlah_hari = 29;
                }else{
                    $jumlah_hari =28;
                }
            }elseif($start_date[1]==4 || $start_date[1]==6 || $start_date[1]==9 || $start_date[1]==11){
                $jumlah_hari = 30;
            }else{
                $jumlah_hari = 31;
            }
            if($selisih_tanggal>0){
                if($selisih_bulan>0){
                    $tahun = $end_date[0]-$start_date[0];
                    $bulan = $end_date[1]-$start_date[1];
                    $tanggal = $end_date[2]-$start_date[2];
                }else{
                    $awal = intval($start_date[1]);
                    $akhir = intval($end_date[1]);
                    $tahun = ($end_date[0]-$start_date[0])-1;
                    $bulan = (12-$awal)+$akhir;
                    $tanggal = $end_date[2]-$start_date[2];
                }
                $total_waktu = $tahun.' tahun '.$bulan.' bulan '.$tanggal.' hari';
            }else{
                if($selisih_bulan==1){
                    $tahun = $end_date[0]-$start_date[0];
                    $tanggal = ($jumlah_hari-$start_date[2])+$end_date[2];
                    $total_waktu = $tahun.' tahun '.$tanggal.' hari';
                }elseif($selisih_bulan>0){
                    $tahun = $end_date[0]-$start_date[0];
                    $bulan = $end_date[1]-$start_date[1];
                    $tanggal = ($jumlah_hari-$start_date[2])+$end_date[2];
                    $total_waktu = $tahun.' tahun '.$bulan.' bulan '.$tanggal.' hari';
                }else{
                    $awal = intval($start_date[1]);
                    $akhir = intval($end_date[1]);
                    $tahun = ($end_date[0]-$start_date[0])-1;
                    $bulan = (12-$awal)+$akhir;
                    $tanggal = ($jumlah_hari-$start_date[2])+$end_date[2];
                    $total_waktu = $tahun.' tahun '.$bulan.' bulan '.$tanggal.' hari';
                }
            }
            
        }
        return $total_waktu;
    }
    public function textReplace($text,$data){
        $text = str_replace('%lokasi_surat%',$data['lokasi_surat'],$text);
        $text = str_replace('%tanggal_surat%',$data['tanggal_surat'],$text);
        $text = str_replace('%no_surat%',$data['no_surat'],$text);
        $text = str_replace('%pihak_dua%',$data['pihak_dua'],$text);
        $text = str_replace('%location_mall%',$data['location_mall'],$text);
        $text = str_replace('%location_city%',$data['location_mall'],$text);
        $text = str_replace('%address%',$data['address'],$text);
        $text = str_replace('%large%',$data['large'],$text);
        $text = str_replace('%partnership_fee%',$data['partnership_fee'],$text);
        $text = str_replace('%partnership_fee_string%',$data['partnership_fee_string'],$text);
        $text = str_replace('%dp%',$data['dp'],$text);
        $text = str_replace('%dp_string%',$data['dp_string'],$text);
        $text = str_replace('%dp2%',$data['dp2'],$text);
        $text = str_replace('%dp2_string%',$data['dp2_string'],$text);
        $text = str_replace('%final%',$data['final'],$text);
        $text = str_replace('%final_string%',$data['final_string'],$text);
        $text = str_replace('%sisa_waktu%',$data['sisa_waktu'],$text);
        $text = str_replace('%ttd_pihak_dua%',$data['ttd_pihak_dua'],$text);
        
        return $text;
    }
    public function update_step_status() {
        $i = 0;
        $data = OutletCloseTemporaryStep::where(array('follow_up'=>""))->get();
        foreach ($data as $value) {
            $update = OutletCloseTemporaryStep::where(array('id_outlet_close_temporary_steps'=>$value['id_outlet_close_temporary_steps']))
                      ->update([
                          'follow_up'=>"Follow Up"
                      ]);
            if($update){
                $i++;
            }
        }
        return $i;
    }
    public function update_step_log() {
        $i = 0;
        $data = StepsLog::where(array('follow_up'=>""))->get();
        foreach ($data as $value) {
            $update = StepsLog::where(array('id_steps_log'=>$value['id_steps_log']))
                      ->update([
                          'follow_up'=>"Follow Up"
                      ]);
            if($update){
                $i++;
            }
        }
        return $i;
    }
}
