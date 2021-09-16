<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\Location;
use App\Lib\MyHelper;
use DB;

class ApiPartnersController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->all();
        if (isset($post['status']) && $post['status'] == 'Candidate') {
            $partner = Partner::with(['partner_bank_account','partner_locations'])->where('status',$post['status']);
        } elseif(isset($post['status']) && $post['status'] == 'Active') {
            $partner = Partner::with(['partner_bank_account','partner_locations'])->where('status','Active')->orWhere('status','Inactive');
        } else {
            $partner = Partner::with(['partner_bank_account','partner_locations']);
        }
        if ($keyword = ($request->search['value']??false)) {
            $partner->where('name', 'like', '%'.$keyword.'%')
                        ->orWhereHas('partner_bank_account', function($q) use ($keyword) {
                                $q->where('beneficiary_name', 'like', '%'.$keyword.'%');
                            })
                        ->orWhereHas('partner_locations', function($q) use ($keyword) {
                            $q->where('name', 'like', '%'.$keyword.'%');
                        });
        }
        if(isset($post['get_child']) && $post['get_child'] == 1){
            $partner = $partner->whereNotNull('id_bank_account');
        }
        if(isset($post['page'])){
            $partner = $partner->orderBy('updated_at', 'desc')->paginate($request->length ?: 10);
        }else{
            $partner = $partner->orderBy('updated_at', 'desc')->get()->toArray();
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
            $partner = Partner::where('id_partner', $post['id_partner'])->with(['partner_bank_account','partner_locations'])->first();
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
            $old_status = Partner::where('id_partner', $post['id_partner'])->get('status')[0]['status'];
            $update = Partner::where('id_partner', $post['id_partner'])->update($data_update);
            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update product variant']]);
            }
            DB::commit();
            if($old_status=='Candidate' && $data_update['status'] == 'Active'){
                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Updated Candidate Partner to Partner',
                        $data_update['phone'],
                        [
                            'name' => $data_update['name'],
                            'pin' => $post['pin'],
                        ]
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
}
