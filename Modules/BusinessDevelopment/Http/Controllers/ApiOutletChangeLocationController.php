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
use App\Http\Models\Outlet;
use Modules\BusinessDevelopment\Entities\OutletChangeLocation;
use Modules\BusinessDevelopment\Entities\OutletChangeLocationConfirmationLetter;
use Modules\BusinessDevelopment\Entities\OutletChangeLocationFormSurvey;
use Modules\BusinessDevelopment\Entities\OutletChangeLocationSteps;
use Modules\BusinessDevelopment\Entities\OutletManage;
use Modules\BusinessDevelopment\Http\Requests\OutletClose\CreateOutletChangeLocationRequest;
use Modules\BusinessDevelopment\Http\Requests\OutletClose\UpdateOutletChangeLocationRequest;
use Modules\BusinessDevelopment\Entities\LegalAgreement;
use App\Lib\Icount;
use Modules\Project\Http\Controllers\ApiProjectController;

class ApiOutletChangeLocationController extends Controller
{
     public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/outlet_change_location/follow_up/";
        $this->confirmation = "file/outlet_change_location/confirmation/";
        $this->form_survey = "file/outlet_change_location/form_survey/";
         $this->legal_agreement = "file/legal_agreement/";
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
    public function create(CreateOutletChangeLocationRequest $request){
        $manage = OutletManage::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_outlet"    =>  $request->id_outlet,
                    "type"         =>  "Change Location",
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
        ]);
        $outlet = Outlet::where(array('id_outlet'=>$request->id_outlet))->first();
         $store = OutletChangeLocation::create([
                    "id_partner"   =>  $request->id_partner,
                    "id_location"  =>  $outlet->id_location,
                    "id_outlet"    =>  $request->id_outlet,
                    "date"         =>  date_format(date_create($request->date),"Y-m-d H:i:s"),
                    "id_outlet_manage"=>$manage->id_outlet_manage
                ]);   
        
            return response()->json(MyHelper::checkCreate($store));
    }
    public function update(UpdateOutletChangeLocationRequest $request){
         $store = OutletChangeLocation::where(array('id_change_location'=>$request->id_change_location))->update([
                    "date"      =>  date_format(date_create($request->date),"Y-m-d H:i:s")
                ]);
            return response()->json(MyHelper::checkCreate($store));
    }
    public function indexClose(Request $request){
         $store = OutletChangeLocation::where(array('outlet_close_temporary.id_outlet'=>$request->id_outlet))->orderby('created_at','desc')->get();
         $outlet = Outlet::where('id_outlet',$request->id_outlet)->join('cities','cities.id_city','outlets.id_city')->join('locations','locations.id_location','outlets.id_location')->first();
         return response()->json(['status' => 'success','result'=>array(
             'outlet'=>$outlet,
             'list'=>$store
         )]);
    }
    public function detail(Request $request){
         $store = OutletChangeLocation::where(array('id_outlet_manage'=>$request->id_outlet_manage))
                 ->join('outlets','outlets.id_outlet','outlet_change_location.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_location','outlets.id_location')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->select('outlet_change_location.*','outlets.*','locations.id_location','locations.id_brand','partners.gender','partners.name as name_partner')
                 ->with(['steps','first_location','confirmation'])
                 ->first();
         if($store){
                $store['penomoran_cl'] = $this->no_cl();
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'fail','message'=>"Data Not Found"]);
    }
    public function no_cl(){
        $confirmation = ConfirmationLetter::orderby('created_at','desc')->first();
        if($confirmation){
            $explode = explode('/', $confirmation->no_letter);
            $number = $explode[3]??0;
            $s = 1;
            for ($x = 0; $x < $s; $x++) {
                $number++;
                if($number < 10){
                 $no = "CL/".date('y').'/'.date('m').'/0'.$number;
                }else{
                  $no = "CL/".date('y').'/'.date('m').'/'.$number;
                }
                $cek = ConfirmationLetter::where('no_letter',$no)->first();
                if($cek){
                    $s++;
                }
            }
        }else{
            $no = "CL/".date('y').'/'.date('m').'/01';
        }
        return $no;
    }
    public function reject(Request $request){
         $store = OutletChangeLocation::where(array('id_outlet_change_location'=>$request->id_outlet_change_location))->first();
         if($store){
             $manage = OutletManage::where(array('id_outlet_manage'=>$store->id_outlet_manage))->update([
         'status'=>"Reject"
         ]);
             $store = OutletChangeLocation::where(array('id_outlet_change_location'=>$request->id_outlet_change_location))->first();
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
    public function success(Request $request){
         $store = OutletChangeLocation::where(array('id_outlet_change_location'=>$request->id_outlet_change_location))->first();
         if($store){
             $manage = OutletManage::where(array('id_outlet_manage'=>$store->id_outlet_manage))->update([
         'status'=>"Success"
         ]);
             $store = OutletChangeLocation::where(array('id_outlet_change_location'=>$request->id_outlet_change_location))->update([
         'status'=>"Success"
         ]);
              return response()->json(['status' => 'success','result'=>$store]);
         }
           return response()->json(['status' => 'success','message'=>"Data Not Found"]);
    }
  
    public function cron(){
        $log = MyHelper::logCron('Cron Outlet Change Location');
        try {
        $outlet = OutletChangeLocation::join('outlets','outlets.id_outlet','outlet_change_location.id_outlet')
                   ->join('locations','locations.id_location','outlets.id_location')
                   ->where(array(
                       'outlets.outlet_status'=>'Active',
                       'locations.status'=>'Active',
                   ))
                   ->wheredate('date','<=',date('Y-m-d'))->get();
        foreach ($outlet as $value) {
            Location::join('outlets','outlets.id_location','locations.id_location')
                        ->where('outlets.id_outlet',$value['id_outlet'])
                        ->update(['locations.status'=>'Close','outlets.outlet_status'=>'Inactive']);
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
        $outlet = OutletChangeLocation::where(array('id_outlet_change_location'=>$request->id_outlet_change_location))->first();
        if($outlet){
            if(isset($request->status_steps)){
                $outlet->status_steps = $request->status_steps;
            }
            if(isset($request->status)){
                $outlet->status = $request->status;
            }
            if(isset($request->id_location)){
                $outlet->to_id_location = $request->id_location;
            }
            $outlet->save();
        }
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
        $outlet = OutletChangeLocationLocation::where(array('id_outlet_close_temporary_location'=>$request->id_outlet_close_temporary_location))->update($data);
        return response()->json(MyHelper::checkCreate($outlet));
       }
       return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function createFollowUp(Request $request) {
        $post = $request->post_follow_up;
        if($post){
            if(isset($post['follow_up']) && $post['follow_up'] == 'Payment'){
                $data_send = [
                    "partner" => Partner::where('id_partner',$post["id_partner"])->first(),
                    "location" => Location::where('id_partner',$post["id_partner"])->where('id_location',$post["id_location"])->first(),
                    "confir" => ConfirmationLetter::where('id_partner',$post["id_partner"])->first(),
                ];
                $initBranch = Icount::ApiInitBranch($data_send, $data_send['location']['company_type']);
                if($initBranch['response']['Status']=='1' && $initBranch['response']['Message']=='success'){
                    $data_init = $initBranch['response']['Data'][0];
                    $partner_init = [
                        "id_business_partner" => $data_init['Branch']['OutletPartnerID'],
                        "id_company" => $data_init['BusinessPartner']['CompanyID'],
                        "id_sales_order" => $data_init['SalesOrderID'],
                        "voucher_no" => $data_init['VoucherNo'],
                        "id_sales_order_detail" => $data_init['Detail'][0]['SalesOrderDetailID'],
                    ];
                    $location_init = [
                        "id_branch" => $data_init['Branch']['BranchID'],
                        "id_chart_account" => $data_init['Branch']['ChartOfAccountID'],
                    ];
                    $value_detail[$data_init['Detail'][0]['Name']] = [
                        "name" => $data_init['Detail'][0]['Name'],
                        "amount" => $data_init['Amount'],
                        "tax_value" => $data_init['TaxValue'],
                        "netto" => $data_init['Netto'],
                    ];
                    $location_init['value_detail'] = json_encode($value_detail);
                    $update_partner_init = Partner::where('id_partner', $post['id_partner'])->update($partner_init);
                    if($update_partner_init){
                        $update_location_init = Location::where('id_partner', $post['id_partner'])->where('id_location',$post["id_location"])->update($location_init);
                        if(!$update_location_init){
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                        }
                        $data_send_2 = [
                            "partner" => Partner::where('id_partner',$post["id_partner"])->first(),
                            "location" => Location::where('id_partner',$post["id_partner"])->where('id_location',$post["id_location"])->first(),
                            "confir" =>  ConfirmationLetter::where('id_partner',$post["id_partner"])->first(),
                        ];
                        $invoiceCL = Icount::ApiInvoiceConfirmationLetter($data_send_2, 'PT IMA');
                        if($invoiceCL['response']['Status']=='1' && $invoiceCL['response']['Message']=='success'){
                            $data_invoCL = $invoiceCL['response']['Data'][0];
                            $val = Location::where('id_partner',$post["id_partner"])->where('id_location',$post["id_location"])->get('value_detail')[0]['value_detail'];
                            $val = json_decode($val, true);
                            $val[$data_invoCL['Detail'][0]['Name']] = [
                                "name" => $data_invoCL['Detail'][0]['Name'],
                                "amount" => $data_invoCL['Amount'],
                                "tax_value" => $data_invoCL['TaxValue'],
                                "netto" => $data_invoCL['Netto'],
                            ];
                            $location_invoCL['value_detail'] = json_encode($val);
                            $partner_invoCL = [
                                "id_sales_invoice" => $data_invoCL['SalesInvoiceID'],
                                "id_sales_invoice_detail" => $data_invoCL['Detail'][0]['SalesInvoiceDetailID'],
                                "id_delivery_order_detail" => $data_invoCL['Detail'][0]['DeliveryOrderDetailID'],
                            ];
                            $update_partner_invoCL = Partner::where('id_partner', $post['id_partner'])->update($partner_invoCL);
                            if($update_partner_invoCL){
                                $update_location_invoCL = Location::where('id_partner', $post['id_partner'])->where('id_location',$post["id_location"])->update($location_invoCL);
                                if(!$update_location_invoCL){
                                    DB::rollback();
                                    return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                                }
                            }
                            $init = new ApiProjectController();
                            $outlet = $init->initProject($data_send['partner'], $data_send['location']);
                            $outletChangeLocation = OutletChangeLocation::where(array('id_outlet_change_location'=>$post["id_outlet_change_location"]))->first();
                            $outlet_manage = OutletManage::where('id_outlet_manage',$outletChangeLocation->id_outlet_manage)->update([
                                'status'=>'Success'
                            ]);
                            
                            $outletChangeLocation = OutletChangeLocation::where(array('id_outlet_change_location'=>$post["id_outlet_change_location"]))->update([
                                'status'=>'Success',
                                'to_id_outlet'=>$outlet['result']['outlet']['id_outlet']
                            ]);
                            //make legal agreement
                            $legal_agree = $this->createLegalAgreement($data_send['partner'], $data_send['location']);
                            if(!$legal_agree){
                                DB::rollback();
                                return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                            }
                        }else{
                            return response()->json(['status' => 'fail', 'messages' => [$invoiceCL['response']['Message']]]);
                        }
                    }else{
                        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                    }
                }else{
                    return response()->json(['status' => 'fail', 'messages' => [$initBranch['response']['Message']]]);
                }
                
            DB::commit();
            }
        $attachment = null;
            if(isset($request->attachment)){
                    $upload = MyHelper::uploadFile($post->file('attachment'), $this->saveFileActiveFollowUp, 'pdf');
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
        $outlet = OutletChangeLocationSteps::create([
                'id_outlet_change_location'=>$post['id_outlet_change_location'],
                'follow_up'=>$post['follow_up'],
                'note'=>$post['note'],
                'attachment'=>$attachment
                ]);
        return response()->json(MyHelper::checkCreate($outlet));
        }
         return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function createFormSurvey(Request $request){
        $post = $request->all();
        return $post;
        if(isset($post['id_outlet_change_location']) && !empty($post['id_outlet_change_location'])){
            DB::beginTransaction();
            $data_store = [
                "id_outlet_change_location" => $post["id_outlet_change_location"],
                "survey" => $post["value"],
                "surveyor" => $post["surveyor"],
                "potential" => $post["potential"],
                "note" => $post["note"],
                "survey_date" => $post["date"],
            ];
            $store = OutletChangeLocationFormSurvey::create($data_store);
            if (!$store) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add form survey data']]);
            }
            DB::commit();
            $data_update = [
                'attachment' => $this->pdfSurvey($post["id_outlet_close_temporary"]),
            ];
            $update = OutletChangeLocationFormSurvey::where('id_outlet_close_temporary', $post['id_outlet_close_temporary'])->update($data_update);
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
        $form_survey = OutletChangeLocationFormSurvey::where('id_outlet_close_temporary', $id)->first();
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
      
        if(isset($post['id_outlet_change_location']) && !empty($post['id_outlet_change_location'])){
            $cek_partner = OutletChangeLocation::join('partners','partners.id_partner','outlet_change_location.id_partner')
                           ->where(array('outlet_change_location.id_outlet_change_location'=>$post['id_outlet_change_location']))
                           ->select('partners.*','outlet_change_location.to_id_location')
                           ->first();
         
            if($cek_partner){
                DB::beginTransaction();
                $creatConf = [
                    "id_outlet_change_location"   => $post['id_outlet_change_location'],
                    "no_letter"   => $post['no_letter'],
                    "location"   => $post['location'],
                    "date"   => date("Y-m-d"),
                ];
                   
                $data['partner'] = $cek_partner;
                $data['letter'] = $creatConf;
                $data['location'] = Location::where(array('id_location'=>$cek_partner->to_id_location))->first();
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
                
                $no = str_replace('/', '_', $post['no_letter']);
                $path = $this->confirmation.'confirmation_'.$no.'.pdf'; 
                $pdf = PDF::loadView('businessdevelopment::confirmation', $pdf_contect );
                Storage::put($path, $pdf->output(),'public');
                $creatConf['attachment'] = $path;
                $store = OutletChangeLocationConfirmationLetter::create($creatConf);
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
        $data = OutletChangeLocationStep::where(array('follow_up'=>""))->get();
        foreach ($data as $value) {
            $update = OutletChangeLocationStep::where(array('id_outlet_close_temporary_steps'=>$value['id_outlet_close_temporary_steps']))
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
    
     public function createLegalAgreement($partner,$location){
        
        $data_legal = [
            'id_partner' => $partner['id_partner'],
            'id_location' => $location['id_location'],
            'no_letter' => '123',
            'date_letter' => date('Y-m-d'),
            'attachment' => $this->legal_agreement.'tes.pdf',
        ];

        $send = LegalAgreement::create($data_legal);
        if(!$send){
            return false;
        }
        return true;
    }
}
