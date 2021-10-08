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

class ApiLocationsController extends Controller
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
            $locations = Location::with(['location_partner','location_city'])->where('status',$post['status']);
        }elseif(isset($post['status']) && $post['status'] == 'Active'){
            $locations = Location::with(['location_partner','location_city'])->where('status','Active')->orWhere('status','Inactive');
        }else {
            $locations = Location::with(['location_partner','location_city']);
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
            $location = Location::where('id_location', $post['id_location'])->with(['location_partner','location_city'])->first();

            return response()->json(['status' => 'success', 'result' => [
                'location' => $location,
            ]]);
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
        if (isset($post['id_location']) && !empty($post['id_location'])) {
            DB::beginTransaction();
            if (isset($post['name'])) {
                $data_update['name'] = $post['name'];
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
            if (isset($post['start_date'])) {
                $data_update['start_date'] = $post['start_date'];
            }
            if (isset($post['end_date'])) {
                $data_update['end_date'] = $post['end_date'];
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
            $old_status = Location::where('id_location', $post['id_location'])->get('status')[0]['status'];
            $update = Location::where('id_location', $post['id_location'])->update($data_update);
            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update location']]);
            }
            DB::commit();
            $new_id_partner = Location::where('id_location', $post['id_location'])->get('id_partner')[0]['id_partner'];
            $partner = Partner::where('id_partner',$new_id_partner)->get()[0];
            if(isset($data_update['status'])){
                if($old_status=='Candidate' && $data_update['status'] == 'Active'){
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Updated Candidate Location to Location',
                            $partner['phone'],
                            [
                                'name' => $partner['name'],
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
        $id_location  = $request->json('id_location');
        $delete = Location::where('id_location', $id_location)->delete();
        return MyHelper::checkDelete($delete);
    }


    public function brandsList(Request $request)
    {
        $post = $request->json()->all();
        $brands = Brand::select('id_brand', 'name_brand', 'code_brand')->get()->toArray();
        return response()->json(MyHelper::checkGet($brands));
    }


}
