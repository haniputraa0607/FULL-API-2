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
use Modules\Product\Entities\ProductCatalog;
use Monolog\Handler\NullHandler;
use App\Lib\Icount;
use Modules\BusinessDevelopment\Entities\ConfirmationLetter;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\Project\Entities\InvoiceSpk;
use Modules\Project\Entities\Project;
use Validator;
use Modules\Product\Http\Requests\CallbackRequest;
use App\Http\Models\Setting;
use App\Http\Models\User;
use Modules\Product\Entities\ProductIcount;

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
                                        ->with(['request_product_user_approve' => function($u){$u->select('id','name');}])
                                        ->select(
                                            'request_products.*',
                                            'outlets.outlet_name',
                                            'users.name',
                                        )
                                        ->where('from',$post['from']);

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
            if (isset($post['id_product_catalog'])) {
                $store_request['id_product_catalog'] = $post['id_product_catalog'];
            }
            if (isset($post['type'])) {
                $store_request['type'] = $post['type'];
            }
            if (isset($post['from'])) {
                $store_request['from'] = $post['from'];
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
                if($post['from'] && $post['from']=='Asset'){
                    $crm = 'Create Request Asset';
                }else{
                    $crm = 'Create Request Product';
                }
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    $crm,
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
            if(isset($value['id_product_icount']) && isset($value['unit']) && isset($value['qty'])){
                if(!isset($value['status']) && empty($value['status'])){
                    $value['status'] = NULL;   
                    if(isset($data['id_request_product'])){
                        $value['status'] = 'Pending';
                    }
                }
                $price = 0;
                $product_icount = ProductIcount::where('id_product_icount',$value['id_product_icount'])->first();
               
                if($value['unit'] == $product_icount['unit1']){
                    if($product_icount['unit_price_1']!=0){
                         $price = $product_icount['unit_price_1'];
                    }elseif($product_icount['unit_price_2']!=0){
                         $price = $product_icount['unit_price_2']/$product_icount['ratio2'];
                    }elseif($product_icount['unit_price_3']!=0){
                         $price = $product_icount['unit_price_3']/$product_icount['ratio3'];
                    }
                }elseif($value['unit'] == $product_icount['unit2']){
                    if($product_icount['unit_price_2']!=0){
                         $price = $product_icount['unit_price_2'];
                    }elseif($product_icount['unit_price_1']!=0){
                         $price = $product_icount['unit_price_1']*$product_icount['ratio2'];
                    }elseif($product_icount['unit_price_3']!=0){
                         $price = $product_icount['unit_price_3']*$product_icount['ratio2']/$product_icount['ratio3'];
                    }
                }elseif($value['unit'] == $product_icount['unit3']){
                    if($product_icount['unit_price_3']!=0){
                         $price = $product_icount['unit_price_3'];
                    }elseif($product_icount['unit_price_1']!=0){
                         $price = $product_icount['unit_price_1']*$product_icount['ratio3'];
                    }elseif($product_icount['unit_price_2']!=0){
                         $price = $product_icount['unit_price_3']/$product_icount['ratio2']*$product_icount['ratio3'];
                    }
                }
                $push =  [
                    $col 	=> $id_req,
                    'id_product_icount'  => $value['id_product_icount'],
                    'unit'  => $value['unit'],
                    'value'  => $value['qty'],
                    'filter'  => $value['filter'],
                    'status'  => $value['status'],
                    'price' => $price,
                    'total_price' => $price*$value['qty']
                ];
                if(isset($data['id_request_product'])){
                    $push['budget_code'] = $value['budget_code'];
                    $push['price'] = $price;
                    $push['total_price'] = $price*$value['qty'];
                }
                array_push($data_detail, $push);
            }
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
            $request_product = RequestProduct::join('product_catalogs','product_catalogs.id_product_catalog','=','request_products.id_product_catalog')
                            ->where('id_request_product', $post['id_request_product'])->select('request_products.*','product_catalogs.id_product_catalog','product_catalogs.name as catalog_name')
                            ->with(['request_product_detail','request_product_user_request','request_product_user_approve','request_product_outlet','request_product_outlet.location_outlet'])->first();
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
            $old_data = RequestProduct::where('id_request_product',$post['id_request_product'])->first();
            $cek_input = $this->checkInputUpdate($post);
            $store_request = $cek_input['store_request'];
            $store_request['id_outlet'] = $old_data['id_outlet'] ?? $store_request['id_outlet'];
            $store_request['code'] = $old_data['code'] ?? $store_request['code'];
            $cek_outlet = Outlet::where(['id_outlet'=>$store_request['id_outlet']])->first();
            if ($cek_outlet) {
                DB::beginTransaction();
                $update = RequestProduct::where('id_request_product',$post['id_request_product'])->update($store_request);
                if($update) {
                    if (isset($post['product_icount'])) {
                        $save_detail = $this->saveDetail($post, $cek_input['product_icount']);
                        if (!$save_detail) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed update request product']]);
                        }
                    }else{
                        RequestProductDetail::where('product_icount', $post['product_icount'])->delete();
                    }
                }   
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['Id Outlet not found']]);
            }
            if($store_request['status']=='Completed By User'){
                $data_send['location'] = Location::where('id_location',$cek_outlet['id_location'])->first();
                $data_send['location']['no_spk'] = $store_request['code'];
                $data_send['location']['trans_date'] = date('Y-m-d');
                $data_send['partner'] = Partner::where('id_partner',$data_send['location']['id_partner'])->first();
                $data_send['confir'] = ConfirmationLetter::where('id_partner',$data_send['location']['id_partner'])->where('id_location',$data_send['location']['id_location'])->first();
                $data_send["location_bundling"] = RequestProductDetail::where('id_request_product',$post['id_request_product'])->where('status','Approved')->join('product_icounts','product_icounts.id_product_icount','request_product_details.id_product_icount')->get()->toArray();
                $data_send["location_bundling"] = array_map(function($val){
                    $val['qty'] = $val['value'];
                    unset($val['value']);
                    return $val;
                },$data_send['location_bundling']);
                $project = Project::where('id_partner',$data_send['location']['id_partner'])->where('id_location',$data_send['location']['id_location'])->first();
                if(isset($post['from']) && $post['from'] == 'Asset'){
                    $invoice = Icount::ApiPurchaseSPK($data_send,'PT IMA');
                }elseif(isset($post['from']) && $post['from'] == 'Product'){
                    $invoice = Icount::ApiPurchaseSPK($data_send,$data_send['location']['company_type']);
                }
                if($invoice['response']['Status']=='1' && $invoice['response']['Message']=='success'){
                    $data_invoice = [
                        'id_project'=>$project['id_project'],
                        'id_request_product'=>$post['id_request_product'],
                        'id_sales_invoice'=>$invoice['response']['Data'][0]['SalesInvoiceID']??null,
                        'id_business_partner'=>$invoice['response']['Data'][0]['BusinessPartnerID'],
                        'id_branch'=>$invoice['response']['Data'][0]['BranchID'],
                        'dpp'=>$invoice['response']['Data'][0]['DPP']??null,
                        'dpp_tax'=>$invoice['response']['Data'][0]['DPPTax']??null,
                        'tax'=>$invoice['response']['Data'][0]['Tax']??null,
                        'tax_value'=>$invoice['response']['Data'][0]['TaxValue']??null,
                        'tax_date'=>date('Y-m-d H:i:s',strtotime($invoice['response']['Data'][0]['TaxDate']??date('Y-m-d'))),
                        'netto'=>$invoice['response']['Data'][0]['Netto']??null,
                        'amount'=>$invoice['response']['Data'][0]['Amount']??null,
                        'outstanding'=>$invoice['response']['Data'][0]['Outstanding']??null,
                        'value_detail'=>json_encode($invoice['response']['Data'][0]['Detail']),  
                        'message'=>$invoice['response']['Message'],
                    ];
                    $input = InvoiceSpk::create($data_invoice);
                    $update_request_pro = RequestProduct::where('id_request_product',$post['id_request_product'])->update(['id_purchase_request' => $invoice['response']['Data'][0]['PurchaseRequestID']]);
                }else{
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Failed to send purchase to ICOUNT']
                    ]);
                }
            }
            DB::commit();
            $user_request = User::where('id',$store_request['id_user_request'])->first();
            if($old_data['status'] == $store_request['status']){
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
            }else if($old_data['status'] == 'Pending' && $store_request['status'] == 'Completed By User'){
                if (\Module::collections()->has('Autocrm')) {
                    
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Product Request Approved by Admin',
                        $user_request['phone'],
                    );
                    // return $autocrm;
                    if (!$autocrm) {
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Failed to send']
                        ]);
                    }
                }
            }else if($old_data['status'] == 'Pending' && $store_request['status'] == 'Rejected'){
                if (\Module::collections()->has('Autocrm')) {
                    
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Product Request Rejected by Admin',
                        $user_request['phone'],
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
        
        $store_request['id_user_approve'] = $data['id_user_approve'] ?? auth()->user()->id;

        if (isset($data['note_approve'])) {
            $store_request['note_approve'] = $data['note_approve'];
        }
        if (isset($data['status'])) {
            $store_request['status'] = $data['status'];
        }
        if (isset($data['product_icount'])) {
            $v_status = true;
            $reject = false;
            foreach($data['product_icount'] as $key => $product){
                if($product['status'] == 'Approved'){
                    $status = 'Completed By User';
                    $reject = false;
                }else if($product['status'] == 'Rejected'){
                    $reject = true;
                }else{
                    $v_status = false;
                }

            }
            if($v_status){
                $status = 'Completed By User';
            }else{
                if (isset($data['status'])) {
                    $status = $data['status'];
                }
            }
            if($reject){
                $status = 'Rejected';
            }
            $store_request['status'] = $status;
        }
        return [
            'store_request' => $store_request,
            'product_icount' => $data['product_icount']
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
            if (isset($post['from'])) {
                $store_delivery['from'] = $post['from'];
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
            if (\Module::collections()->has('Autocrm')) {
            
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Create Delivery Product',
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
            $delivery_product = DeliveryProduct::where('id_delivery_product', $post['id_delivery_product'])->with(['delivery_product_detail','delivery_product_user_delivery','delivery_product_user_accept','delivery_product_outlet','delivery_product_outlet.location_outlet','delivery_request_products','request'])->first();
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
            // $v_status = true;
            // foreach($data['product_icount'] as $product){
            //     if($product['status'] == 'Approved' || $product['status'] == 'Rejected'){
            //         $status = 'On Progress';
            //     }
            // }
            $store_request['status'] = $status;
        }
        if (isset($data['status'])) {
            $store_request['status'] = $data['status'];
        }
        return [
            'store_request' => $store_request,
        ];

    }

    public function callbackRequest(CallbackRequest $request){
        $post = $request->all();

        if($post['status'] == 'Approve'){
            $status = 'Completed By Finance';
        }else if($post['status'] == 'Reject'){
            $status = 'Rejected';
        }
        $update = RequestProduct::where('id_purchase_request', $post['PurchaseRequestID'] ?? $post['PurchaseRequestID'])->where('status','!=','Completed By Finance')->update(['status'=>$status]);
        if($update){
            $data_req = RequestProduct::where('id_purchase_request', $post['PurchaseRequestID'])->first();
            $user_req = User::where('id',$data_req['id_user_request'])->first();
            $user_approve = User::where('id',$data_req['id_user_approve'])->first();

            if($status == 'Completed By Finance'){
                if (\Module::collections()->has('Autocrm')) {
                
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Product Request Approved by Finance',
                        $user_req['phone'],
                    );
                    // return $autocrm;
                    if (!$autocrm) {
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Failed to send']
                        ]);
                    }
                }

                if (\Module::collections()->has('Autocrm')) {
                
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Product Request Approved by Finance',
                        $user_approve['phone'],
                    );
                    // return $autocrm;
                    if (!$autocrm) {
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Failed to send']
                        ]);
                    }
                }
            }else{
                if (\Module::collections()->has('Autocrm')) {
                
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Product Request Rejected by Finance',
                        $user_req['phone'],
                    );
                    // return $autocrm;
                    if (!$autocrm) {
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Failed to send']
                        ]);
                    }
                }

                if (\Module::collections()->has('Autocrm')) {
                
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Product Request Rejected by Finance',
                        $user_approve['phone'],
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

            return response()->json(['status' => 'success']); 
        }else{
            return response()->json(['status' => 'fail']);
        }
    }

    public function listCatalog(Request $request){
        $post = $request->all();
        $catalogs = ProductCatalog::where('status', 1);

        if(isset($post['company'])){
            $catalogs = $catalogs->where('company_type', $post['company']);
        }

        $catalogs = $catalogs->get()->toArray();
        return $catalogs;
    }

}
