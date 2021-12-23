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

class ApiRequestProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
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
    	
        $table = new RequestProductDetail;
    	$id_req = $data['id_request_product'];

    	$delete = $table::where('id_request_product', $id_req)->delete();

        $data_detail = [];

        foreach ($detail as $value) {
            if(!isset($value['status']) && empty($value['status'])){
                $value['status'] = 'Pending';   
            }
            array_push($data_detail, [
                'id_request_product' 	=> $id_req,
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
}
