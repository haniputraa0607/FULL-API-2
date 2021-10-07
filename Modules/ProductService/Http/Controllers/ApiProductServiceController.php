<?php

namespace Modules\ProductService\Http\Controllers;

use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\ProductDetail;
use App\Lib\MyHelper;
use DB;
use Modules\ProductService\Entities\ProductServiceUse;

class ApiProductServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['visibility'])) {

            if ($post['visibility'] == 'Hidden') {
                $idVisible = ProductDetail::join('products', 'products.id_product', '=', 'product_detail.id_product')
                    ->where('product_detail.product_detail_visibility', 'Visible')
                    ->where('product_detail.product_detail_status', 'Active')
                    ->where('id_outlet', $post['id_outlet'])
                    ->where('products.product_type', 'service')
                    ->pluck('product_detail.id_product')->toArray();
                $product = Product::whereNotIn('products.id_product', $idVisible)->where('products.product_type', 'service');
            } else {
                $product = Product::join('product_detail','product_detail.id_product','=','products.id_product')
                    ->where('product_detail.id_outlet','=',$post['id_outlet'])
                    ->where('product_detail.product_detail_visibility','=','Visible')
                    ->where('product_detail.product_detail_status','=','Active')
                    ->where('products.product_type', 'service');
            }

            unset($post['id_outlet']);
        }else{
            if(isset($post['product_setting_type']) && $post['product_setting_type'] == 'product_price'){
                $product = Product::with(['category', 'discount', 'product_special_price', 'global_price'])->where('products.product_type', 'service');
            }elseif(isset($post['product_setting_type']) && $post['product_setting_type'] == 'outlet_product_detail'){
                $product = Product::with(['category', 'discount', 'product_detail'])->where('products.product_type', 'service');
            }else{
                $product = Product::with(['category', 'discount', 'product_service_use'])->where('products.product_type', 'service');
            }
        }

        if(isset($post['rule'])){
            foreach ($post['rule'] as $rule){
                if($rule[0] !== 'all_product'){
                    if ($rule[1] == 'like' && isset($rule[2])) {
                        $rule[2] = '%' . $rule[2] . '%';
                    }

                    if($post['operator'] == 'or'){
                        if(isset($rule[2])){
                            $product->orWhere('products.'.$rule[0], $rule[1],$rule[2]);
                        }else{
                            $product->orWhere('products.'.$rule[0], $rule[1]);
                        }
                    }else{
                        if(isset($rule[2])){
                            $product->where('products.'.$rule[0], $rule[1],$rule[2]);
                        }else{
                            $product->where('products.'.$rule[0], $rule[1]);
                        }
                    }
                }
            }
        }

        if (isset($post['id_product'])) {
            $product->with('category')->where('products.id_product', $post['id_product'])->with(['brands']);
        }

        if (isset($post['product_code'])) {
            $product->with(['global_price','product_special_price','product_tags','brands','product_promo_categories'=>function($q){$q->select('product_promo_categories.id_product_promo_category');}])->where('products.product_code', $post['product_code']);
        }

        if (isset($post['update_price']) && $post['update_price'] == 1) {
            $product->where('product_variant_status', 0);
        }

        if (isset($post['product_name'])) {
            $product->where('products.product_name', 'LIKE', '%'.$post['product_name'].'%');
        }

        if(isset($post['orderBy'])){
            $product = $product->orderBy($post['orderBy']);
        }
        else{
            $product = $product->orderBy('position', 'asc');
        }

        if(isset($post['admin_list'])){
            $product = $product->withCount('product_detail')->withCount('product_detail_hiddens')->with(['brands']);
        }

        if(isset($post['pagination'])){
            $product = $product->paginate(10);
        }else{
            $product = $product->get();
        }

        if (!empty($product)) {
            foreach ($product as $key => $value) {
                $product[$key]['photos'] = ProductPhoto::select('*', DB::raw('if(product_photo is not null, (select concat("'.config('url.storage_url_api').'", product_photo)), "'.config('url.storage_url_api').'img/default.jpg") as url_product_photo'))->where('id_product', $value['id_product'])->orderBy('product_photo_order', 'ASC')->get()->toArray();
            }
        }

        $product = $product->toArray();
        return response()->json(MyHelper::checkGet($product));
    }

    public function productUseList(){
        $list = Product::where('product_type', 'product')->select('id_product', 'product_name', 'product_code')->get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function productUseUpdate(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_product_service']) && !empty($post['id_product_service'])){
            if(empty($post['product_use_data'])){
                return response()->json(['status' => 'fail', 'messages' => ['Data can not be empty']]);
            }

            ProductServiceUse::where('id_product_service', $post['id_product_service'])->delete();
            $insert = [];
            foreach ($post['product_use_data'] as $pu){
                $insert[] = [
                    'id_product_service' => $post['id_product_service'],
                    'id_product' => $pu['id_product'],
                    'quantity_use' => (int)$pu['quantity_use'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            $save = ProductServiceUse::insert($insert);
            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID product service can not be empty']]);
        }
    }
}
