<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\ProductCatalogDetail;
use Modules\Product\Entities\ProductCatalog;
use Modules\Product\Entities\RequestProduct;
use Modules\Product\Entities\RequestProductDetail;
use App\Lib\MyHelper;
use DB;
use Modules\Product\Entities\DeliveryProduct;
use Modules\Product\Entities\DeliveryProductDetail;
use Modules\Product\Entities\DeliveryRequestProduct;
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

class ApiProductCatalogController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->all();

        $catalog = ProductCatalog::select('product_catalogs.*');

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){   

                        if($condition['subject']=='status'){
                            $condition['parameter'] = $condition['operator'];
                            $condition['operator'] = '=';
                        }

                        if($condition['operator'] == '='){
                            $catalog = $catalog->where($condition['subject'], $condition['parameter']);
                        }else{
                            $catalog = $catalog->where($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $catalog = $catalog->where(function ($q) use ($post){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){

                            if($condition['subject']=='status'){
                                $condition['parameter'] = $condition['operator'];
                                $condition['operator'] = '=';
                            }

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
                $catalog = $catalog->orderBy($post['order'], $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $catalog = $catalog->orderBy($post['order'], $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $catalog = $catalog->orderBy('created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $catalog = $catalog->orderBy('created_at', 'desc')->get()->toArray();
            }
        }
        return MyHelper::checkGet($catalog);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Request $request)
    {
        $post = $request->all();
        if (!empty($post)) {
            if (isset($post['name'])) {
                $store_data['name'] = $post['name'];
            }
            if (isset($post['company_type'])) {
                $store_data['company_type'] = $post['company_type'];
            }

            $store_data['status'] = 0;
            if (isset($post['status'])) {
                $store_data['status'] = 1;
            }
            if (isset($post['description'])) {
                $store_data['description'] = $post['description'];
            }

            DB::beginTransaction();
            $store = ProductCatalog::create($store_data); 
            if($store) {
                if (isset($post['product_catalog_detail'])) {
                    $save_detail = $this->saveDetail($store, $post['product_catalog_detail']);
                    if (!$save_detail) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed add product catalog']]);
                    }
                }
            }   

            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function saveDetail($data,$detail){
        
        $table = new ProductCatalogDetail;
        $id = $data['id_product_catalog'];
        $col = 'id_product_catalog';

    	$delete = $table::where($col, $id)->delete();

        $data_detail = [];

        foreach ($detail as $value) {
            if(isset($value['id_product_icount'])){

                $push =  [
                    $col 	=> $id,
                    'id_product_icount'  => $value['id_product_icount'],
                    'filter'  => $value['filter'],
                    'budget_code'  => $value['budget_code'],
                ];

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
    public function show(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_product_catalog']) && !empty($post['id_product_catalog'])){
            $catalog = ProductCatalog::where('id_product_catalog', $post['id_product_catalog'])->with(['product_catalog_details'])->first();
            if($catalog==null){
                return response()->json(['status' => 'success', 'result' => [
                    'product_catalog' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'product_catalog' => $catalog,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('product::edit');
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
            $data_store = $this->checkInputUpdate($post);
            DB::beginTransaction();
            $update = ProductCatalog::where('id_product_catalog',$post['id_product_catalog'])->update($data_store);
            if($update) {
                if (isset($post['product_catalog_detail'])) {
                    $save_detail = $this->saveDetail($post, $post['product_catalog_detail']);
                    if (!$save_detail) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed add request product']]);
                    }
                }else{
                    ProductCatalogDetail::where('id_product_catalog', $post['id_product_catalog'])->delete();
                }
            }   

            DB::commit();
            return response()->json(MyHelper::checkUpdate($update));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function checkInputUpdate($data){
        if (isset($data['name'])) {
            $data_store['name'] = $data['name'];
        }
        if (isset($data['company_type'])) {
            $data_store['company_type'] = $data['company_type'];
        }
        if (isset($data['description'])) {
            $data_store['description'] = $data['description'];
        }

        $data_store['status'] = 0;
        if (isset($data['status'])) {
            $data_store['status'] = 1;
        }
        
        return $data_store;

    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $id_product_catalog  = $request->json('id_product_catalog');
        $catalog = ProductCatalog::where('id_product_catalog', $id_product_catalog)->get();
        if($catalog){
            $delete = $this->deleteDetail($id_product_catalog);
        }
        if($delete){
            $delete = ProductCatalog::where('id_product_catalog', $id_product_catalog)->delete();
            return MyHelper::checkDelete($delete);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function deleteDetail($id){
        $get = ProductCatalogDetail::where('id_product_catalog', $id)->first();
        if($get){
            $delete = ProductCatalogDetail::where('id_product_catalog', $id)->delete();
            $this->deleteDetail($id);
            return $delete;
        }else{
            return true;
        }
    }
}
