<?php

namespace Modules\ProductBundling\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\BrandProduct;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductModifierGroup;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\ProductBundling\Entities\Bundling;
use Modules\ProductBundling\Entities\BundlingOutlet;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductBundling\Http\Requests\CreateBundling;
use DB;
use Modules\ProductBundling\Http\Requests\UpdateBundling;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;

class ApiBundlingController extends Controller
{
    function __construct()
    {
        $this->product_variant_group = "Modules\ProductVariant\Http\Controllers\ApiProductVariantGroupController";
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Bundling $bundling)
    {
        $bundling = Bundling::with(['bundling_product'])->paginate(20)->toArray();

        foreach ($bundling['data'] as $key=>$b){
            $idProd = array_column($b['bundling_product'], 'id_product');
            $getBrands = BrandProduct::join('brands', 'brands.id_brand', 'brand_product.id_brand')
                        ->whereIn('brand_product.id_product', $idProd)
                        ->groupBy('brands.id_brand')->select('brands.id_brand', 'name_brand')
                        ->get()->toArray();
            $bundling['data'][$key]['brands'] = $getBrands;
        }
        return MyHelper::checkGet($bundling);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(CreateBundling $request)
    {
        $post = $request->json()->all();
        if(isset($post['data_product']) && !empty($post['data_product'])){
            DB::beginTransaction();
                $checkCode = Bundling::where('bundling_code', $post['bundling_code'])->first();
                if(!empty($checkCode)){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Bundling ID can not be same']]);
                }

                $isAllOutlet = 0;
                if(in_array("all", $post['id_outlet'])){
                    $isAllOutlet = 1;
                }
                //create bundling
                $createBundling = [
                    'bundling_code' => $post['bundling_code'],
                    'bundling_name' => $post['bundling_name'],
                    'start_date' => date('Y-m-d H:i:s', strtotime($post['bundling_start'])),
                    'end_date' => date('Y-m-d H:i:s', strtotime($post['bundling_end'])),
                    'bundling_description' => $post['bundling_description'],
                    'all_outlet' => $isAllOutlet
                ];
                $create = Bundling::create($createBundling);

                if(!$create){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed create bundling']]);
                }

                if(isset($post['photo'])){
                    $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/bundling/', 300, 300, $create['id_bundling']);

                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $photo['image'] = $upload['path'];
                    }
                }

                if(isset($post['photo_detail'])){
                    $upload = MyHelper::uploadPhotoStrict($post['photo_detail'], 'img/bundling/detail/', 720, 360, $create['id_bundling']);

                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $photo['image_detail'] = $upload['path'];
                    }
                }

                if(isset($photo) && !empty($photo)){
                    $updatePhotoBundling = Bundling::where('id_bundling', $create['id_bundling'])->update($photo);
                    if(!$updatePhotoBundling){
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed update photo bundling']]);
                    }
                }

                //create bundling product
                $bundlingProduct = [];
                $beforePrice = 0;
                $afterPrice = 0;
                foreach ($post['data_product'] as $product){
                    $bundlingProduct[] = [
                        'id_bundling' => $create['id_bundling'],
                        'id_brand' => $product['id_brand'],
                        'id_product' => $product['id_product'],
                        'id_product_variant_group' => $product['id_product_variant_group']??null,
                        'bundling_product_qty' => $product['qty'],
                        'bundling_product_discount_type' => $product['discount_type'],
                        'bundling_product_discount' => $product['discount'],
                        'charged_central' => $product['charged_central'],
                        'charged_outlet' => $product['charged_outlet'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $price = ProductGlobalPrice::where('id_product', $product['id_product'])->first()['product_global_price']??0;
                    if(!empty($product['id_product_variant_group'])){
                        $price = ProductVariantGroup::where('id_product_variant_group', $product['id_product_variant_group'])->first()['product_variant_group_price']??0;
                    }

                    $price = (float)$price;
                    if(strtolower($product['discount_type']) == 'nominal'){
                        $calculate = ($price - $product['discount']);
                    }else{
                        $discount = $price*($product['discount']/100);
                        $calculate = ($price - $discount);
                    }
                    $calculate = $calculate * $product['qty'];
                    $afterPrice = $afterPrice + $calculate;
                    $beforePrice = $beforePrice + ($price * $product['qty']);
                }

                $insertBundlingProduct = BundlingProduct::insert($bundlingProduct);
                if(!$insertBundlingProduct){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert list product']]);
                }

                //update price
                Bundling::where('id_bundling', $create['id_bundling'])->update(['bundling_price_before_discount' => $beforePrice, 'bundling_price_after_discount' => $afterPrice]);

                if($isAllOutlet == 0){
                    //create bundling outlet/outlet available
                    $bundlingOutlet = [];
                    foreach ($post['id_outlet'] as $outlet){
                        $bundlingOutlet[] = [
                            'id_bundling' => $create['id_bundling'],
                            'id_outlet' => $outlet,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    $bundlingOutlet = BundlingOutlet::insert($bundlingOutlet);
                    if(!$bundlingOutlet){
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed insert outlet available']]);
                    }
                }

                DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Data product can not be empty']]);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function detail(Request $request)
    {
        $post = $request->json()->all();
        if(isset($post['id_bundling']) && !empty($post['id_bundling'])){
            $detail = Bundling::where('id_bundling', $post['id_bundling'])
                    ->with(['bundling_product'])->first();

            $brands = [];
            if(!empty($detail['bundling_product'])){
                foreach ($detail['bundling_product'] as $bp){
                    $brands[] = $bp['id_brand'];
                    $bp['products'] = Product::join('brand_product','products.id_product','=','brand_product.id_product')
                        ->where('brand_product.id_brand', $bp['id_brand'])
                        ->select('products.id_product', 'products.product_code', 'products.product_name')->get()->toArray();
                    $bp['product_variant'] = [];
                    $bp['product_variant'] = app($this->product_variant_group)->productVariantGroupListAjax($bp['id_product'], 'array');
                    $bp['price'] = 0;
                    if(!empty($bp['id_product_variant_group'])){
                        $price = ProductVariantGroup::where('id_product_variant_group', $bp['id_product_variant_group'])->selectRaw('FORMAT(product_variant_group_price, 0) as price')->first();
                        $bp['price'] = $price['price']??0;
                    }elseif(!empty($bp['id_product'])){
                        $price = ProductGlobalPrice::where('id_product', $bp['id_product'])->selectRaw('FORMAT(product_global_price, 0) as price')->first();
                        $bp['price'] = $price['price']??0;
                    }
                }
            }

            $outletAvailable = Outlet::join('brand_outlet as bo', 'bo.id_outlet', 'outlets.id_outlet')
                ->groupBy('outlets.id_outlet')
                ->whereIn('bo.id_brand', $brands)
                ->select('outlets.id_outlet', 'outlets.outlet_code', 'outlets.outlet_name')
                ->orderBy('outlets.outlet_code', 'asc')
                ->get()->toArray();
            $selectedOutletAvailable = BundlingOutlet::where('id_bundling', $post['id_bundling'])->pluck('id_outlet')->toArray();

            if(!empty($detail)){
                return response()->json(['status' => 'success',
                                         'result' => [
                                             'detail' => $detail,
                                             'outlets' => $outletAvailable,
                                             'selected_outlet' => $selectedOutletAvailable
                                         ]]);
            }else{
                return response()->json(['status' => 'fail', 'messages' => ['ID bundling can not be null']]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID bundling can not be null']]);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(UpdateBundling $request)
    {
        $post = $request->json()->all();
        if(isset($post['data_product']) && !empty($post['data_product'])){
            DB::beginTransaction();

            $isAllOutlet = 0;
            if(in_array("all", $post['id_outlet'])){
                $isAllOutlet = 1;
            }

            //update bundling
            $updateBundling = [
                'bundling_name' => $post['bundling_name'],
                'start_date' => date('Y-m-d H:i:s', strtotime($post['bundling_start'])),
                'end_date' => date('Y-m-d H:i:s', strtotime($post['bundling_end'])),
                'bundling_description' => $post['bundling_description'],
                'all_outlet' => $isAllOutlet
            ];
            $update = Bundling::where('id_bundling', $post['id_bundling'])->update($updateBundling);

            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update bundling']]);
            }

            if(isset($post['photo'])){
                $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/bundling/', 300, 300, $post['id_bundling']);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $photo['image'] = $upload['path'];
                }
            }

            if(isset($post['photo_detail'])){
                $upload = MyHelper::uploadPhotoStrict($post['photo_detail'], 'img/bundling/detail/', 720, 360, $post['id_bundling']);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $photo['image_detail'] = $upload['path'];
                }
            }

            if(isset($photo) && !empty($photo)){
                $updatePhotoBundling = Bundling::where('id_bundling', $post['id_bundling'])->update($photo);
                if(!$updatePhotoBundling){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed update photo bundling']]);
                }
            }

            $afterPrice = 0;
            $beforePrice = 0;
            //update bundling product
            foreach ($post['data_product'] as $product){
                $bundlingProduct = [
                    'id_bundling' => $post['id_bundling'],
                    'id_brand' => $product['id_brand'],
                    'id_product' => $product['id_product'],
                    'id_product_variant_group' => $product['id_product_variant_group']??null,
                    'bundling_product_qty' => $product['qty'],
                    'bundling_product_discount_type' => $product['discount_type'],
                    'bundling_product_discount' => $product['discount'],
                    'charged_central' => $product['charged_central'],
                    'charged_outlet' => $product['charged_outlet'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                if(isset($product['id_bundling_product']) && !empty($product['id_bundling_product'])){
                    $saveBundlingProduct = BundlingProduct::where('id_bundling_product', $product['id_bundling_product'])->update($bundlingProduct);
                }else{
                    $bundlingProduct['created_at'] = date('Y-m-d H:i:s');
                    $saveBundlingProduct = BundlingProduct::create($bundlingProduct);
                }

                if(!$saveBundlingProduct){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed save list product']]);
                }

                $price = ProductGlobalPrice::where('id_product', $product['id_product'])->first()['product_global_price']??0;
                if(!empty($product['id_product_variant_group'])){
                    $price = ProductVariantGroup::where('id_product_variant_group', $product['id_product_variant_group'])->first()['product_variant_group_price']??0;
                }

                $price = (float)$price;
                if(strtolower($product['discount_type']) == 'nominal'){
                    $calculate = ($price - $product['discount']);
                }else{
                    $discount = $price*($product['discount']/100);
                    $calculate = ($price - $discount);
                }
                $calculate = $calculate * $product['qty'];
                $afterPrice = $afterPrice + $calculate;
                $beforePrice = $beforePrice + ($price * $product['qty']);
            }

            //update price
            Bundling::where('id_bundling', $post['id_bundling'])->update(['bundling_price_before_discount' => $beforePrice, 'bundling_price_after_discount' => $afterPrice]);

            //delete bundling outlet
            $delete = BundlingOutlet::where('id_bundling', $post['id_bundling'])->delete();

            if(!$delete){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed delete outlet available']]);
            }

            if($isAllOutlet == 0){
                //create bundling outlet/outlet available
                $bundlingOutlet = [];
                foreach ($post['id_outlet'] as $outlet){
                    $bundlingOutlet[] = [
                        'id_bundling' => $post['id_bundling'],
                        'id_outlet' => $outlet,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                $bundlingOutlet = BundlingOutlet::insert($bundlingOutlet);
                if(!$bundlingOutlet){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed insert outlet available']]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Data product can not be empty']]);
        }
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

    public function outletAvailable(Request $request){
        $post = $request->json()->all();
        if(isset($post['brands']) && !empty($post['brands'])){
            $idBrand = array_column($post['brands'], 'value');
            $count = count($idBrand);
            $paramValue = '';
            foreach ($idBrand as $index => $p){
                if($index !== $count-1){
                    $paramValue .= 'bo.id_brand = "'.$p.'" OR ';
                }else{
                    $paramValue .= 'bo.id_brand = "'.$p.'"';
                }
            }

            $outlets = Outlet::join('brand_outlet as bo', 'bo.id_outlet', 'outlets.id_outlet')
                ->groupBy('bo.id_outlet')
                ->whereRaw($paramValue)
                ->havingRaw('COUNT(*) >= '.$count)
                ->select('outlets.id_outlet', 'outlets.outlet_code', 'outlets.outlet_name')
                ->orderBy('outlets.outlet_code', 'asc')
                ->get()->toArray();

            return response()->json(MyHelper::checkGet($outlets));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted parameter']]);
        }
    }

    public function detailForApps(Request $request){
        $post = $request->json()->all();
        if(!isset($post['id_bundling']) && empty($post['id_bundling'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID bundling can not be empty']]);
        }

        if(!isset($post['id_outlet']) && empty($post['id_outlet'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

        $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet', $post['id_outlet'])->first();
        if (!$outlet) {
            return ['status' => 'fail','messages' => ['Outlet not found']];
        }

        $getProductBundling = BundlingProduct::join('products', 'products.id_product', 'bundling_product.id_product')
            ->join('brand_product', 'brand_product.id_product', 'products.id_product')
            ->leftJoin('product_global_price as pgp', 'pgp.id_product', '=', 'products.id_product')
            ->join('bundling', 'bundling.id_bundling', 'bundling_product.id_bundling')
            ->where('bundling.id_bundling', $post['id_bundling'])
            ->select('brand_product.id_brand', 'pgp.product_global_price',  'products.product_variant_status', 'products.product_name',
                'products.product_code', 'products.product_description',
                'bundling_product.*', 'bundling.*')
            ->get()->toArray();

        if (empty($getProductBundling)) {
            return ['status' => 'fail','messages' => ['Bundling detail not found']];
        }

        //check available outlet
        $availableOutlet = BundlingOutlet::where('id_outlet', $post['id_outlet'])->where('id_bundling', $post['id_bundling'])->first();
        if (!$availableOutlet) {
            return ['status' => 'fail','messages' => ['Product not available in this outlet']];
        }

        $priceForListNoDiscount = 0;
        $priceForList = 0;
        $products = [];
        foreach ($getProductBundling as $p){
            $price = $p['product_global_price'];

            if($outlet['outlet_different_price'] == 1){
                $price = ProductSpecialPrice::where('id_product', $p['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_special_price']??0;
            }

            if ($p['product_variant_status']) {
                $variantTree = Product::getVariantTree($p['id_product'], $outlet);
                $price = $variantTree['base_price']??0;
            }

            $variants = [];
            $extraModifier = [];
            $selectedExtraMod = [];
            if($p['product_variant_status'] && !empty($p['id_product_variant_group'])){
                $variants = ProductVariantGroup::join('product_variant_pivot', 'product_variant_pivot.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                    ->join('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant')
                    ->where('product_variant_groups.id_product_variant_group', $p['id_product_variant_group'])
                    ->select('product_variants.id_product_variant', 'product_variant_pivot.id_product_variant_group', 'product_variant_name')
                    ->get()->toArray();
                $idVariant = array_column($variants, 'id_product_variant');
                $getExtraModifier = ProductModifierGroup::join('product_modifier_group_pivots', 'product_modifier_groups.id_product_modifier_group', 'product_modifier_group_pivots.id_product_modifier_group')
                                    ->join('product_modifiers', 'product_modifiers.id_product_modifier_group', 'product_modifier_groups.id_product_modifier_group')
                                    ->where('id_product', $p['id_product'])->orWhereIn('id_product_variant', $idVariant)
                                    ->orderBy('product_modifiers.id_product_modifier_group', 'asc')
                                    ->orderBy('product_modifier_order', 'asc')
                                    ->select('id_product_modifier', 'text_detail_trx', 'product_modifiers.id_product_modifier_group')->get()->toArray();

                foreach ($getExtraModifier as $em){
                    $check = array_search($em['id_product_modifier_group'], array_column($selectedExtraMod, 'id_product_modifier_group'));
                    if($check === false){
                        $extraModifier[] = $em['id_product_modifier'];
                        $selectedExtraMod[] = [
                            'id_product_modifier_group' => $em['id_product_modifier_group'],
                            'id_product_variant' => $em['id_product_modifier'],
                            'product_variant_name' => $em['text_detail_trx']
                        ];
                    }
                }
            }

            $price = (float)$price;
            for ($i=0;$i<$p['bundling_product_qty'];$i++){
                $priceForListNoDiscount = $priceForListNoDiscount + $price;

                //calculate discount produk
                if(strtolower($p['bundling_product_discount_type']) == 'nominal'){
                    $calculate = ($price - $p['bundling_product_discount']);
                }else{
                    $discount = $price*($p['bundling_product_discount']/100);
                    $calculate = ($price - $discount);
                }
                $priceForList = $priceForList + $calculate;

                $products[] = [
                    'id_product' => $p['id_product'],
                    'id_brand' => $p['id_brand'],
                    'id_bundling' => $p['id_bundling'],
                    'id_bundling_product' => $p['id_bundling_product'],
                    'id_product_variant_group' => $p['id_product_variant_group'],
                    'product_name' => $p['product_name'],
                    'product_code' => $p['product_code'],
                    'product_description' => $p['product_description'],
                    'extra_modifiers' => $extraModifier,
                    'variants' => array_merge($variants, $selectedExtraMod)
                ];
            }
        }

        $result = [
            'id_bundling' => $getProductBundling[0]['id_bundling'],
            'bundling_name' => $getProductBundling[0]['bundling_name'],
            'bundling_code' => $getProductBundling[0]['bundling_code'],
            'bundling_description' => $getProductBundling[0]['bundling_description'],
            'bundling_image_detail' => (!empty($getProductBundling[0]['image_detail']) ? config('url.storage_url_api').$getProductBundling[0]['image_detail'] : ''),
            'bundling_base_price' => $priceForList,
            'bundling_base_price_no_discount' => $priceForListNoDiscount,
            'products' => $products
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function globalPrice(Request $request){
        $post = $request->json()->all();
        if(!isset($post['id_product']) && empty($post['id_product']) &&
            !isset($post['id_product_variant_group']) && empty($post['id_product_variant_group'])){

            return ['status' => 'fail','messages' => ['ID product and ID product variant group can not be empty']];
        }

        $price = [];
        if(!empty($post['id_product'])){
            $price = ProductGlobalPrice::where('id_product', $post['id_product'])->selectRaw('FORMAT(product_global_price, 0) as price')->first();
        }elseif(!empty($post['id_product_variant_group'])){
            $price = ProductVariantGroup::where('id_product_variant_group', $post['id_product_variant_group'])->selectRaw('FORMAT(product_variant_group_price, 0) as price')->first();
        }

        return response()->json(MyHelper::checkGet($price));
    }
}
