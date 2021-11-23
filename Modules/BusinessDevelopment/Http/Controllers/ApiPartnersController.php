<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\PartnersLog;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Http\Controllers\ApiLocationsController;
use App\Lib\MyHelper;
use App\Lib\Icount;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Models\City;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use Illuminate\Support\Facades\App;
use Modules\Brand\Entities\Brand;
use PDF;
use Storage;
use Modules\BusinessDevelopment\Entities\StepsLog;
use Modules\BusinessDevelopment\Entities\ConfirmationLetter;
use Modules\BusinessDevelopment\Entities\FormSurvey;
use Modules\BusinessDevelopment\Entities\TermPayment;
use Modules\BusinessDevelopment\Entities\PartnersClosePermanent;
use Modules\BusinessDevelopment\Entities\PartnersClosePermanentDocument;
use Modules\BusinessDevelopment\Entities\PartnersClosePermanentOutlet;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporary;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporaryDocument;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporaryOutlet;
use Modules\BusinessDevelopment\Entities\PartnersBecomesIxobox;
use Modules\BusinessDevelopment\Entities\PartnersBecomesIxoboxDocument;
use Modules\BusinessDevelopment\Entities\PartnersBecomesIxoboxOutlet;
use Modules\Project\Entities\Project;
use App\Http\Models\Product;

use function GuzzleHttp\json_decode;

