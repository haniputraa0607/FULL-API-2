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
use Modules\BusinessDevelopment\Entities\StepsLog;

class ApiPartnersController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/follow_up/";
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->all();
        if (isset($post['status']) && $post['status'] == 'Candidate') {
            $partner = Partner::with(['partner_bank_account','partner_locations','partner_step'])->where('status',$post['status'])->orWhere('status','Rejected');
        } elseif(isset($post['status']) && $post['status'] == 'Active') {
            $partner = Partner::with(['partner_bank_account','partner_locations','partner_step'])->where('status','Active')->orWhere('status','Inactive');
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
            $partner = Partner::where('id_partner', $post['id_partner'])->with(['partner_bank_account','partner_locations','partner_step'])->first();
            if(($partner['partner_step'])){
                foreach($partner['partner_step'] as $step){
                    if(isset($step['attachment']) && !empty($step['attachment'])){
                        $step['attachment'] = env('STORAGE_URL_API').'/'.$step['attachment'];
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
            $old_status = Partner::where('id_partner', $post['id_partner'])->get('status')[0]['status'];
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
                            $data_update['phone'],
                            [
                                'name' => $data_update['name'],
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
            $delete = $this->deleteLocations($id_partner);
        }
        $delete = Partner::where('id_partner', $id_partner)->delete();
        return MyHelper::checkDelete($delete);
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
        $post = $request->all();
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
            return response()->json(MyHelper::checkCreate($store));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
}
