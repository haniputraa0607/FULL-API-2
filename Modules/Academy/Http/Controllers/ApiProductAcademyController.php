<?php

namespace Modules\Academy\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Academy\Entities\ProductAcademyTheory;
use Modules\Franchise\Entities\Setting;
use Modules\Outlet\Http\Requests\Outlet\OutletList;
use Modules\Product\Entities\ProductDetail;
use DB;
use App\Lib\MyHelper;

class ApiProductAcademyController extends Controller
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
                $product = Product::join('product_detail','product_detail.id_product','=','products.id_product')
                    ->where('product_detail.id_outlet','=',$post['id_outlet'])
                    ->where('product_detail.product_detail_visibility','=','Hidden')
                    ->where('products.product_type', 'academy');
            } else {
                $ids = Product::join('product_detail','product_detail.id_product','=','products.id_product')
                    ->where('product_detail.id_outlet','=',$post['id_outlet'])
                    ->where('product_detail.product_detail_visibility','=','Hidden')
                    ->where('products.product_type', 'academy')->pluck('products.id_product')->toArray();
                $product = Product::whereNotIn('id_product', $ids)
                        ->where('products.product_type', 'academy');
            }

            unset($post['id_outlet']);
        }else{
            if(isset($post['product_setting_type']) && $post['product_setting_type'] == 'product_price'){
                $product = Product::with(['category', 'discount', 'product_special_price', 'global_price'])->where('products.product_type', 'academy');
            }elseif(isset($post['product_setting_type']) && $post['product_setting_type'] == 'outlet_product_detail'){
                $product = Product::with(['category', 'discount', 'product_detail'])->where('products.product_type', 'academy');
            }else{
                $product = Product::with(['category', 'discount'])->where('products.product_type', 'academy');
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
            $product->with(['product_academy_theory', 'global_price','product_special_price','product_tags','brands','product_icount_use','product_promo_categories'=>function($q){$q->select('product_promo_categories.id_product_promo_category');}])->where('products.product_code', $post['product_code']);
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

    public function academyTheory(Request $request){
        $post = $request->json()->all();

        if(!empty($post['theory']) && !empty($post['id_product'])){
            $insert = [];
            ProductAcademyTheory::where('id_product', $post['id_product'])->delete();
            foreach ($post['theory'] as $dt){
                $insert[] = [
                    'id_product' => $post['id_product'],
                    'id_theory' => $dt,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            if(!empty($insert)){
                $save = ProductAcademyTheory::insert($insert);
            }

            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Data theory and ID product can not be empty']]);
        }
    }
}