class ApiPartnersController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/follow_up/";
        $this->confirmation = "file/confirmation/";
        $this->form_survey = "file/form_survey/";
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->all();
        $partner = Partner::with(['partner_bank_account','partner_locations','partner_step']);
        if (isset($post['status']) && $post['status'] == 'Candidate') {
            $partner = Partner::with(['partner_bank_account','partner_locations','partner_step'])->where('status',$post['status']);
        } elseif(isset($post['status']) && $post['status'] == 'Active') {
            $partner = Partner::with(['partner_bank_account','partner_locations','partner_step'])->where('status','Active');
        } else {
            $partner = Partner::with(['partner_bank_account','partner_locations','partner_step']);
        }
        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){                
                        if($condition['operator'] == '='){
                            $partner = $partner->where($condition['subject'], $condition['parameter']);
                        }else{
                            $partner = $partner->where($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $partner = $partner->where(function ($q) use ($post){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){
                            if($condition['operator'] == '='){
                                $q->orWhere($condition['subject'], $condition['parameter']);
                            }else{
                                $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }
        if (isset($post['status']) && $post['status'] == 'Candidate') {
            $partner = $partner->orWhere('status','Rejected');
        } elseif(isset($post['status']) && $post['status'] == 'Active') {
            $partner = $partner->orWhere('status','Inactive');
        }
        if(isset($post['order']) && isset($post['order_type'])){
            if(isset($post['page'])){
                $partner = $partner->orderBy($post['order'], $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $partner = $partner->orderBy($post['order'], $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $partner = $partner->orderBy('created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $partner = $partner->orderBy('created_at', 'desc')->get()->toArray();
            }
        }
        return MyHelper::checkGet($partner);
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
        $post = $request->all();
        $data_request_partner = $post['partner'];
        if (!empty($data_request_partner)) {
            DB::beginTransaction();
            $store = Partner::create([
                "name"   => $data_request_partner['name'],
                "phone"   => $data_request_partner['phone'],
                "email"   => $data_request_partner['email'],
                "address"   => $data_request_partner['address'],
            ]);
            if ($store) {
                if (isset($post['location'])) {
                    $id = $store->id_partner;
                    foreach ($post['location'] as $key => $location) {
                        $store_loc = Location::create([
                            "name"   => $location['name'],
                            "address"   => $location['address'],
                            "id_city"   => $location['id_city'],
                            "latitude"   => $location['latitude'],
                            "longitude"   => $location['longitude'],
                            "id_partner"   => $id,
                        ]);
                        if(!$store_loc){
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add partner']]);
                        }
                    }
                }
            } else {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add partner']]);
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
        if(isset($post['id_partner']) && !empty($post['id_partner'])){
            $partner = Partner::where('id_partner', $post['id_partner'])->with(['partner_bank_account','partner_locations','partner_step','partner_confirmation','partner_survey'])->first();
            if(($partner['partner_step'])){
                foreach($partner['partner_step'] as $step){
                    if(isset($step['attachment']) && !empty($step['attachment'])){
                        $step['attachment'] = env('STORAGE_URL_API').$step['attachment'];
                    }
                }
            } 
            if(($partner['partner_confirmation'])){
                foreach($partner['partner_confirmation'] as $confir){
                    if(isset($confir['attachment']) && !empty($confir['attachment'])){
                        $confir['attachment'] = env('STORAGE_URL_API').$confir['attachment'];
                    }
                }
            } 
            if(($partner['partner_survey'])){
                foreach($partner['partner_survey'] as $survey){
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
            if($partner==null){
                return response()->json(['status' => 'success', 'result' => [
                    'partner' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'partner' => $partner,
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
        $post = $request->all();
        if (isset($post['id_partner']) && !empty($post['id_partner'])) {
            DB::beginTransaction();
            if (isset($post['name'])) {
                $data_update['name'] = $post['name'];
            }
            if (isset($post['code'])) {
                $data_update['code'] = $post['code'];
            }
            if (isset($post['mobile']) && $post['mobile'] == 'default') {
                $data_update['mobile'] = Partner::where('id_partner', $post['id_partner'])->get('phone')[0]['phone'];
            }elseif(isset($post['mobile'])){
                $data_update['mobile'] = $post['mobile'];
            }
            if (isset($post['contact_person'])) {
                $data_update['contact_person'] = $post['contact_person'];
            }
            if (isset($post['gender'])) {
                $data_update['gender'] = $post['gender'];
            }
            if (isset($post['phone'])) {
                $data_update['phone'] = $post['phone'];
            }
            if (isset($post['email'])) {
                $data_update['email'] = $post['email'];
            }
            if (isset($post['address'])) {
                $data_update['address'] = $post['address'];
            }
            if (isset($post['ownership_status'])) {
                $data_update['ownership_status'] = $post['ownership_status'];
            }
            if (isset($post['cooperation_scheme'])) {
                $data_update['cooperation_scheme'] = $post['cooperation_scheme'];
            }
            if (isset($post['id_bank_account'])) {
                $data_update['id_bank_account'] = $post['id_bank_account'];
            }
            if (isset($post['status'])) {
                $data_update['status'] = $post['status'];
            }
            if (isset($post['password'])) {
                $data_update['password'] = $post['password'];
            }
            if (isset($post['start_date'])) {
                $data_update['start_date'] = $post['start_date'];
            }
            if (isset($post['end_date'])) {
                $data_update['end_date'] = $post['end_date'];
            }
            if (isset($post['status_steps'])) {
                $data_update['status_steps'] = $post['status_steps'];
            }
            if (isset($post['npwp'])) {
                $data_update['npwp'] = $post['npwp'];
            }
            if (isset($post['npwp_name'])) {
                $data_update['npwp_name'] = $post['npwp_name'];
            }
            if (isset($post['npwp_address'])) {
                $data_update['npwp_address'] = $post['npwp_address'];
            }
            if (isset($post['id_term_payment'])) {
                $data_update['id_term_payment'] = $post['id_term_payment'];
            }
            if (isset($post['notes'])) {
                $data_update['notes'] = $post['notes'];
            }
            if (isset($post['trans_date'])) {
                $data_update['trans_date'] = $post['trans_date'];
            }
            if (isset($post['due_date'])) {
                $data_update['due_date'] = $post['due_date'];
            }
            if(isset($data_update['start_date']) && isset($data_update['end_date'])){
                $start = explode('-', $data_update['start_date']);
                $end = explode('-', $data_update['end_date']);
                try{
                    $waktu = $this->timeTotal($start,$end);
                }catch(\Exception $e) {
                    return response()->json(['status' => 'fail_date', 'messages' => ['Start Date and End Date must be at least 3 years apar']]);
                }
            }
            $old_status = Partner::where('id_partner', $post['id_partner'])->get('status')[0]['status'];
            $old_phone = Partner::where('id_partner', $post['id_partner'])->get('phone')[0]['phone'];
            $old_name = Partner::where('id_partner', $post['id_partner'])->get('name')[0]['name'];

            $update = Partner::where('id_partner', $post['id_partner'])->update($data_update);
            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update partner']]);
            }
            DB::commit();

            if (isset($data_update['status'])) {
                if($old_status=='Candidate' && $data_update['status'] == 'Active'){
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Updated Candidate Partner to Partner',
                            $old_phone,
                            [
                                'name' => $old_name,
                                'pin' => $post['pin'],
                            ], null, null, null, null, null, null, null, 1,
                        );
                        // return $autocrm;
                        if ($autocrm) {
                            return response()->json([
                                'status'    => 'success',
                                'messages'  => ['Approved sent to email partner']
                            ]);
                        } else {
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
                    }
                }
                if($old_status=='Candidate' && $data_update['status'] == 'Rejected'){
                    $reject_data = Partner::where('id_partner', $post['id_partner'])->get();
                    $phone_reject = $reject_data[0]["phone"];
                    $name_reject = $reject_data[0]["name"];
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Reject Candidate Partner',
                            $phone_reject,
                            [
                                'name' => $name_reject,
                            ], null, null, null, null, null, null, null, 1,
                        );
                        // return $autocrm;
                        if ($autocrm) {
                            return response()->json([
                                'status'    => 'success',
                                'messages'  => ['Rejected sent to email partner']
                            ]);
                        } else {
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
                    }
                }
            }
            if(isset($post['request']) && $post['request'] == 'approve'){
                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Approved request update data partner',
                        $data_update['phone'],
                        [
                            'name' => $data_update['name']
                        ], null, null, null, null, null, null, null, 1,
                    );
                    // return $autocrm;
                    if ($autocrm) {
                        return response()->json([
                            'status'    => 'success',
                            'messages'  => ['Approved request has been sent to email partner']
                        ]);
                    } else {
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Failed to send']
                        ]);
                    }
                }
            }
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
        $id_partner  = $request->json('id_partner');
        $partner = Partner::where('id_partner', $id_partner)->get();
        if($partner){
            $delete = $this->deleteClosePermanent($id_partner);
            $delete = $this->deleteCloseTemporary($id_partner);
            $delete = $this->deleteBecomeIxobox($id_partner);
            $delete = $this->deleteOutlet($id_partner);
            $delete = $this->deleteProject($id_partner);
            $delete = $this->deleteLocations($id_partner);
            $delete = $this->deleteConfir($id_partner);
            $delete = $this->deleteFormSurvey($id_partner);
        }
        if($delete){
            $delete = Partner::where('id_partner', $id_partner)->delete();
            return MyHelper::checkDelete($delete);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function deleteLocations($id_partner){
        $get = Location::where('id_partner', $id_partner)->first();
        if($get){
            $delete = Location::where('id_partner', $id_partner)->delete();
            $this->deleteLocations($id_partner);
            return $delete;
        }else{
            return true;
        }
    }

    public function deleteConfir($id){
        $get = ConfirmationLetter::where('id_partner', $id)->first();
        if($get){
            $delete = ConfirmationLetter::where('id_partner', $id)->delete();
            $this->deleteConfir($id);
            return $delete;
        }else{
            return true;
        }
    }

    public function deleteFormSurvey($id){
        $get = FormSurvey::where('id_partner', $id)->first();
        if($get){
            $delete = FormSurvey::where('id_partner', $id)->delete();
            $this->deleteFormSurvey($id);
            return $delete;
        }else{
            return true;
        }
    }

    public function deleteProject($id){
        $get = Project::where('id_partner', $id)->first();
        if($get){
            $delete = Project::where('id_partner', $id)->delete();
            $this->deleteProject($id);
            return $delete;
        }else{
            return true;
        }
    }

    public function deleteOutlet($id_partner){
        $get_code = Location::where('id_partner',$id_partner)->get('code')[0]['code'];
        $delete = $this->deleteOutletbyCode($get_code);
        return true;
    }

    public function deleteOutletbyCode($code){
        $get = Outlet::where('branch_code', $code)->first();
        if($get){
            $delete = Outlet::where('branch_code', $code)->delete();
            $this->deleteOutletbyCode($code);
            return $delete;
        }else{
            return true;
        }
    }

    public function deleteClosePermanent($id_partner){
        $permanent = PartnersClosePermanent::where('id_partner', $id_partner)->first();
        if($permanent){
            $delete = true;
            $id_permanent = $permanent['id_partners_close_permanent'];
            $delete = $this->deletePermanentDocument($id_permanent);
            $delete = $this->deletePermanentOutlet($id_permanent);
        }else{
            $delete = false;
        }
        if($delete){
            $delete = PartnersClosePermanent::where('id_partners_close_permanent', $id_permanent)->delete();
            $this->deleteClosePermanent($id_partner);
            return $delete;
        }else{
            return true;
        }
    }

    public function deletePermanentDocument($id){
        $get = PartnersClosePermanentDocument::where('id_partners_close_permanent', $id)->first();
        if($get){
            $delete = PartnersClosePermanentDocument::where('id_partners_close_permanent', $id)->delete();
            $this->deletePermanentDocument($id);
            return $delete;
        }else{
            return true;
        }
    }

    public function deletePermanentOutlet($id){
        $get = PartnersClosePermanentOutlet::where('id_partners_close_permanent', $id)->first();
        if($get){
            $delete = PartnersClosePermanentOutlet::where('id_partners_close_permanent', $id)->delete();
            $this->deletePermanentOutlet($id);
            return $delete;
        }else{
            return true;
        }
    }


    public function deleteBecomeIxobox($id_partner){
        $becomes = PartnersBecomesIxobox::where('id_partner', $id_partner)->first();
        if($becomes){
            $delete = true;
            $id_becomes = $becomes['id_partners_becomes_ixobox'];
            $delete = $this->deleteBecomeDocument($id_becomes);
            $delete = $this->deleteBecomeOutlet($id_becomes);
        }else{
            $delete = false;
        }
        if($delete){
            $delete = PartnersBecomesIxobox::where('id_partners_becomes_ixobox', $id_becomes)->delete();
            $this->deleteBecomeIxobox($id_partner);
            return $delete;
        }else{
            return true;
        }
    }

    public function deleteBecomeDocument($id){
        $get = PartnersBecomesIxoboxDocument::where('id_partners_becomes_ixobox', $id)->first();
        if($get){
            $delete = PartnersBecomesIxoboxDocument::where('id_partners_becomes_ixobox', $id)->delete();
            $this->deleteBecomeDocument($id);
            return $delete;
        }else{
            return true;
        }
    }

    public function deleteBecomeOutlet($id){
        $get = PartnersBecomesIxoboxOutlet::where('id_partners_becomes_ixobox', $id)->first();
        if($get){
            $delete = PartnersBecomesIxoboxOutlet::where('id_partners_becomes_ixobox', $id)->delete();
            $this->deleteBecomeOutlet($id);
            return $delete;
        }else{
            return true;
        }
    }

    public function deleteCloseTemporary($id_partner){
        $temporary = PartnersCloseTemporary::where('id_partner', $id_partner)->first();
        if($temporary){
            $delete = true;
            $id_temporary = $temporary['id_partners_close_temporary'];
            $delete = $this->deleteTemporaryDocument($id_temporary);
            $delete = $this->deleteTemporaryOutlet($id_temporary);
        }else{
            $delete = false;
        }
        if($delete){
            $delete = PartnersCloseTemporary::where('id_partners_close_temporary', $id_temporary)->delete();
            $this->deleteCloseTemporary($id_partner);
            return $delete;
        }else{
            return true;
        }
    }

    public function deleteTemporaryDocument($id){
        $get = PartnersCloseTemporaryDocument::where('id_partners_close_temporary', $id)->first();
        if($get){
            $delete = PartnersCloseTemporaryDocument::where('id_partners_close_temporary', $id)->delete();
            $this->deleteTemporaryDocument($id);
            return $delete;
        }else{
            return true;
        }
    }

    public function deleteTemporaryOutlet($id){
        $get = PartnersCloseTemporaryOutlet::where('id_partners_close_temporary', $id)->first();
        if($get){
            $delete = PartnersCloseTemporaryOutlet::where('id_partners_close_temporary', $id)->delete();
            $this->deleteTemporaryOutlet($id);
            return $delete;
        }else{
            return true;
        }
    }

    public function detailByPartner(){
        $user = Auth::user();
        $id_partner = $user['id_partner'];
        if(isset($id_partner) && !empty($id_partner)){
            if($user==null){
                return response()->json(['status' => 'success', 'result' => [
                    'partner' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'partner' => $user,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function updateByPartner(Request $request){
        $user = Auth::user();
        $id_partner = $user['id_partner'];
        $post = $request->all();
        if (!empty($post)) {
            $cek_partner = Partner::where(['id_partner'=>$id_partner])->first();
            if($cek_partner){
                DB::beginTransaction();
                $store = PartnersLog::create([
                    "id_partner" => $id_partner,
                    "update_name"   => $post['name'],
                    "update_phone"   => $post['phone'],
                    "update_email"   => $post['email'],
                    "update_address"   => $post['address'],
                ]);
                if(!$store) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed add partners log']]);
                }
            } else{
                return response()->json(['status' => 'fail', 'messages' => ['Id Partner not found']]);
            }
            DB::commit();
            if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Request update data partner',
                            $user['phone'],
                            [
                                'name' => $cek_partner['name']
                            ], null, null, null, null, null, null, null, 1,
                        );
                        // return $autocrm;
                        if ($autocrm) {
                            return response()->json([
                                'status'    => 'success',
                                'messages'  => ['Permintaan ubah data telah dikirim']
                            ]);
                        } else {
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Gagal mengirim permintaan']
                            ]);
                        }
                    }
            return response()->json(MyHelper::checkCreate($store));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }  
    }
    public function checkPassword(Request $request){
        $user = Auth::user();
        $id_partner = $user['id_partner'];
        $post = $request->all();
        if (isset($post['current_pin']) && !empty($post['current_pin'])) {
            $partner = Partner::where('id_partner',$id_partner)->get();
            $partner->makeVisible(['password']);
            if(Hash::check($post['current_pin'], $partner[0]['password'])){
                return response()->json(['status' => 'success', 'messages' => ['The password matched']]);
            }else{
                return response()->json(['status' => 'fail', 'messages' => ['The password does not match']]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
    public function passwordByPartner(Request $request){
        $user = Auth::user();
        $id_partner = $user['id_partner'];
        $post = $request->all();
        if (isset($id_partner) && !empty($id_partner)) {
            DB::beginTransaction();
            $data_update['password'] = $post['password'];
            $update = Partner::where('id_partner', $id_partner)->update($data_update);
            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update password partner']]);
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function listPartnersLogs(Request $request){
		$post = $request->all();
        $partners_log = PartnersLog::with(['original_data'])->join('partners', 'partners_logs.id_partner', '=', 'partners.id_partner')->select(['partners_logs.*', 'partners.name', 'partners.email']);
        
        if(isset($post['order']) && isset($post['order_type'])){
            if(isset($post['page'])){
                $partners_log = $partners_log->orderBy($post['order'], $post['order_type'])->paginate($request->length ?: 10);
                
            }else{
                $partners_log = $partners_log->orderBy($post['order'], $post['order_type'])->get()->toArray();
                
            }
        }else{
            if(isset($post['page'])){
                $partners_log = $partners_log->orderBy('created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $partners_log = $partners_log->orderBy('created_at', 'desc')->get()->toArray();
            }
        }
        return MyHelper::checkGet($partners_log);
	}
    public function deletePartnersLogs(Request $request)
    {
        $id_partners_log  = $request->json('id_partners_log');
        $delete = PartnersLog::where('id_partners_log', $id_partners_log)->delete();
        return MyHelper::checkDelete($delete);
    }

    public function detailPartnersLogs(Request $request){
        $post = $request->all();
        if(isset($post['id_partners_log']) && !empty($post['id_partners_log'])){
            $partners_log = PartnersLog::where('id_partners_log', $post['id_partners_log'])->with(['original_data'])->first();
            if($partners_log==null){
                return response()->json(['status' => 'success', 'result' => [
                    'partners_log' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'partners_log' => $partners_log,
                ]]);
            }
            
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function statusPartner(){
        $user = Auth::user();
        $id_partner = $user['id_partner'];
        $partner = Partner::with(['partner_bank_account','partner_locations','partner_step'])->where('id_partner',$id_partner)->get();
        if(isset($partner) && !empty($partner)){
            if($partner==null){
                return response()->json(['status' => 'success', 'result' => [
                    'partner' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'partner' => $partner[0],
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
    
    public function followUp(Request $request)
    {
        $post = $request['post_follow_up'];
        if(isset($post['id_partner']) && !empty($post['id_partner'])){
            DB::beginTransaction();
            $data_store = [
                "id_partner" => $post["id_partner"],
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
            $store = StepsLog::create($data_store);
            if (!$store) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add follow up data']]);
            }
            DB::commit();
            if(isset($request['form_survey']) && !empty($request['form_survey'])){
                $survey =  $this->createFormSurvey($request['form_survey']);
                if($survey['status'] != 'success' && isset($survey['status'])){
                    return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                }
            }
            if(isset($post['follow_up']) && $post['follow_up'] == 'Payment'){
                $data_send = [
                    "partner" => Partner::where('id_partner',$post["id_partner"])->first(),
                    "location" => Location::where('id_partner',$post["id_partner"])->first(),
                    "confir" => ConfirmationLetter::where('id_partner',$post["id_partner"])->first(),
                ];
                $initBranch = Icount::ApiConfirmationLetter($data_send);
                if($initBranch['response']['Status']=='1' && $initBranch['response']['Message']=='success'){
                    $data_init = $initBranch['response']['Data'][0];
                    $partner_init = [
                        "id_business_partner" => $data_init['BusinessPartner']['BusinessPartnerID'],
                        "id_company" => $data_init['BusinessPartner']['CompanyID'],
                        "id_sales_order" => $data_init['SalesOrderID'],
                        "voucher_no" => $data_init['VoucherNo'],
                        "id_sales_order_detail" => $data_init['Detail'][0]['SalesOrderDetailID'],
                    ];
                    $location_init = [
                        "id_branch" => $data_init['Branch']['BranchID'],
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
                        $update_location_init = Location::where('id_partner', $post['id_partner'])->update($location_init);
                        if(!$update_location_init){
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                        }
                        DB::commit();
                        $data_send_2 = [
                            "partner" => Partner::where('id_partner',$post["id_partner"])->first(),
                            "location" => Location::where('id_partner',$post["id_partner"])->first(),
                            "confir" => ConfirmationLetter::where('id_partner',$post["id_partner"])->first(),
                        ];
                        $invoiceCL = Icount::ApiInvoiceConfirmationLetter($data_send_2);
                        if($invoiceCL['response']['Status']=='1' && $invoiceCL['response']['Message']=='success'){
                            $data_invoCL = $invoiceCL['response']['Data'][0];
                            $val = Location::where('id_partner',$post["id_partner"])->get('value_detail')[0]['value_detail'];
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
                                $update_location_invoCL = Location::where('id_partner', $post['id_partner'])->update($location_invoCL);
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
    
    public function createConfirLetter($request){
        $post = $request;
        if(isset($post['id_partner']) && !empty($post['id_partner'])){
            $cek_partner = Partner::where(['id_partner'=>$post['id_partner']])->first();
            if($cek_partner){
                DB::beginTransaction();
                $creatConf = [
                    "id_partner"   => $post['id_partner'],
                    "no_letter"   => $post['no_letter'],
                    "location"   => $post['location'],
                    "date"   => date("Y-m-d"),
                ];
                $data['partner'] = $cek_partner;
                $data['letter'] = $creatConf;
                $data['location'] = Location::where(['id_partner'=>$post['id_partner']])->first();
                $data['city'] = City::where(['id_city'=>$data['location']['id_city']])->first();
                $waktu = $this->timeTotal(explode('-', $data['partner']['start_date']),explode('-', $data['partner']['end_date']));
                $send['data'] = [
                    'pihak_dua' => $this->pihakDua($data['partner']['contact_person'],$data['partner']['gender']),
                    'ttd_pihak_dua' => $data['partner']['contact_person'],
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
                    'total_waktu' => $waktu['total'],
                    'sisa_waktu' => $waktu['sisa'],
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
                $store = ConfirmationLetter::create($creatConf);
                if(!$store) {
                    DB::rollback();
                    return ['status' => 'fail', 'messages' => ['Failed create confirmation letter']];
                }
            } else{
                return ['status' => 'fail', 'messages' => ['Id Partner not found']];
            }
            DB::commit();
            return MyHelper::checkCreate($store);
            
        }else{
            return ['status' => 'fail', 'messages' => ['Incompleted Data']];
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
    public function letterDate($date){
        $bulan = array (1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
        $pecah = explode('-', $date);
        return $date_latter = $pecah[2].' '.$bulan[intval($pecah[1])].' '.$pecah[0];
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
            $string_tahun = strtolower($this->stringNominal($tahun));
            $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun';
            $array_waktu = [
                0 => $tahun,
            ];
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
            $string_tahun = strtolower($this->stringNominal($tahun));
            $string_tanggal = strtolower($this->stringNominal($tanggal));
            $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$tanggal.' ('.$string_tanggal.')'.' hari';
            $array_waktu = [
                0 => $tahun,
                2 => $tanggal,
            ];
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
            $string_tahun = strtolower($this->stringNominal($tahun));
            $string_bulan = strtolower($this->stringNominal($bulan));
            $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$bulan.' ('.$string_bulan.')'.' bulan';
            $array_waktu = [
                0 => $tahun,
                1 => $bulan,
            ];
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
                $string_tahun = strtolower($this->stringNominal($tahun));
                $string_bulan = strtolower($this->stringNominal($bulan));
                $string_tanggal = strtolower($this->stringNominal($tanggal));
                $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$bulan.' ('.$string_bulan.')'.' bulan '.$tanggal.' ('.$string_tanggal.')'.' hari';
                $array_waktu = [
                    0 => $tahun,
                    1 => $bulan,
                    2 => $tanggal,
                ];
            }else{
                if($selisih_bulan==1){
                    $tahun = $end_date[0]-$start_date[0];
                    $tanggal = ($jumlah_hari-$start_date[2])+$end_date[2];
                    $string_tahun = strtolower($this->stringNominal($tahun));
                    $string_tanggal = strtolower($this->stringNominal($tanggal));
                    $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$tanggal.' ('.$string_tanggal.')'.' hari';
                    $array_waktu = [
                        0 => $tahun,
                        2 => $tanggal,
                    ];
                }elseif($selisih_bulan>0){
                    $tahun = $end_date[0]-$start_date[0];
                    $bulan = $end_date[1]-$start_date[1];
                    $tanggal = ($jumlah_hari-$start_date[2])+$end_date[2];
                    $string_tahun = strtolower($this->stringNominal($tahun));
                    $string_bulan = strtolower($this->stringNominal($bulan));
                    $string_tanggal = strtolower($this->stringNominal($tanggal));
                    $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$bulan.' ('.$string_bulan.')'.' bulan '.$tanggal.' ('.$string_tanggal.')'.' hari';
                    $array_waktu = [
                        0 => $tahun,
                        1 => $bulan,
                        2 => $tanggal,
                    ];
                }else{
                    $awal = intval($start_date[1]);
                    $akhir = intval($end_date[1]);
                    $tahun = ($end_date[0]-$start_date[0])-1;
                    $bulan = (12-$awal)+$akhir;
                    $tanggal = ($jumlah_hari-$start_date[2])+$end_date[2];
                    $string_tahun = strtolower($this->stringNominal($tahun));
                    $string_bulan = strtolower($this->stringNominal($bulan));
                    $string_tanggal = strtolower($this->stringNominal($tanggal));
                    $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$bulan.' ('.$string_bulan.')'.' bulan '.$tanggal.' ('.$string_tanggal.')'.' hari';
                    $array_waktu = [
                        0 => $tahun,
                        1 => $bulan,
                        2 => $tanggal,
                    ];
                }
            }
            
        }
        $sisa = $array_waktu[0] - 3;
        if($sisa==0){
            if(isset($array_waktu[1]) && isset($array_waktu[2])){
                $string_sisa = ' + '.$array_waktu[1].' ('.strtolower($this->stringNominal($array_waktu[1])).')'.' bulan '.$array_waktu[2].' ('.strtolower($this->stringNominal($array_waktu[2])).')'.' hari berikutnya;';
            }elseif(isset($array_waktu[1])){
                $string_sisa = ' + '.$array_waktu[1].' ('.strtolower($this->stringNominal($array_waktu[1])).')'.' bulan berikutnya;';
            }elseif(isset($array_waktu[2])){
                $string_sisa = ' + '.$array_waktu[2].' ('.strtolower($this->stringNominal($array_waktu[2])).')'.' hari berikutnya;';
            }else{
                $string_sisa = ';';
            }
        }else{
            if(isset($array_waktu[1]) && isset($array_waktu[2])){
                $string_sisa = ' + '.$sisa.' ('.strtolower($this->stringNominal($sisa)).')'.' tahun '.$array_waktu[1].' ('.strtolower($this->stringNominal($array_waktu[1])).')'.' bulan '.$array_waktu[2].' ('.strtolower($this->stringNominal($array_waktu[2])).')'.' hari berikutnya;';
            }elseif(isset($array_waktu[1])){
                $string_sisa = ' + '.$sisa.' ('.strtolower($this->stringNominal($sisa)).')'.' tahun '.$array_waktu[1].' ('.strtolower($this->stringNominal($array_waktu[1])).')'.' bulan berikutnya;';
            }elseif(isset($array_waktu[2])){
                $string_sisa = ' + '.$sisa.' ('.strtolower($this->stringNominal($sisa)).')'.' tahun '.$array_waktu[2].' ('.strtolower($this->stringNominal($array_waktu[2])).')'.' hari berikutnya;';
            }else{
                $string_sisa = ' + '.$sisa.' ('.strtolower($this->stringNominal($sisa)).')'.' tahun;';
            }
        }
        return [
            'total' => $total_waktu,
            'sisa' =>$string_sisa
        ];
    }

    public function textReplace($text,$data){
        $text = str_replace('%lokasi_surat%',$data['lokasi_surat'],$text);
        $text = str_replace('%tanggal_surat%',$data['tanggal_surat'],$text);
        $text = str_replace('%no_surat%',$data['no_surat'],$text);
        $text = str_replace('%pihak_dua%',$data['pihak_dua'],$text);
        $text = str_replace('%location_mall%',$data['location_mall'],$text);
        $text = str_replace('%location_city%',$data['location_city'],$text);
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
        $text = str_replace('%total_waktu%',$data['total_waktu'],$text);
        $text = str_replace('%sisa_waktu%',$data['sisa_waktu'],$text);
        $text = str_replace('%ttd_pihak_dua%',$data['ttd_pihak_dua'],$text);
        if(isset($data['angsuran'])){
            $angsuran = '<li>'.$data['angsuran'].';</li>';
            $text = str_replace('%angsuran%',$angsuran,$text);
        }else{
            $text = str_replace('%angsuran%','',$text);
        }
        return $text;
    }

    public function formSurvey(Request $request){
        $form = Setting::where('key', 'form_survey')->first();
        $form = json_decode($form['value_text']??'' , true);
        return $form[$request['id_brand']]??[];
    }

    public function allFormSurvey(Request $request){
        $form = Setting::where('key', 'form_survey')->first();
        $form = json_decode($form['value_text']??'' , true);
        return $form;
    }

    public function createFormSurvey($request){
        $post = $request;
        if(isset($post['id_partner']) && !empty($post['id_partner'])){
            DB::beginTransaction();
            $data_store = [
                "id_partner" => $post["id_partner"],
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
                'attachment' => $this->pdfSurvey($post["id_partner"]),
            ];
            $update = FormSurvey::where('id_partner', $post['id_partner'])->update($data_update);
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

    public function pdfSurvey($id){
        $form_survey = FormSurvey::where('id_partner', $id)->first();
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
        $location = Location::where('id_partner', $id)->first();
        $brand = Brand::where('id_brand', $location['id_brand'])->first();
        $partner = Partner::where('id_partner', $id)->first();
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
        $name = strtolower(str_replace(' ', '_', $partner['name']));
        $path = $this->form_survey.'form_survey_'.$name.'.pdf';
        $pdf = PDF::loadView('businessdevelopment::form_survey', $data );
        Storage::put($path, $pdf->output(),'public');
        return $path;
    }

    public function listFormSurvey(){
        $form = Setting::where('key', 'form_survey')->first();
        $form = json_decode($form['value_text']??'' , true);
        return MyHelper::checkGet($form);
    }

    public function storeFormSurvey(Request $request){
        $post = $request->all();
        if (isset($post['key']) && !empty($post['key'])) {
            DB::beginTransaction();
            $data_update = [
                "value_text" => $post["value_text"],
            ];
            $update = Setting::where('key', $post['key'])->update($data_update);
            if (!$update) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add form survey data']]);
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }

    }

    public function term(Request $request){
        $post = $request->json()->all();
        $term = TermPayment::select('id_term_of_payment', 'name', 'duration')->get()->toArray();
        return response()->json(MyHelper::checkGet($term));
    }
}

