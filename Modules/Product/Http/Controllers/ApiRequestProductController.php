<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\RequestProduct;
use Modules\Product\Entities\RequestProductDetail;
use App\Lib\MyHelper;
use DB;
use Modules\Product\Entities\DeliveryProduct;
use Modules\Product\Entities\DeliveryProductDetail;
use Modules\Product\Entities\DeliveryRequestProduct;

class ApiRequestProductController extends Controller
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
    }

    public function index(Request $request)
    {
        $post = $request->all();

        $request_product = RequestProduct::join('users','users.id','=','request_products.id_user_request')
                                         ->join('outlets','outlets.id_outlet','=','request_products.id_outlet')
                                         ->select(
                                            'request_products.*',
                                            'outlets.outlet_name',
                                            'users.name',
                                         );

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){                
                        if($condition['operator'] == '='){
                            $request_product = $request_product->where($condition['subject'], $condition['parameter']);
                        }else{
                            $request_product = $request_product->where($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $request_product = $request_product->where(function ($q) use ($post){
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
                $request_product = $request_product->orderBy($post['order'], $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $request_product = $request_product->orderBy($post['order'], $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $request_product = $request_product->orderBy('created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $request_product = $request_product->orderBy('created_at', 'desc')->get()->toArray();
            }
        }
        return MyHelper::checkGet($request_product);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Request $request)
    {
        $post = $request->all();
        if (!empty($post)) {
            if (isset($post['id_outlet'])) {
                $store_request['id_outlet'] = $post['id_outlet'];
            }
            if (isset($post['type'])) {
                $store_request['type'] = $post['type'];
            }
            if (isset($post['requirement_date'])) {
                $store_request['requirement_date'] = date('Y-m-d', strtotime($post['requirement_date']));
            }
            if (isset($post['note_request'])) {
                $store_request['note_request'] = $post['note_request'];
            }
            $store_request['id_user_request'] = auth()->user()->id;
            $store_request['code'] = $this->codeGenerate();
            $cek_outlet = Outlet::where(['id_outlet'=>$store_request['id_outlet']])->first();
            if ($cek_outlet) {
                DB::beginTransaction();
                $store = RequestProduct::create($store_request); 
                if($store) {
                    if (isset($post['product_icount'])) {
                        $save_detail = $this->saveDetail($store, $post['product_icount']);
                        if (!$save_detail) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add request hair stylist']]);
                        }
                    }
                }   
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['Id Outlet not found']]);
            }
            DB::commit();
            if (\Module::collections()->has('Autocrm')) {
                
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Create Request Product',
                    auth()->user()->phone,
                );
                // return $autocrm;
                if (!$autocrm) {
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Failed to send']
                    ]);
                }
            }
            return response()->json(MyHelper::checkCreate($store));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function codeGenerate(){
        $date = date('ymd');
        $random = rand(100,999);
        $code = 'REQ-'.$date.$random;
        $cek_code = RequestProduct::where('code',$code)->first();
        if($cek_code){
            $this->codeGenerate();
        }
        return $code;
    }

    public function saveDetail($data,$detail){
    	
        if(isset($data['id_request_product'])){

            $table = new RequestProductDetail;
            $id_req = $data['id_request_product'];
            $col = 'id_request_product';

        }elseif(isset($data['id_delivery_product'])){

            $table = new DeliveryProductDetail;
            $id_req = $data['id_delivery_product'];
            $col = 'id_delivery_product';

        }

    	$delete = $table::where($col, $id_req)->delete();

        $data_detail = [];

        foreach ($detail as $value) {
            if(!isset($value['status']) && empty($value['status'])){
                $value['status'] = 'Pending';   
            }
            array_push($data_detail, [
                $col 	=> $id_req,
                'id_product_icount'  => $value['id_product_icount'],
                'unit'  => $value['unit'],
                'value'  => $value['qty'],
                'status'  => $value['status'],
            ]);
        }

        if (!empty($data_detail)) {
            $save = $table::insert($data_detail);

            return $save;
        } else {
            return false;
        }

        return true;
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
        return view('product::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function detail(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_request_product']) && !empty($post['id_request_product'])){
            $request_product = RequestProduct::where('id_request_product', $post['id_request_product'])->with(['request_product_detail','request_product_user_request','request_product_user_approve','request_product_outlet'])->first();
            if($request_product==null){
                return response()->json(['status' => 'success', 'result' => [
                    'request_product' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'request_product' => $request_product,
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
        if (!empty($post)) {
            $cek_input = $this->checkInputUpdate($post);
            $store_request = $cek_input['store_request'];
            $cek_outlet = Outlet::where(['id_outlet'=>$store_request['id_outlet']])->first();
            if ($cek_outlet) {
                DB::beginTransaction();
                $update = RequestProduct::where('id_request_product',$post['id_request_product'])->update($store_request);
                if($update) {
                    if (isset($post['product_icount'])) {
                        $save_detail = $this->saveDetail($post, $post['product_icount']);
                        if (!$save_detail) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add request hair stylist']]);
                        }
                    }else{
                        RequestProductDetail::where('product_icount', $post['product_icount'])->delete();
                    }
                }   
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['Id Outlet not found']]);
            }
            DB::commit();
            if($store_request['status']!='Pending'){
                if (\Module::collections()->has('Autocrm')) {
                
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Update Request Product',
                        auth()->user()->phone,
                    );
                    // return $autocrm;
                    if (!$autocrm) {
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Failed to send']
                        ]);
                    }
                }
            }
            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function checkInputUpdate($data){
        if (isset($data['code'])) {
            $store_request['code'] = $data['code'];
        }
        if (isset($data['id_user_request'])) {
            $store_request['id_user_request'] = $data['id_user_request'];
        }
        if (isset($data['id_outlet'])) {
            $store_request['id_outlet'] = $data['id_outlet'];
        }
        if (isset($data['type'])) {
            $store_request['type'] = $data['type'];
        }
        if (isset($data['requirement_date'])) {
            $store_request['requirement_date'] = date('Y-m-d', strtotime($data['requirement_date']));
        }
        if (isset($data['note_request'])) {
            $store_request['note_request'] = $data['note_request'];
        }
        if (isset($data['id_user_approve'])) {
            $store_request['id_user_approve'] = $data['id_user_approve'];
        }
        if (isset($data['note_approve'])) {
            $store_request['note_approve'] = $data['note_approve'];
        }
        if (isset($data['product_icount'])) {
            $status = 'Pending';
            $v_status = true;
            foreach($data['product_icount'] as $product){
                if($product['status'] == 'Approved' || $product['status'] == 'Rejected'){
                    $status = 'On Progress';
                }else{
                    $v_status = false;
                }
            }
            if($v_status){
                $status = 'Completed';
            }
            $store_request['status'] = $status;
        }
        return [
            'store_request' => $store_request,
        ];

    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $id_request_product  = $request->json('id_request_product');
        $request_product = RequestProduct::where('id_request_product', $id_request_product)->get();
        if($request_product){
            $delete = $this->deleteDetail($id_request_product);
        }
        if($delete){
            $delete = RequestProduct::where('id_request_product', $id_request_product)->delete();
            return MyHelper::checkDelete($delete);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function deleteDetail($id){
        $get = RequestProductDetail::where('id_request_product', $id)->first();
        if($get){
            $delete = RequestProductDetail::where('id_request_product', $id)->delete();
            $this->deleteDetail($id);
            return $delete;
        }else{
            return true;
        }
    }

    public function all(Request $request)
    {
        $post = $request->all();
        $request_product = RequestProduct::where('status','!=', 'Pending');
        if(isset($post['id_outlet'])){
            $request_product = $request_product->where('id_outlet',$post['id_outlet']);
        }
        if(isset($post['type'])){
            $request_product = $request_product->where('type',$post['type']);
        }
        $request_product = $request_product->get()->toArray();
        if($request_product==null){
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'success', 'result' => $request_product]);
        }
        
    }

    public function createDev(Request $request)
    {
        $post = $request->all();
        if (!empty($post)) {
            if (isset($post['id_outlet'])) {
                $store_delivery['id_outlet'] = $post['id_outlet'];
            }
            if (isset($post['type'])) {
                $store_delivery['type'] = $post['type'];
            }
            if (isset($post['charged'])) {
                $store_delivery['charged'] = $post['charged'];
            }
            $store_delivery['id_user_delivery'] = auth()->user()->id;
            $store_delivery['code'] = $this->codeGenerateDev();
            $cek_outlet = Outlet::where(['id_outlet'=>$store_delivery['id_outlet']])->first();
            if ($cek_outlet) {
                DB::beginTransaction();
                $store = DeliveryProduct::create($store_delivery); 
                if($store) {
                    if (isset($post['product_icount'])) {
                        $save_detail = $this->saveDetail($store, $post['product_icount']);
                        if (!$save_detail) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add delivery']]);
                        }
                    }

                    if (isset($post['request'])) {
                        $save_request = $this->saveDeliveryRequest($store, $post['request']);
                        if (!$save_request) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add delivery']]);
                        }
                    }
                }   
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['Id Outlet not found']]);
            }
            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function codeGenerateDev(){
        $date = date('ymd');
        $random = rand(100,999);
        $code = 'DEV-'.$date.$random;
        $cek_code = DeliveryProduct::where('code',$code)->first();
        if($cek_code){
            $this->codeGenerateDev();
        }
        return $code;
    }

    public function saveDeliveryRequest($data,$request){
        
        $table = new DeliveryRequestProduct;
        $id = $data['id_delivery_product'];
        $col = 'id_delivery_product';

    	$delete = $table::where('id_delivery_product', $id)->delete();

        $data_request = [];

        foreach ($request as $value) {
            array_push($data_request, [
                'id_delivery_product'  => $id,
                'id_request_product'  => $value,
            ]);
        }

        if (!empty($data_request)) {
            $save = $table::insert($data_request);

            return $save;
        } else {
            return false;
        }

        return true;
    }

    public function indexDev(Request $request)
    {
        $post = $request->all();

        $delivery_product = DeliveryProduct::join('users','users.id','=','delivery_products.id_user_delivery')
                                         ->join('outlets','outlets.id_outlet','=','delivery_products.id_outlet')
                                         ->select(
                                            'delivery_products.*',
                                            'outlets.outlet_name',
                                            'users.name',
                                         );

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){                
                        if($condition['operator'] == '='){
                            $delivery_product = $delivery_product->where($condition['subject'], $condition['parameter']);
                        }else{
                            $delivery_product = $delivery_product->where($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $delivery_product = $delivery_product->where(function ($q) use ($post){
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
                $delivery_product = $delivery_product->orderBy($post['order'], $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $delivery_product = $delivery_product->orderBy($post['order'], $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $delivery_product = $delivery_product->orderBy('created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $delivery_product = $delivery_product->orderBy('created_at', 'desc')->get()->toArray();
            }
        }
        return MyHelper::checkGet($delivery_product);
    }

    public function destroyDev(Request $request)
    {
        $id_delivery_product  = $request->json('id_delivery_product');
        $delivery_product = DeliveryProduct::where('id_delivery_product', $id_delivery_product)->get();
        if($delivery_product){
            $delete = $this->deleteDetailDev($id_delivery_product);
            $delete = $this->deleteRequestDev($id_delivery_product);
        }
        if($delete){
            $delete = DeliveryProduct::where('id_delivery_product', $id_delivery_product)->delete();
            return MyHelper::checkDelete($delete);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function deleteDetailDev($id){
        $get = DeliveryProductDetail::where('id_delivery_product', $id)->first();
        if($get){
            $delete = DeliveryProductDetail::where('id_delivery_product', $id)->delete();
            $this->deleteDetailDev($id);
            return $delete;
        }else{
            return true;
        }
    }
    
    public function deleteRequestDev($id){
        $get = DeliveryRequestProduct::where('id_delivery_product', $id)->first();
        if($get){
            $delete = DeliveryRequestProduct::where('id_delivery_product', $id)->delete();
            $this->deleteRequestDev($id);
            return $delete;
        }else{
            return true;
        }
    }

    public function detailDev(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_delivery_product']) && !empty($post['id_delivery_product'])){
            $delivery_product = DeliveryProduct::where('id_delivery_product', $post['id_delivery_product'])->with(['delivery_product_detail','delivery_product_user_delivery','delivery_product_user_accept','delivery_product_outlet','delivery_request_products','request'])->first();
            if($delivery_product==null){
                return response()->json(['status' => 'success', 'result' => [
                    'delivery_product' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'delivery_product' => $delivery_product,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function updateDev(Request $request)
    {
        $post = $request->all();
        if (!empty($post)) {
            $cek_input = $this->checkInputUpdateDev($post);
            $store_request = $cek_input['store_request'];
            $cek_outlet = Outlet::where(['id_outlet'=>$store_request['id_outlet']])->first();
            if ($cek_outlet) {
                DB::beginTransaction();
                $update = DeliveryProduct::where('id_delivery_product',$post['id_delivery_product'])->update($store_request);
                if($update) {
                    if (isset($post['product_icount'])) {
                        $save_detail = $this->saveDetail($post, $post['product_icount']);
                        if (!$save_detail) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add request product']]);
                        }
                    }else{
                        DeliveryProductDetail::where('id_delivery_product', $post['id_delivery_product'])->delete();
                    }
                    if (isset($post['request'])) {
                        $save_request = $this->saveDeliveryRequest($post, $post['request']);
                        if (!$save_request) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add delivery']]);
                        }
                    }else{
                        DeliveryRequestProduct::where('id_delivery_product', $post['id_delivery_product'])->delete();
                    }
                }   
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['Id Outlet not found']]);
            }
            DB::commit();
            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function checkInputUpdateDev($data){
        if (isset($data['code'])) {
            $store_request['code'] = $data['code'];
        }
        if (isset($data['id_user_delivery'])) {
            $store_request['id_user_delivery'] = $data['id_user_delivery'];
        }
        if (isset($data['id_outlet'])) {
            $store_request['id_outlet'] = $data['id_outlet'];
        }
        if (isset($data['type'])) {
            $store_request['type'] = $data['type'];
        }
        if (isset($data['charged'])) {
            $store_request['charged'] = $data['charged'];
        }
        if (isset($data['delivery_date'])) {
            $store_request['delivery_date'] = date('Y-m-d', strtotime($data['delivery_date']));
        }
        if (isset($data['product_icount'])) {
            $status = 'Draft';
            $v_status = true;
            foreach($data['product_icount'] as $product){
                if($product['status'] == 'Approved' || $product['status'] == 'Rejected'){
                    $status = 'On Progress';
                }
            }
            $store_request['status'] = $status;
        }
        return [
            'store_request' => $store_request,
        ];

    }
}
