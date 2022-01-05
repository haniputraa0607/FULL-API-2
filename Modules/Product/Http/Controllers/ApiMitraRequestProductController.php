<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\RequestProduct;
use Modules\Product\Entities\RequestProductDetail;
use Modules\Product\Entities\DeliveryProduct;
use Modules\Product\Entities\DeliveryProductDetail;
use Modules\Product\Entities\DeliveryRequestProduct;

use DB;
use App\Lib\MyHelper;
use Modules\Product\Entities\DeliveryProductImage;
use Modules\Product\Entities\ProductIcount;

class ApiMitraRequestProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->deliv_path = "img/product/delivery_product/";
    }

    public function index(Request $request)
    {
        $post = $request->all();
        $id_outlet =  auth()->user()->id_outlet;
        
        $delivery_product = DeliveryProduct::join('delivery_product_details','delivery_product_details.id_delivery_product','=','delivery_products.id_delivery_product')
                            ->where('delivery_products.id_outlet',$id_outlet)
                            ->where('delivery_products.status','!=','Draft')
                            ->select(
                                'delivery_products.id_delivery_product',
                                'delivery_products.code',
                                'delivery_products.type',
                                'delivery_products.delivery_date',
                                DB::raw('
                                        COUNT(delivery_product_details.id_delivery_product_detail) as total_trx
                                    ')
                            )->OrderBy('id_delivery_product', 'DESC');

        if(isset($post['page'])){
            $delivery_product = $delivery_product->paginate($request->length ?: 10)->toArray();
        }else{
            $delivery_product = $delivery_product->get()->toArray();
        }

        return [
            'status' => 'success',
            'result' => $delivery_product
        ];
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('product::create');
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
        if (isset($post['id_delivery_product']) && !empty($post['id_delivery_product'])) {
            $id_outlet =  auth()->user()->id_outlet;

            $delivery_product = DeliveryProduct::join('delivery_product_details','delivery_product_details.id_delivery_product','=','delivery_products.id_delivery_product')
                            ->where('delivery_products.id_outlet',$id_outlet)
                            ->where('delivery_products.status','!=','Draft')
                            ->where('delivery_products.id_delivery_product', $post['id_delivery_product'])
                            ->with('delivery_product_images')
                            ->select(
                                'delivery_products.id_delivery_product',
                                'delivery_products.code',
                                'delivery_products.type',
                                'delivery_products.delivery_date',
                                DB::raw('
                                        COUNT(delivery_product_details.id_delivery_product_detail) as total_trx
                                    ')
                            )->first();
            
            if($delivery_product['id_delivery_product']){
                $products = DeliveryRequestProduct::with(['delivery_product' => function($query) {
                                $query->select('id_delivery_product');
                                $query->with(['delivery_product_detail' => function($query) {
                                    $query->where('status','Approved');
                                    $query->with(['delivery_product_icount' => function($query){
                                            $query->select('id_product_icount','name');
                                    }]);
                                }]);
                            }])
                            ->with(['request_product' => function($query) {
                                $query->select('id_request_product');
                                $query->with(['request_product_detail' => function($query) {
                                    $query->where('status','Approved');
                                    $query->with(['request_product_icount' => function($query){
                                            $query->select('id_product_icount','name');
                                    }]);
                                }]);
                            }])
                            ->where('id_delivery_product',$post['id_delivery_product'])->get()->toArray();
                
                $new_pro = 0;
                $dev = 0;
                if ($products[0]['delivery_product']) {
                    foreach ($products[0]['delivery_product']['delivery_product_detail'] as $detail) {
                        $delivery[$dev] = [
                            "id_product_icount" => $detail['delivery_product_icount']['id_product_icount'],
                            "name" => $detail['delivery_product_icount']['name'],
                            "delivery" => $detail['value'],
                        ];
                        $dev++;
                    }
                }
                foreach($products as $key => $product){
                    if($product['request_product']){
                        foreach ($product['request_product']['request_product_detail'] as $detail) {
                            $new_products[$new_pro] = [
                                "id_product_icount" => $detail['request_product_icount']['id_product_icount'],
                                "name" => $detail['request_product_icount']['name'],
                                "unit" => $detail['unit'],
                                "request" => $detail['value'],
                                "delivery" => 0,
                                "status" => "Kurang"
                            ];
                            $new_pro++;
                        }
                    }
                }
                foreach($new_products as $key => $new_product){
                    foreach($new_products as $key2 => $cek){
                        if($new_product['name'] == $cek['name'] && $key < $key2){
                            $new_products[$key] = [
                                "id_product_icount" => $new_product['id_product_icount'],
                                "name" => $new_product['name'],
                                "unit" => $new_product['unit'],
                                "request" => $new_products[$key]['request']+$cek['request'],
                                "delivery" => 0,
                                "status" => "Kurang"
                            ];
                            unset($new_products[$key2]);
                        }
                    }
                }
                foreach($new_products as $key => $new_product){
                    foreach($delivery as $dev => $deliv){
                        if($new_product['name'] == $deliv['name']){
                            $new_products[$key]['delivery'] = $deliv['delivery'];
                            if($new_product['request'] <= $deliv['delivery']){
                                $new_products[$key]['status'] = 'Lengkap';
                            }
                        }
                    }
                }
                $delivery_product['detail'] = $new_products;
            }else{
                $delivery_product['detail'] = [];
            }
            
            return [
                'status' => 'success',
                'result' => $delivery_product
            ];
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function confirm(Request $request){
        $post = $request->all();
        if (isset($post['id_delivery_product']) && !empty($post['id_delivery_product'])) {
            
            $update = [
                'status'            => 'Completed',
                'id_user_accept'    => auth()->user()->id_user_hair_stylist,
                'confirmation_date' => date('Y-m-d'),
            ];
            if(isset($post['note'])){
                $update['confirmation_note'] = $post['note'];
            }

            if(isset($post['images'])){
                DB::beginTransaction();
                $delete_image = DeliveryProductImage::where('id_delivery_product',$post['id_delivery_product'])->delete();
                foreach($post['images'] as $key => $image){
                    $name_file = 'attachment_'.$post['id_delivery_product'].'_'.$key;
                    $path_full = $this->deliv_path.$name_file;
                    $delete_path = MyHelper::deletePhoto($path_full);
                    $upload = MyHelper::uploadPhoto($image, $this->deliv_path, null, $name_file);
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $save_image = [
                            "id_delivery_product" => $post['id_delivery_product'],
                            "path"                => $upload['path']
                        ];
                        $storage_image = DeliveryProductImage::create($save_image);
                    }else {
                        DB::rollback();
                        return response()->json([
                            'status'=>'fail',
                            'messages'=>['Failed to confirm delivery product']
                        ]);
                    }
                }
            }

            if($post['detail']){
                foreach($post['detail'] as $key => $product){
                    $product_icount = new ProductIcount();
                    $update_stock = $product_icount->find($product['id_product_icount'])->addLogStockProductIcount($product['delivery'],$product['unit'],'Delivery Product',$post['id_delivery_product']);
                }
            }

            $update_status = DeliveryProduct::where('id_delivery_product',$post['id_delivery_product'])->update($update);
            if(!$update_status){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed to confirm delivery product']]);
            }
            DB::commit();

            return response()->json(['status' => 'success']);
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
