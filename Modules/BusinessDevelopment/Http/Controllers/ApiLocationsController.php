<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Location;
use App\Lib\MyHelper;
use DB;
use Modules\Brand\Entities\Brand;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\StepLocationsLog;
use Modules\BusinessDevelopment\Http\Controllers\ApiPartnersController;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\ConfirmationLetter;
use App\Lib\Icount;
use Modules\BusinessDevelopment\Entities\FormSurvey;
use Modules\BusinessDevelopment\Entities\LocationOutletStarterBundlingProduct;
use Modules\BusinessDevelopment\Entities\OutletStarterBundlingProduct;
use PDF;
use Storage;
use Image;
use Modules\BusinessDevelopment\Entities\NewStepsLog;
use Modules\BusinessDevelopment\Entities\StepsLog;
use Modules\BusinessDevelopment\Http\Requests\LandingPage\StoreNewLocation;
use App\Http\Models\Setting;

class ApiLocationsController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/follow_up/";
        $this->form_survey = "file/form_survey/";
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->all();
        if (isset($post['status']) && $post['status'] == 'Candidate') {
            $locations = Location::with(['location_partner','location_city','location_step'])->where(function($query){$query->where('status', 'Candidate');});
        }elseif(isset($post['status']) && $post['status'] == 'Active'){
            $locations = Location::with(['location_partner','location_city','location_step'])->where(function($query){$query->where('status', 'Active')->orWhere('status', 'Inactive');});
        }else {
            $locations = Location::with(['location_partner','location_city','location_step']);
        }
        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){
                        if($condition['subject']=='name_partner'){
                            if ($condition['operator'] == '=') {
                                $id_partner = Partner::where('name', $condition['parameter'])->get('id_partner');
                                if(count($id_partner)>0){
                                    $locations = $locations->where('id_partner', $id_partner[0]['id_partner']);
                                }else{
                                    $locations = $locations->where('id_partner', '0');
                                }
                            } else{
                                $id_partner = Partner::where('name','like','%'.$condition['parameter'].'%')->get('id_partner');
                                if(count($id_partner)>0){
                                    $locations = $locations->where('id_partner', $id_partner[0]['id_partner']);
                                }else{
                                    $locations = $locations->where('id_partner', '0');
                                }
                            }   
                        }else{
                            if($condition['operator'] == '='){
                                $locations = $locations->where($condition['subject'], $condition['parameter']);
                            }else{
                                $locations = $locations->where($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                            }
                        }                
                    }
                }
            }else{
                $locations = $locations->where(function ($q) use ($post){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){
                            if($condition['subject']=='name_partner'){
                                if ($condition['operator'] == '=') {
                                    $id_partner = Partner::where('name', $condition['parameter'])->get('id_partner');
                                    if(count($id_partner)>0){
                                        $q->orWhere('id_partner', $id_partner[0]['id_partner']);
                                    }else{
                                        $q->orWhere('id_partner', '0');
                                    }
                                } else{
                                    $id_partner = Partner::where('name','like','%'.$condition['parameter'].'%')->get('id_partner');
                                    if(count($id_partner)>0){
                                        $q->orWhere('id_partner', $id_partner[0]['id_partner']);
                                    }else{
                                        $q->orWhere('id_partner', '0');
                                    }
                                }     
                            }else{
                                if($condition['operator'] == '='){
                                    $q->orWhere($condition['subject'], $condition['parameter']);
                                }else{
                                    $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                }
                            }
                        }
                    }
                });
            }
        }
        if(isset($post['order']) && isset($post['order_type'])){
            if(isset($post['page'])){
                $locations = $locations->orderBy($post['order'], $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $locations = $locations->orderBy($post['order'], $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $locations = $locations->orderBy('created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $locations = $locations->orderBy('created_at', 'desc')->get()->toArray();
            }
        } 
        return MyHelper::checkGet($locations);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('businessdevelopment::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post= $request->all();
        $data_request= $post['location'];
        if (!empty($data_request)) {
            $cek_partner = Partner::where(['id_partner'=>$data_request['id_partner']])->first();
            if($cek_partner){
                DB::beginTransaction();
                $store = Location::create([
                    "name"   => $data_request['name'],
                    "address"   => $data_request['address'],
                    "id_city"   => $data_request['id_city'],
                    "latitude"   => $data_request['latitude'],
                    "longitude"   => $data_request['longitude'],
                    "id_partner"   => $data_request['id_partner'],
                ]);
                if(!$store) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed add location']]);
                }
            } else{
                return response()->json(['status' => 'fail', 'messages' => ['Id Partner not found']]);
            }
            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }           
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
    public function edit(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_location']) && !empty($post['id_location'])){
            $location = Location::where('id_location', $post['id_location'])->with(['location_partner','location_city.province','location_step','location_survey','location_confirmation','location_starter.product','location_init'])->first();
            if(($location['location_step'])){
                foreach($location['location_step'] as $step){
                    if(isset($step['attachment']) && !empty($step['attachment'])){
                        $step['attachment'] = env('STORAGE_URL_API').$step['attachment'];
                    }
                }
            } 
            if(($location['location_survey'])){
                foreach($location['location_survey'] as $survey){
                    if(isset($survey['attachment']) && !empty($survey['attachment'])){
                        $survey['attachment'] = env('STORAGE_URL_API').$survey['attachment'];
                    }
                    if($survey['potential']==1){
                        $survey['potential'] = 'OK';
                    }else{
                        $survey['potential'] = 'Not OK';
                    }
                }
            } 
            if(($location['location_confirmation'])){
                foreach($location['location_confirmation'] as $confir){
                    if(isset($confir['attachment']) && !empty($confir['attachment'])){
                        $confir['attachment'] = env('STORAGE_URL_API').$confir['attachment'];
                    }
                }
            } 
            if(isset($location['value_detail']) && !empty($location['value_detail'])){
                $location['value_detail_decode'] = json_decode($location['value_detail']??'' , true);
            }
            if($location==null){
                return response()->json(['status' => 'success', 'result' => [
                    'location' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'location' => $location,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        if(isset($request['update_data_location'])){
            $post = $request['update_data_location'];
        }else{
            $post = $request->all();
        }
        if (isset($post['id_location']) && !empty($post['id_location'])) {
            DB::beginTransaction();
            $data_update = [];
            if (isset($post['id_location'])) {
                $data_update['id_location'] = $post['id_location'];
            }
            if (isset($post['name'])) {
                $data_update['name'] = $post['name'];
            }
            if (isset($post['code'])) {
                $cek_code = Location::where('code', $post['code'])->where('id_location','<>',$post['id_location'])->first();
                if($cek_code){
                    return response()->json(['status' => 'duplicate_code', 'messages' => ['Location code must be different']]);
                }else{
                    $data_update['code'] = $post['code'];
                }
            }
            if (isset($post['address'])) {
                $data_update['address'] = $post['address'];
            }
            if (isset($post['status'])) {
                $data_update['status'] = $post['status'];
            }
            if (isset($post['id_city'])) {
                $data_update['id_city'] = $post['id_city'];
            }
            if (isset($post['latitude'])) {
                $data_update['latitude'] = $post['latitude'];
            }
            if (isset($post['longitude'])) {
                $data_update['longitude'] = $post['longitude'];
            }
            if (isset($post['pic_name'])) {
                $data_update['pic_name'] = $post['pic_name'];
            }
            if (isset($post['pic_contact'])) {
                $data_update['pic_contact'] = $post['pic_contact'];
            }
            if (isset($post['id_partner'])) {
                $data_update['id_partner'] = $post['id_partner'];
            }
            if (isset($post['width'])) {
                $data_update['width'] = $post['width'];
            }
            if (isset($post['height'])) {
                $data_update['height'] = $post['height'];
            }
            if (isset($post['length'])) {
                $data_update['length'] = $post['length'];
            }
            if (isset($post['location_large'])) {
                $data_update['location_large'] = $post['location_large'];
            }
            if (isset($post['rental_price'])) {
                $data_update['rental_price'] = $post['rental_price'];
            }
            if (isset($post['service_charge'])) {
                $data_update['service_charge'] = $post['service_charge'];
            }
            if (isset($post['promotion_levy'])) {
                $data_update['promotion_levy'] = $post['promotion_levy'];
            }
            if (isset($post['renovation_cost'])) {
                $data_update['renovation_cost'] = $post['renovation_cost'];
            }
            if (isset($post['partnership_fee'])) {
                $data_update['partnership_fee'] = $post['partnership_fee'];
            }
            if (isset($post['income'])) {
                $data_update['income'] = $post['income'];
            }
            if (isset($post['id_brand'])) {
                $data_update['id_brand'] = $post['id_brand'];
            }
            if (isset($post['mall'])) {
                $data_update['mall'] = $post['mall'];
            }
            if (isset($post['notes'])) {
                $data_update['notes'] = $post['notes'];
            }
            if (isset($post['total_payment'])) {
                $data_update['total_payment'] = $post['total_payment'];
            }
            if (isset($post['step_loc'])) {
                $data_update['step_loc'] = $post['step_loc'];
            }
            if (isset($post['trans_date'])) {
                $data_update['trans_date'] = $post['trans_date'];
            }
            if (isset($post['due_date'])) {
                $data_update['due_date'] = $post['due_date'];
            }
            if (isset($post['is_tax'])) {
                $data_update['is_tax'] = $post['is_tax'];
            }
            if (isset($post['no_loi'])) {
                $data_update['no_loi'] = $post['no_loi'];
            }
            if (isset($post['date_loi'])) {
                $data_update['date_loi'] = $post['date_loi'];
            }
            if (isset($post['no_spk'])) {
                $data_update['no_spk'] = $post['no_spk'];
            }
            if (isset($post['date_spk'])) {
                $data_update['date_spk'] = $post['date_spk'];
            }
            if (isset($post['total_box'])) {
                $data_update['total_box'] = $post['total_box'];
            }
            if (isset($post['handover_date'])) {
                $data_update['handover_date'] = $post['handover_date'];
            }

            if (isset($post['id_outlet_starter_bundling'])) {
                $data_update['id_outlet_starter_bundling'] = $post['id_outlet_starter_bundling'];

                $starter = OutletStarterBundlingProduct::where('id_outlet_starter_bundling', $post['id_outlet_starter_bundling'])->get()->toArray();
                $starter = array_map(function ($value) use ($post) {
                    return [
                        'id_location'   => $post['id_location'],
                        'id_product_icount'  => $value['id_product_icount'],
                        'unit'  => $value['unit'],
                        'qty'  => $value['qty'],
                        'filter'  => $value['filter'],
                        'budget_code'  => $value['budget_code'],
                    ];
                }, $starter);

                $product_start = $this->addLocationProductStarter($starter);
                if(!$product_start){
                    return response()->json(['status' => 'fail', 'messages' => ['Failed to save product starter outlet']]);
                }

                if(empty($post['start_date']) && empty($post['end_date'])){
                    $id_loc_start =  Location::select('id_partner')->where('id_location',$post['id_location'])->first()['id_partner'];
                    $date = Partner::select('start_date')->where('id_partner',$id_loc_start)->first();
                    $data_update['start_date'] = $date['start_date'];
                    $data_update['end_date'] = $date['end_date'];
                }
            }

            if (isset($post['start_date'])) {
                $data_update['start_date'] = $post['start_date'];
            }
            if (isset($post['end_date'])) {
                $data_update['end_date'] = $post['end_date'];
            }
            if (isset($post['ownership_status'])) {
                $data_update['ownership_status'] = $post['ownership_status'];
            }
            if (isset($post['company_type'])) {
                $data_update['company_type'] = $post['company_type'];
            }
            if (isset($post['cooperation_scheme'])) {
                $data_update['cooperation_scheme'] = $post['cooperation_scheme'];
            }
            if (isset($post['id_term_of_payment'])) {
                $data_update['id_term_of_payment'] = $post['id_term_of_payment'];
            }
            if (isset($post['sharing_value'])) {
                $data_update['sharing_value'] = $post['sharing_value'];
            }
            if (isset($post['sharing_percent'])) {
                $data_update['sharing_percent'] = $post['sharing_percent'];
            }
            if (isset($post['from']) && $post['from'] == 'Select Location') {
                $year = date('y');
                $month = date('m');
                //confir letter
                $yearMonthSPK = 'CL/'.$year.'/'.$month.'/';
                $cl = ConfirmationLetter::where('no_letter','like', $yearMonthSPK.'%')->count() + 1;
                if($cl < 10){
                    $cl = '0'.$cl;
                }
                $no_cl = 'CL/'.$year.'/'.$month.'/'.$cl;
                $creatConf = [
                    "id_partner"   => $post['id_partner'],
                    "id_location"   => $post['id_location'],
                    "no_letter"   => $no_cl,
                    "location"   => '1',
                    "date"   => date("Y-m-d"),
                    "path" => '1'
                ];
                $cek_cl = ConfirmationLetter::where('id_partner',$post['id_partner'])->where('id_location',$post['id_location'])->first();
                if(!$cek_cl){
                    $store = ConfirmationLetter::create($creatConf);
                }

                //spk
                $yearMonth = 'SPK/'.$year.'/'.$month.'/';
                $no_spk = Location::where('no_spk','like', $yearMonth.'%')->count() + 1;
                if($no_spk < 10 ){
                    $no_spk = '0'.$no_spk;
                }
                $no_spk = $yearMonth.$no_spk;
                $data_update['no_spk'] = $no_spk;
                if (empty($post['start_date'])) {
                    $data_update['start_date'] = Partner::where('id_partner', $post['id_partner'])->first()['start_date'];
                }
                if (empty($post['end_date'])) {
                    $data_update['end_date'] = Partner::where('id_partner', $post['id_partner'])->first()['end_date'];
                }
            }
            $old_data = Location::where('id_location', $post['id_location'])->first();
            $update = Location::where('id_location', $post['id_location'])->update($data_update);
            $new_data = Location::where('id_location', $post['id_location'])->first();
            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update location']]);
            }
            if(isset($request['data_confir']) && !empty($request['data_confir'])){
                $confir = new ApiPartnersController;
                $confir_letter = $confir->createConfirLetter($request['data_confir']);
                if($confir_letter['status'] != 'success' && isset($confir_letter['status'])){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                }
            }
            if (isset($data_update['status'])) {
                if($old_data['status']=='Candidate' && $data_update['status'] == 'Active'){
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Updated Candidate Location to Location',
                            $new_data['email'],
                            [
                                'name_location' => $new_data['name'],
                                'code' => $new_data['code'],
                                'pic_contact' => $old_data['pic_contact'],
                                'approved_date' => date('Y-m-d')
                            ], null, null, null, null, null, null, null, 1,
                        );
                        // return $autocrm;
                        if (!$autocrm) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $id_location  = $request->json('id_location');
        $location = Location::where('id_location', $id_location)->get();
        if($location){
            $delete = $this->deleteProject($id_location);
        }
        if($delete){
            $delete = Location::where('id_location', $id_location)->delete();
            return MyHelper::checkDelete($delete);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function deleteProject($id){
        $get = Project::where('id_location', $id)->first();
        if($get){
            $delete = Project::where('id_location', $id)->delete();
            $this->deleteProject($id);
            return $delete;
        }else{
            return true;
        }
    }
    
    public function brandsList(Request $request)
    {
        $post = $request->json()->all();
        $brands = Brand::select('id_brand', 'name_brand', 'code_brand')->get()->toArray();
        return response()->json(MyHelper::checkGet($brands));
    }

    public function followUp(Request $request)
    {
        $post = $request['post_follow_up'];
        if (isset($post['id_location']) && !empty($post['id_location'])) {
            DB::beginTransaction();
            $data_store = [
                "id_location" => $post["id_location"],
                "follow_up" => $post["follow_up"],
                "note" => $post["note"],
            ];
            if (isset($post['attachment']) && !empty($post['attachment'])) {
                $upload = MyHelper::uploadFile($post['attachment'], $this->saveFile, 'pdf');
                if (isset($upload['status']) && $upload['status'] == "success") {
                    $data_store['attachment'] = $upload['path'];
                } else {
                    $result = [
                        'error'    => 1,
                        'status'   => 'fail',
                        'messages' => ['fail upload file']
                    ];
                    return $result;
                }
            }
            $store = StepLocationsLog::create($data_store);
            if (!$store) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add follow up data']]);
            }
            DB::commit();
            if(isset($request['form_survey']) && !empty($request['form_survey'])){
                $survey = $this->createFormSurvey($request['form_survey']);
                if($survey['status'] != 'success' && isset($survey['status'])){
                    return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                }
            }
            if(isset($post['follow_up']) && $post['follow_up'] == 'Payment'){
                $data_send = [
                    "partner" => Partner::where('id_partner',$post["id_partner"])->first(),
                    "location" => Location::where('id_partner',$post["id_partner"])->where('id_location',$post["id_location"])->first(),
                    "confir" => ConfirmationLetter::where('id_partner',$post["id_partner"])->first(),
                ];
                $initBranch = Icount::ApiConfirmationLetter($data_send);
                if($initBranch['response']['Status']=='1' && $initBranch['response']['Message']=='success'){
                    $data_init = $initBranch['response']['Data'][0];
                    $partner_init = [
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
                    DB::beginTransaction();
                    $update_partner_init = Partner::where('id_partner', $post['id_partner'])->update($partner_init);
                    if($update_partner_init){
                        $update_location_init = Location::where('id_partner', $post['id_partner'])->where('id_location',$post["id_location"])->update($location_init);
                        if(!$update_location_init){
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                        }
                        DB::commit();
                        $data_send_2 = [
                            "partner" => Partner::where('id_partner',$post["id_partner"])->first(),
                            "location" => Location::where('id_partner',$post["id_partner"])->where('id_location',$post["id_location"])->first(),
                            "confir" => ConfirmationLetter::where('id_partner',$post["id_partner"])->first(),
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
                            DB::beginTransaction();
                            $update_partner_invoCL = Partner::where('id_partner', $post['id_partner'])->update($partner_invoCL);
                            if($update_partner_invoCL){
                                $update_location_invoCL = Location::where('id_partner', $post['id_partner'])->where('id_location',$post["id_location"])->update($location_invoCL);
                                if(!$update_location_invoCL){
                                    DB::rollback();
                                    return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                                }
                                DB::commit();
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
            }
            return response()->json(MyHelper::checkCreate($store));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
       }
    }

    public function createFormSurvey($request){
        $post = $request;
        if(isset($post['id_location']) && !empty($post['id_location'])){
            DB::beginTransaction();
            $data_store = [
                "id_location" => $post["id_location"],
                "title" => $post["title"],
                "survey" => $post["value"],
                "surveyor" => $post["surveyor"],
                "potential" => $post["potential"],
                "note" => $post["note"],
                "survey_date" => $post["date"],
            ];
            $store = FormSurvey::create($data_store);
            if (!$store) {
                DB::rollback();
                return ['status' => 'fail', 'messages' => ['Failed add form survey data']];
            }
            DB::commit();
            $data_update = [
                'attachment' => $this->pdfSurvey($post["id_location"]),
            ];
            $update = FormSurvey::where('id_location', $post['id_location'])->update($data_update);
            if(!$update){
                return ['status' => 'fail', 'messages' => ['Incompleted Data']];
            }
            else{
                return ['status' => 'success'];
            }
        }else{
            return ['status' => 'fail', 'messages' => ['Incompleted Data']];
        }
    }

    public function pdfSurvey($id_location){
        $form_survey = FormSurvey::where('id_location',$id_location)->first();
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
        $location = Location::where('id_location',$id_location)->first();
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
        // $name = strtolower(str_replace(' ', '_', $partner['name']));
        $name_loc = strtolower(str_replace(' ', '_', $location['name']));
        $path = $this->form_survey.'form_survey_'.$name_loc.'.pdf';
        $pdf = PDF::loadView('businessdevelopment::form_survey', $data );
        Storage::put($path, $pdf->output(),'public');
        return $path;
    }
    public function letterDate($date){
        $bulan = array (1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
        $pecah = explode('-', $date);
        return $date_latter = $pecah[2].' '.$bulan[intval($pecah[1])].' '.$pecah[0];
    }

    public function storeLandingPage(StoreNewLocation $request)
    {
        $post= $request->all();
        $data_request= $post;
        if (!empty($data_request)) {
            DB::beginTransaction();

            $checkPhoneFormat = MyHelper::phoneCheckFormat($data_request['pic_contact']);
            if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
                return response()->json([
                    'status' => 'fail',
                    'messages' => 'Invalid number PIC contact format'
                ]);
            } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
                $data_request['pic_contact'] = $checkPhoneFormat['phone'];
            }

            $loc_code = $this->locationCode();

            $data_loc = [
                "name"   => $data_request['name'],
                "email"   => $data_request['email'],
                "address"   => $data_request['address'],
                "id_city"   => $data_request['id_city'],
                "latitude"   => $data_request['latitude'],
                "longitude"   => $data_request['longitude'],
                "width"   => $data_request['width'],
                "length"   => $data_request['length'],
                "location_large"   => $data_request['location_large'],
                "location_type"   => $data_request['location_type'],
                "pic_name"   => $data_request['pic_name'],
                "pic_contact"   => $data_request['pic_contact'],
                "location_notes"   => $data_request['notes'],
                "code" => $loc_code
            ];

            if (isset($post['location_image']) && !empty($post['location_image'])) {
                $img = Image::make(base64_decode($post['location_image']));
                $imgwidth = $img->width();
                $imgheight = $img->height();
                $upload = MyHelper::uploadPhotoStrict($post['location_image'], 'img/location/', $imgwidth, $imgheight, time());
                if ($upload['status'] == "success") {
                    $data_loc['location_image'] = $upload['path'];
                }
            }

            if (isset($post['submited_by']) && !empty($post['submited_by'])) {
                $data_loc['submited_by'] = $post['submited_by'];
            }

            $store = Location::create($data_loc);
            if(!$store) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add location']]);
            }
            if (\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Create A New Candidate Location',
                    $data_loc['email'],
                    [
                        'name_location' => $data_loc['name'],
                        'code' => $data_loc['code'],
                        'pic_contact' => $data_loc['pic_contact']
                    ], null, null, null, null, null, null, null, 1,
                );
                // return $autocrm;
                if (!$autocrm) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Failed to send']
                    ]);
                }
            }
            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }           
    }

    public function locationCode(){
        $year = date('y');
        $month = date('m');
        $yearMonth = 'LOC'.$year.$month;
        $no = Location::where('code','like', $yearMonth.'%')->count() + 1;
        if($no < 10 ){
            $no = '000'.$no;
        }elseif($no < 100 && $no >= 10){
            $no = '00'.$no;
        }elseif($no < 1000 && $no >= 100){
            $no = '0'.$no;
        }
        $no = $yearMonth.$no;
        return $no;
    }

    public function addLocationProductStarter($data){

        $data_product = [];

        foreach ($data as $value) {
            array_push($data_product, [
                'id_location' 	=> $value['id_location'],
                'id_product_icount'  => $value['id_product_icount'],
                'unit'  => $value['unit'],
                'qty'  => $value['qty'],
                'filter'  => $value['filter'],
                'budget_code'  => $value['budget_code'],
            ]);
        }

        if (!empty($data_product)) {
            $save = LocationOutletStarterBundlingProduct::insert($data_product);
            return $save;
        } else {
            return false;
        }

        return true;
    }

    public function newStatusLogs(Request $request){
        $post = $request->all();
        $data = NewStepsLog::where('id_partner',$post['id_partner'])->where('id_location',$post['id_location'])->get()->toArray();
        if(isset($data) && !empty($data)){
            return [
                "status" => "success",
                "result" => $data
            ];
        }else{
            $data = StepsLog::where('id_partner',$post['id_partner'])->get()->toArray();
            return [
                "status" => "success",
                "result" => $data
            ];
        }
        
    }

    public function settingUpdate(Request $request){
        $post = $request->all();

        $update['before'] = Setting::updateOrCreate(['key' => $post['key'].'_before'],['value_text' => $post['value_before']]);
        $update['after'] = Setting::updateOrCreate(['key' => $post['key'].'_after'],['value_text' => $post['value_after']]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function valueBeforeAfter(Request $request, $key){
        $post = $request->all();
        
        if($key=='partner'){
            $key_setting_before = 'setting_partner_content_before';
            $key_setting_after = 'setting_partner_content_after';
        }elseif($key=='location'){
            $key_setting_before = 'setting_locations_content_before';
            $key_setting_after = 'setting_locations_content_after';
        }elseif($key=='hairstylist'){
            $key_setting_before = 'setting_hairstylist_content_before';
            $key_setting_after = 'setting_hairstylist_content_after';
        }
        
        $before = Setting::where('key', $key_setting_before)->first();
        if($before){
            $data['before']['id_setting'] = $before['id_setting'];
            $data['before']['value_text'] = $before['value_text'];
        }else{
            $data['before'] = null;
        }
        $after = Setting::where('key', $key_setting_after)->first();
        if($after){
            $data['after']['id_setting'] = $after['id_setting'];
            $data['after']['value_text'] = $after['value_text'];
        }else{
            $data['after'] = null;
        }
        return MyHelper::checkGet($data);
    }

}
