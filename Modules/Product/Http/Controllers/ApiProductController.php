<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Holiday;
use Modules\ProductService\Entities\ProductHairstylistCategory;
use Modules\Recruitment\Entities\HairstylistAttendance;
use Modules\Recruitment\Entities\HairstylistAttendanceLog;
use Storage;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductDiscount;
use App\Http\Models\ProductPhoto;
use App\Http\Models\NewsProduct;
use App\Http\Models\TransactionProduct;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductModifier;
use App\Http\Models\ProductModifierBrand;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierGlobalPrice;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use Lcobucci\JWT\Parser;
use Modules\Outlet\Entities\OutletTimeShift;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductIcount;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Product\Entities\ProductStockStatusUpdate;
use Modules\Product\Entities\ProductProductPromoCategory;
use Modules\Product\Entities\ProductGroup;
use App\Lib\Icount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Modules\Product\Http\Requests\product\AvailableHs;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantPivot;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use Validator;
use Hash;
use DB;
use Mail;
use Image;

use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\Brand;

use Modules\Product\Http\Requests\product\Create;
use Modules\Product\Http\Requests\product\Update;
use Modules\Product\Http\Requests\product\Delete;
use Modules\Product\Http\Requests\product\UploadPhoto;
use Modules\Product\Http\Requests\product\UpdatePhoto;
use Modules\Product\Http\Requests\product\DeletePhoto;
use Modules\Product\Http\Requests\product\Import;
use Modules\Product\Http\Requests\product\UpdateAllowSync;
use Modules\PromoCampaign\Entities\UserPromo;
use App\Http\Models\Deal;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\Subscription\Entities\Subscription;
use App\Http\Models\OutletSchedule;
use Modules\Product\Http\Requests\product\DeleteIcount;
use Modules\ProductService\Entities\ProductServiceUse;
use Modules\Product\Entities\ProductCommissionDefault;
use Modules\Product\Entities\ProductCommissionDefaultDynamic;
use Modules\Product\Http\Requests\product\Commission;
use App\Jobs\SyncIcountItems;
use Modules\Product\Entities\ProductCatalogDetail;
use Modules\Product\Entities\ProductIcountOutletStockLog;
use Modules\Product\Entities\ProductIcountOutletStock;
use Modules\Product\Entities\UnitIcount;
use Modules\Product\Entities\UnitIcountConversion;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Product\Entities\ProductProductIcount;
use Modules\BusinessDevelopment\Entities\OutletStarterBundlingProduct;
use Modules\BusinessDevelopment\Entities\LocationOutletStarterBundlingProduct;
use Modules\Employee\Entities\EmployeeReimbursement;
use Modules\Employee\Entities\EmployeeCashAdvance;
use Modules\Employee\Entities\EmployeeCashAdvanceProductIcount;
use Modules\Product\Entities\RequestProductDetail;
use Modules\Product\Entities\DeliveryProductDetail;

class ApiProductController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public $saveImage = "img/product/item/";
    public $saveImageIcount = "img/product/icount/item/";

    function checkInputProduct($post=[], $type=null) {
    	$data = [];

    	if (empty($post['id_product_category']) || isset($post['id_product_category'])) {

            if (empty($post['id_product_category'])) {
                $data['id_product_category'] = NULL;
            }
            else {
                $data['id_product_category'] = $post['id_product_category'];
            }
    	}
    	if (isset($post['product_code'])) {
    		$data['product_code'] = $post['product_code'];
    	} else {
    	    $code = [
    	        'product' => 'P',
                'service' => 'SVC',
                'academy' => 'CRS'
            ];
            $count = Product::where('product_type', $post['product_type'])->orderBy('product_code', 'desc')->first()['product_code']??'';
            $lastCode = substr($count, -4);
            $lastCode = (int)$lastCode;
            $countCode = $lastCode+1;
            $data['product_code'] = ($code[$post['product_type']]??'P').sprintf("%04d", $countCode);
        }

        if(isset($post['product_photo_detail'])){
            $upload = MyHelper::uploadPhotoStrict($post['product_photo_detail'], 'img/product/item/detail/', 720, 360, $data['product_code'].'-'.strtotime("now"));

    	    if (isset($upload['status']) && $upload['status'] == "success") {
    	        $data['product_photo_detail'] = $upload['path'];
    	    }
    	    else {
    	        $data['product_photo_detail'] = null;
    	    }
        }

    	if (isset($post['product_name'])) {
    		$data['product_name'] = $post['product_name'];
    	}
    	
        if (isset($post['product_name_pos'])) {
            $data['product_name_pos'] = $post['product_name_pos'];
        }
    	if (isset($post['product_description'])) {
    		$data['product_description'] = $post['product_description'];
    	}
    	if (isset($post['product_video'])) {
    		$data['product_video'] = $post['product_video'];
    	}
    	if (isset($post['product_type'])) {
    		$data['product_type'] = $post['product_type'];
    	}
    	if (isset($post['product_price'])) {
    		$data['product_price'] = $post['product_price'];
    	}
    	if (isset($post['product_weight'])) {
    		$data['product_weight'] = $post['product_weight'];
    	}
    	if (isset($post['product_visibility'])) {
    		$data['product_visibility'] = $post['product_visibility'];
    	}
    	if (isset($post['product_order'])) {
    		$data['product_order'] = $post['product_order'];
    	}
        if (isset($post['product_variant_status'])) {
            $data['product_variant_status'] = 1;
        }else{
            $data['product_variant_status'] = 0;
        }

        if (isset($post['processing_time_service'])) {
            $data['processing_time_service'] = $post['processing_time_service'];
        }

        if (isset($post['available_home_service'])) {
            $data['available_home_service'] = 1;
        }else{
            $data['available_home_service'] = 0;
        }

        if (isset($post['product_brands'])) {
            if(($post['product_brands'][0]??false) == '*') {
                $data['product_brands'] = Brand::select('id_brand')->pluck('id_brand')->toArray();
            } else {
                $data['product_brands'] = $post['product_brands'];
            }
        }

        if($type == 'create' && !($data['product_brands']??false) && ($data['id_product_category']??false)){
            $data['product_brands'] = ['0'];
        }

        // search position
        if ($type == "create") {
            if (isset($post['id_product_category'])) {
                $data['position'] = $this->searchLastSorting($post['id_product_category']);
            }
            else {
                $data['position'] = $this->searchLastSorting(null);
            }
        }

        if (isset($post['product_short_description'])) {
            $data['product_short_description'] = $post['product_short_description'];
        }

        if (isset($post['product_academy_duration'])) {
            $data['product_academy_duration'] = $post['product_academy_duration'];
        }

        if (isset($post['product_academy_total_meeting'])) {
            $data['product_academy_total_meeting'] = $post['product_academy_total_meeting'];
        }

        if (isset($post['product_academy_hours_meeting'])) {
            $data['product_academy_hours_meeting'] = $post['product_academy_hours_meeting'];
        }

        if (isset($post['product_academy_maximum_installment'])) {
            $data['product_academy_maximum_installment'] = $post['product_academy_maximum_installment'];
        }
    	return $data;
    }

    /**
     * cari urutan ke berapa
     */
    function searchLastSorting($id_product_category=null) {
        $sorting = Product::select('position')->orderBy('position', 'DESC');

        if (is_null($id_product_category)) {
            $sorting->whereNull('id_product_category');
        }
        else {
            $sorting->where('id_product_category', $id_product_category);
        }

        $sorting = $sorting->first();

        if (empty($sorting)) {
            return 1;
        }
        else {
            // kalo kosong otomatis jadiin nomer 1
            if (empty($sorting->position)) {
                return 1;
            }
            else {
                $sorting = $sorting->position + 1;
                return $sorting;
            }
        }
    }

    public function priceUpdate(Request $request) {
		$post = $request->json()->all();
        $date_time = date('Y-m-d H:i:s');
		foreach ($post['id_product_price'] as $key => $id_product_price) {
			if($id_product_price == 0){
				$update = ProductPrice::create(['id_product' => $post['id_product'],
												'id_outlet' => $post['id_outlet'][$key],
												'product_price' => $post['product_price'][$key],
												'product_price_base' => $post['product_price_base'][$key],
												'product_price_tax' => $post['product_price_tax'][$key],
												'product_stock_status' => $post['product_stock_status'][$key],
												'product_visibility' => $post['product_visibility'][$key]
												]);
                $create = ProductStockStatusUpdate::create([
                    'id_product' => $post['id_product'],
                    'id_user' => $request->user()->id,
                    'user_type' => 'users',
                    'id_outlet' => $post['id_outlet'][$key],
                    'date_time' => $date_time,
                    'new_status' => $post['product_stock_status'][$key],
                    'id_outlet_app_otp' => null
                ]);
			}
			else{
                $pp = ProductPrice::where('id_product_price','=',$id_product_price)->first();
                if(!$pp){continue;}
                $old_status = $pp->product_stock_status;
                if(strtolower($old_status) != strtolower($post['product_stock_status'][$key])){
                    $create = ProductStockStatusUpdate::create([
                        'id_product' => $post['id_product'],
                        'id_user' => $request->user()->id,
                        'user_type' => 'users',
                        'id_outlet' => $post['id_outlet'][$key],
                        'date_time' => $date_time,
                        'new_status' => $post['product_stock_status'][$key],
                        'id_outlet_app_otp' => null
                    ]);
                }
				$update = ProductPrice::where('id_product_price','=',$id_product_price)->update(['product_price' => $post['product_price'][$key], 'product_price_base' => $post['product_price_base'][$key], 'product_price_tax' => $post['product_price_tax'][$key],'product_stock_status' => $post['product_stock_status'][$key],'product_visibility' => $post['product_visibility'][$key]]);
			}
		}
		return response()->json(MyHelper::checkUpdate($update));
	}

    public function updateProductDetail(Request $request) {
        $post = $request->json()->all();
        $date_time = date('Y-m-d H:i:s');
        foreach ($post['id_product_detail'] as $key => $id_product_detail) {
            if($id_product_detail == 0){
                $update = ProductDetail::create(['id_product' => $post['id_product'],
                    'id_outlet' => $post['id_outlet'][$key],
                    'product_detail_stock_status' => 'Sold Out',
                    'product_detail_visibility' => $post['product_detail_visibility'][$key]
                ]);
                $create = ProductStockStatusUpdate::create([
                    'id_product' => $post['id_product'],
                    'id_user' => $request->user()->id,
                    'user_type' => 'users',
                    'id_outlet' => $post['id_outlet'][$key],
                    'date_time' => $date_time,
                    'new_status' => 'Sold Out',
                    'id_outlet_app_otp' => null
                ]);
            }
            else{
                $pp = ProductDetail::where('id_product_detail','=',$id_product_detail)->first();
                if(!$pp){continue;}
                $old_status = $pp->product_detail_stock_status;
                
                $update = ProductDetail::where('id_product_detail','=',$id_product_detail)->update(['product_detail_stock_status' => $old_status,'product_detail_visibility' => $post['product_detail_visibility'][$key]]);
            }
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function updatePriceDetail(Request $request) {
        $post = $request->json()->all();

        foreach ($post['id_product_special_price'] as $key => $id_product_special_price) {
            if($id_product_special_price == 0){
                if(!is_null($post['product_price'][$key])){
                    $update = ProductSpecialPrice::create(['id_product' => $post['id_product'],
                        'id_outlet' => $post['id_outlet'][$key],
                        'product_special_price' => str_replace(".","",$post['product_price'][$key])
                    ]);
                }
            }
            else{
                $pp = ProductSpecialPrice::where('id_product_special_price','=',$id_product_special_price)->first();
                if(!$pp){continue;}
                if(!is_null($post['product_price'][$key])) {
                    $update = ProductSpecialPrice::where('id_product_special_price', '=', $id_product_special_price)
                        ->update(['product_special_price' => str_replace(".","",$post['product_price'][$key])]);
                }
            }
        }
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function categoryAssign(Request $request) {
		$post = $request->json()->all();
		foreach ($post['id_product'] as $key => $idprod) {
            $count = BrandProduct::where('id_product',$idprod)->count();
			if($post['id_product_category'][$key] == 0){
				$update = Product::where('id_product','=',$idprod)->update(['id_product_category' => null, 'product_name' => $post['product_name'][$key]]);
                if($count){
                    BrandProduct::where(['id_product'=>$idprod])->update(['id_product_category' => null]);
                }else{
                    BrandProduct::create(['id_product'=>$idprod,'id_product_category' => null]);
                }
			}else{
				$update = Product::where('id_product','=',$idprod)->update(['id_product_category' => $post['id_product_category'][$key], 'product_name' => $post['product_name'][$key]]);
                if($count){
                    BrandProduct::where(['id_product'=>$idprod])->update(['id_product_category' => $post['id_product_category'][$key]]);
                }else{
                    BrandProduct::create(['id_product'=>$idprod,'id_product_category' => $post['id_product_category'][$key]]);
                }
            }
		}
		return response()->json(MyHelper::checkUpdate($update));
	}

    /**
     * Export data product
     * @param Request $request Laravel Request Object
     */
    public function import(Request $request) {
        $post = $request->json()->all();
        $result = [
            'processed' => 0,
            'invalid' => 0,
            'updated' => 0,
            'updated_price' => 0,
            'updated_price_fail' => 0,
            'create' => 0,
            'create_category' => 0,
            'no_update' => 0,
            'failed' => 0,
            'not_found' => 0,
            'more_msg' => [],
            'more_msg_extended' => []
        ];
        switch ($post['type']) {
            case 'global':
                // update or create if not exist
                $data = $post['data']??[];
                $check_brand = Brand::where(['id_brand'=>$post['id_brand'],'code_brand'=>$data['code_brand']??''])->exists();
                if($check_brand){
                    foreach ($data['products'] as $key => $value) {
                        if(empty($value['product_code'])){
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if(empty($value['product_name'])){
                            unset($value['product_name']);
                        }
                        if(empty($value['product_description'])){
                            unset($value['product_description']);
                        }
                        $product = Product::where('product_code',$value['product_code'])->first();
                        if($product){
                            if($product->update($value)){
                                $result['updated']++;
                            }else{
                                $result['no_update']++;
                            }
                        }else{
                            $product = Product::create($value);
                            if($product){
                                $result['create']++;
                            }else{
                                $result['failed']++;
                                $result['more_msg_extended'][] = "Product with product code {$value['product_code']} failed to be created";
                                continue;
                            }
                        }
                        $update = BrandProduct::updateOrCreate([
                            'id_brand'=>$post['id_brand'],
                            'id_product'=>$product->id_product
                        ]);
                    }
                }else{
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'detail':
                // update only, never create
                $data = $post['data']??[];
                $check_brand = Brand::where(['id_brand'=>$post['id_brand'],'code_brand'=>$data['code_brand']??''])->first();
                if($check_brand){
                    foreach ($data['products'] as $key => $value) {
                        if(empty($value['product_code'])){
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if(empty($value['product_name'])){
                            unset($value['product_name']);
                        }
                        if(empty($value['product_description'])){
                            unset($value['product_description']);
                        }
                        if(empty($value['position'])){
                            unset($value['position']);
                        }
                        if(empty($value['product_visibility'])){
                            unset($value['product_visibility']);
                        }
                        $product = Product::join('brand_product','products.id_product','=','brand_product.id_product')
                            ->where([
                                'id_brand' => $check_brand->id_brand,
                                'product_code' => $value['product_code']
                            ])->first();
                        if(!$product){
                            $result['not_found']++;
                            $result['more_msg_extended'][] = "Product with product code {$value['product_code']} in selected brand not found";
                            continue;
                        }
                        if(empty($value['product_category_name'])){
                            unset($value['product_category_name']);
                        }else{
                            $pc = ProductCategory::where('product_category_name',$value['product_category_name'])->first();
                            if(!$pc){
                                $result['create_category']++;
                                $pc = ProductCategory::create([
                                    'product_category_name' => $value['product_category_name']
                                ]);
                            }
                            $value['id_product_category'] = $pc->id_product_category;
                            unset($value['product_category_name']);
                        }
                        $update1 = $product->update($value);
                        if($value['id_product_category']??false){
                            $update2 = BrandProduct::where('id_product',$product->id_product)->update(['id_product_category'=>$value['id_product_category']]);
                        }
                        if($update1 || $update2){
                            $result['updated']++;
                        }else{
                            $result['no_update']++;
                        }
                    }
                }else{
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'price':
                // update only, never create
                $data = $post['data']??[];
                $check_brand = Brand::where(['id_brand'=>$post['id_brand'],'code_brand'=>$data['code_brand']??''])->first();
                if($check_brand){
                    $global_outlets = Outlet::select('id_outlet','outlet_code')->where([
                        'outlet_different_price' => 0
                    ])->get();
                    foreach ($data['products'] as $key => $value) {
                        if(empty($value['product_code'])){
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if(empty($value['product_name'])){
                            unset($value['product_name']);
                        }
                        if(empty($value['product_description'])){
                            unset($value['product_description']);
                        }
                        if(empty($value['global_price'])){
                            unset($value['global_price']);
                        }
                        $product = Product::join('brand_product','products.id_product','=','brand_product.id_product')
                            ->where([
                                'id_brand' => $check_brand->id_brand,
                                'product_code' => $value['product_code']
                            ])->first();
                        if(!$product){
                            $result['not_found']++;
                            $result['more_msg_extended'][] = "Product with product code {$value['product_code']} in selected brand not found";
                            continue;
                        }
                        $update1 = $product->update($value);
                        if($update1){
                            $result['updated']++;
                        }else{
                            $result['no_update']++;
                        }
                        if($value['global_price']??false){
                            foreach ($global_outlets as $outlet) {
                                $pp = ProductGlobalPrice::where([
                                    'id_product' => $product->id_product
                                ])->first();
                                if($pp){
                                    $update = $pp->update(['product_global_price'=>$value['global_price']]);
                                }else{
                                    $update = ProductGlobalPrice::create([
                                        'id_product' => $product->id_product,
                                        'product_global_price'=>$value['global_price']
                                    ]);
                                }
                                if($update){
                                    $result['updated_price']++;
                                }else{
                                    if($update !== 0){
                                        $result['updated_price_fail']++;
                                        $result['more_msg_extended'][] = "Failed set price for product {$value['product_code']} at outlet {$outlet->outlet_code} failed";
                                    }
                                }
                            }
                        }
                        foreach ($value as $col_name => $col_value) {
                            if(!$col_value){
                                continue;
                            }
                            if(strpos($col_name, 'price_') !== false){
                                $outlet_code = str_replace('price_', '', $col_name);
                                $pp = ProductSpecialPrice::join('outlets','outlets.id_outlet','=','product_special_price.id_outlet')
                                ->where([
                                    'outlet_code' => $outlet_code,
                                    'id_product' => $product->id_product
                                ])->first();
                                if($pp){
                                    $update = $pp->update(['product_special_price'=>$col_value]);
                                }else{
                                    $id_outlet = Outlet::select('id_outlet')->where('outlet_code',$outlet_code)->pluck('id_outlet')->first();
                                    if(!$id_outlet){
                                        $result['updated_price_fail']++;
                                        $result['more_msg_extended'][] = "Failed create new price for product {$value['product_code']} at outlet $outlet_code failed";
                                        continue;
                                    }
                                    $update = ProductSpecialPrice::create([
                                        'id_outlet' => $id_outlet,
                                        'id_product' => $product->id_product,
                                        'product_special_price'=>$col_value
                                    ]);
                                }
                                if($update){
                                    $result['updated_price']++;
                                }else{
                                    $result['updated_price_fail']++;
                                    $result['more_msg_extended'][] = "Failed set price for product {$value['product_code']} at outlet $outlet_code failed";
                                }
                            }
                        }
                    }
                }else{
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'modifier-price':
                // update only, never create
                $data = $post['data']??[];
                $check_brand = Brand::where(['id_brand'=>$post['id_brand'],'code_brand'=>$data['code_brand']??''])->first();
                if($check_brand){
                    $global_outlets = Outlet::select('id_outlet','outlet_code')->where([
                        'outlet_different_price' => 0
                    ])->get();
                    foreach ($data['products'] as $key => $value) {
                        if(empty($value['code'])){
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if(empty($value['name'])){
                            unset($value['name']);
                        }else{
                            $value['text'] = $value['name'];
                            unset($value['name']);
                        }
                        if(empty($value['type'])){
                            unset($value['type']);
                        }
                        if(empty($value['global_price'])){
                            unset($value['global_price']);
                        }
                        $product = ProductModifier::select('product_modifiers.*')->leftJoin('product_modifier_brands','product_modifiers.id_product_modifier','=','product_modifier_brands.id_product_modifier')
                            ->where('code',$value['code'])->where(function($q) use ($post) {
                                $q->where('id_brand',$post['id_brand'])->orWhere('modifier_type','<>','Global Brand');
                            })->first();
                        if(!$product){
                            $result['not_found']++;
                            $result['more_msg_extended'][] = "Product modifier with code {$value['code']} in selected brand not found";
                            continue;
                        }
                        $update1 = $product->update($value);
                        if($update1){
                            $result['updated']++;
                        }else{
                            $result['no_update']++;
                        }
                        if($value['global_price']??false){
                            $update = ProductModifierGlobalPrice::updateOrCreate([
                                'id_product_modifier' => $product->id_product_modifier],[
                                'product_modifier_price'=>$value['global_price']
                            ]);
                            if($update){
                                $result['updated_price']++;
                            }else{
                                if($update !== 0){
                                    $result['updated_price_fail']++;
                                    $result['more_msg_extended'][] = "Failed set global price for product modifier {$value['code']}";
                                }
                            }
                        }
                        foreach ($value as $col_name => $col_value) {
                            if(!$col_value){
                                continue;
                            }
                            if(strpos($col_name, 'price_') !== false){
                                $outlet_code = str_replace('price_', '', $col_name);
                                $pp = ProductModifierPrice::join('outlets','outlets.id_outlet','=','product_modifier_prices.id_outlet')
                                ->where([
                                    'outlet_code' => $outlet_code,
                                    'id_product_modifier' => $product->id_product_modifier
                                ])->first();
                                if($pp){
                                    $update = $pp->update(['product_modifier_price'=>$col_value]);
                                }else{
                                    $id_outlet = Outlet::select('id_outlet')->where('outlet_code',$outlet_code)->pluck('id_outlet')->first();
                                    if(!$id_outlet){
                                        $result['updated_price_fail']++;
                                        $result['more_msg_extended'][] = "Failed create new price for product modifier {$value['code']} at outlet $outlet_code failed";
                                        continue;
                                    }
                                    $update = ProductModifierPrice::create([
                                        'id_outlet' => $id_outlet,
                                        'id_product_modifier' => $product->id_product_modifier,
                                        'product_modifier_price'=>$col_value
                                    ]);
                                }
                                if($update){
                                    $result['updated_price']++;
                                }else{
                                    $result['updated_price_fail']++;
                                    $result['more_msg_extended'][] = "Failed set price for product modifier {$value['code']} at outlet $outlet_code failed";
                                }
                            }
                        }
                    }
                }else{
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product modifier\'s brand does not match with selected brand']
                    ];
                }
                break;

            case 'modifier':
                // update only, never create
                $data = $post['data']??[];
                $check_brand = Brand::where(['id_brand'=>$post['id_brand'],'code_brand'=>$data['code_brand']??''])->first();
                if($check_brand){
                    foreach ($data['products'] as $key => $value) {
                        if(empty($value['code'])){
                            $result['invalid']++;
                            continue;
                        }
                        $result['processed']++;
                        if(empty($value['name'])){
                            unset($value['name']);
                        }else{
                            $value['text'] = $value['name'];
                            unset($value['name']);
                        }
                        if(empty($value['type'])){
                            unset($value['type']);
                        }
                        $product = ProductModifier::select('product_modifiers.*')->leftJoin('product_modifier_brands','product_modifiers.id_product_modifier','=','product_modifier_brands.id_product_modifier')
                            ->where('code',$value['code'])->where(function($q) use ($post) {
                                $q->where('id_brand',$post['id_brand'])->orWhere('modifier_type','<>','Global Brand');
                            })->first();
                        if(!$product){
                            $value['modifier_type'] = 'Global Brand';
                            $product = ProductModifier::create($value);
                            if($product){
                                ProductModifierBrand::create(['id_product_modifier'=>$product->id_product_modifier,'id_brand'=>$post['id_brand']]);
                                $result['create']++;
                            }else{
                                $result['failed']++;
                                $result['more_msg_extended'][] = "Product modifier with code {$value['code']} failed to be created";
                            }
                            continue;
                        }
                        $update1 = $product->update($value);
                        if($product->modifier_type == 'Global Brand'){
                            ProductModifierBrand::updateOrCreate(['id_product_modifier'=>$product->id_product_modifier,'id_brand'=>$post['id_brand']]);
                        }
                        if($update1){
                            $result['updated']++;
                        }else{
                            $result['no_update']++;
                        }
                    }
                }else{
                    return [
                        'status' => 'fail',
                        'messages' => ['Imported product modifier\'s brand does not match with selected brand']
                    ];
                }
                break;

            default:
                # code...
                break;
        }
        $response = [];
        if($result['invalid']+$result['processed']<=0){
            return MyHelper::checkGet([],'File empty');
        }else{
            $response[] = $result['invalid']+$result['processed'].' total data found';
        }
        if($result['processed']){
            $response[] = $result['processed'].' data processed';
        }
        if($result['updated']){
            $response[] = 'Update '.$result['updated'].' product';
        }
        if($result['create']){
            $response[] = 'Create '.$result['create'].' new product';
        }
        if($result['create_category']){
            $response[] = 'Create '.$result['create_category'].' new category';
        }
        if($result['no_update']){
            $response[] = $result['no_update'].' product not updated';
        }
        if($result['invalid']){
            $response[] = $result['invalid'].' row data invalid';
        }
        if($result['failed']){
            $response[] = 'Failed create '.$result['failed'].' product';
        }
        if($result['not_found']){
            $response[] = $result['not_found'].' product not found';
        }
        if($result['updated_price']){
            $response[] = 'Update '.$result['updated_price'].' product price';
        }
        if($result['updated_price_fail']){
            $response[] = 'Update '.$result['updated_price_fail'].' product price fail';
        }
        $response = array_merge($response,$result['more_msg_extended']);
        return MyHelper::checkGet($response);
    }

    /**
     * Export data product
     * @param Request $request Laravel Request Object
     */
    public function export(Request $request) {
        $post = $request->json()->all();
        switch ($post['type']) {
            case 'global':
                $data['brand'] = Brand::where('id_brand',$post['id_brand'])->first();
                $data['products'] = Product::select('product_code','product_name','product_description')
                    ->join('brand_product','brand_product.id_product','=','products.id_product')
                    ->where('id_brand',$post['id_brand'])
                    ->where('product_type', 'product')
                    ->groupBy('products.id_product')
                    ->orderBy('position')
                    ->orderBy('products.id_product')
                    ->distinct()
                    ->get();
                break;

            case 'detail':
                $data['brand'] = Brand::where('id_brand',$post['id_brand'])->first();
                $data['products'] = Product::select('product_categories.product_category_name','products.position','product_code','product_name','product_description','products.product_visibility')
                    ->join('brand_product','brand_product.id_product','=','products.id_product')
                    ->where('id_brand',$post['id_brand'])
                    ->where('product_type', 'product')
                    ->leftJoin('product_categories','product_categories.id_product_category','=','brand_product.id_product_category')
                    ->groupBy('products.id_product')
                    ->groupBy('product_category_name')
                    ->orderBy('product_category_name')
                    ->orderBy('position')
                    ->orderBy('products.id_product')
                    ->distinct()
                    ->get();
                break;

            case 'price':
                $different_outlet = Outlet::select('outlet_code','id_product','product_special_price.product_special_price as product_price')
                    ->leftJoin('product_special_price','outlets.id_outlet','=','product_special_price.id_outlet')
                    ->where('outlet_different_price',1)->get();
                $do = MyHelper::groupIt($different_outlet,'outlet_code',null,function($key,&$val){
                    $val = MyHelper::groupIt($val,'id_product');
                    return $key;
                });
                $data['brand'] = Brand::where('id_brand',$post['id_brand'])->first();
                $data['products'] = Product::select('products.id_product','product_code','product_name','product_description','product_global_price.product_global_price as global_price')
                    ->join('brand_product','brand_product.id_product','=','products.id_product')
                    ->leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                    ->where('id_brand',$post['id_brand'])
                    ->where('product_type', 'product')
                    ->orderBy('position')
                    ->orderBy('products.id_product')
                    ->distinct()
                    ->get();
                foreach ($data['products'] as $key => &$product) {
                    $inc = 0;
                    foreach ($do as $outlet_code => $x) {
                        $inc++;
                        $product['price_'.$outlet_code] = $x[$product['id_product']][0]['product_price']??'';
                        if($inc === count($do)){
                            unset($product['id_product']);
                        }
                    }
                }
                break;

            case 'modifier-price':
                $subquery = str_replace('?','0',ProductModifierGlobalPrice::select(\DB::raw('id_product_modifier,product_modifier_price as global_price'))
                    ->groupBy('id_product_modifier')
                    ->toSql());
                $different_outlet = Outlet::select('outlet_code','id_product_modifier','product_modifier_price')
                    ->leftJoin('product_modifier_prices','outlets.id_outlet','=','product_modifier_prices.id_outlet')
                    ->where('outlet_different_price',1)->get();
                $do = MyHelper::groupIt($different_outlet,'outlet_code',null,function($key,&$val){
                    $val = MyHelper::groupIt($val,'id_product_modifier');
                    return $key;
                });
                $data['brand'] = Brand::where('id_brand',$post['id_brand'])->first();
                $data['products'] = ProductModifier::select('product_modifiers.id_product_modifier','type','code','text as name','global_prices.global_price')
                    ->leftJoin('product_modifier_brands','product_modifier_brands.id_product_modifier','=','product_modifiers.id_product_modifier')
                    ->leftJoin(DB::raw('('.$subquery.') as global_prices'),'product_modifiers.id_product_modifier','=','global_prices.id_product_modifier')
                    ->whereNotIn('type', ['Modifier Group'])
                    ->where(function($q) use ($post){
                        $q->where('id_brand',$post['id_brand'])
                            ->orWhere('modifier_type','<>','Global Brand');
                    })
                    ->orderBy('type')
                    ->orderBy('text')
                    ->orderBy('product_modifiers.id_product_modifier')
                    ->distinct()
                    ->get();
                foreach ($data['products'] as $key => &$product) {
                    $inc = 0;
                    foreach ($do as $outlet_code => $x) {
                        $inc++;
                        $product['price_'.$outlet_code] = $x[$product['id_product_modifier']][0]['product_modifier_price']??'';
                    }
                    unset($product['id_product_modifier']);
                }
                break;

            case 'modifier':
                $data['brand'] = Brand::where('id_brand',$post['id_brand'])->first();
                $data['products'] = ProductModifier::select('type','code','text as name')
                    ->leftJoin('product_modifier_brands','product_modifier_brands.id_product_modifier','=','product_modifiers.id_product_modifier')
                    ->whereNotIn('type', ['Modifier Group'])
                    ->where(function($q) use ($post){
                        $q->where('id_brand',$post['id_brand'])
                            ->orWhere('modifier_type','<>','Global Brand');
                    })
                    ->orderBy('type')
                    ->orderBy('text')
                    ->orderBy('product_modifiers.id_product_modifier')
                    ->distinct()
                    ->get();
                break;

            default:
                # code...
                break;
        }
        return MyHelper::checkGet($data);
    }

    /* Pengecekan code unique */
    function cekUnique($id, $code) {
        $cek = Product::where('product_code', $code)->first();

        if (empty($cek)) {
            return true;
        }
        else {
            if ($cek->id_product == $id) {
                return true;
            }
            else {
                return false;
            }
        }
    }

    /**
     * list product
     */
    function listProduct(Request $request) {
        $post = $request->json()->all();

		if (isset($post['id_outlet'])) {
            $product = Product::join('product_detail','product_detail.id_product','=','products.id_product')
                                ->leftJoin('product_special_price','product_special_price.id_product','=','products.id_product')
									->where('product_detail.id_outlet','=',$post['id_outlet'])
									->where('product_detail.product_detail_visibility','=','Visible')
                                    ->where('product_detail.product_detail_status','=','Active')
                                    
                                    ->with(['category', 'discount']);

            if (isset($post['visibility'])) {

                if($post['visibility'] == 'Hidden'){
                    $product = Product::join('product_detail','product_detail.id_product','=','products.id_product')
                        ->where('product_detail.id_outlet','=',$post['id_outlet'])
                        ->where('product_detail.product_detail_visibility','=','Hidden')
                        ->with(['category', 'discount']);
                }else{
                    $ids = Product::join('product_detail','product_detail.id_product','=','products.id_product')
                        ->where('product_detail.id_outlet','=',$post['id_outlet'])
                        ->where('product_detail.product_detail_visibility','=','Hidden')
                        ->pluck('products.id_product')->toArray();
                    $product = Product::whereNotIn('id_product', $ids)
                        ->with(['category', 'discount']);
                }

                unset($post['id_outlet']);
            }
		} else {
		    if(isset($post['product_setting_type']) && $post['product_setting_type'] == 'product_price'){
                $product = Product::with(['category', 'discount', 'product_special_price', 'global_price']);
            }elseif(isset($post['product_setting_type']) && $post['product_setting_type'] == 'outlet_product_detail'){
                $product = Product::with(['category', 'discount', 'product_detail']);
            }else{
                $product = Product::with(['category', 'discount','product_icount_use_ima' => function($ima){$ima->where('company_type','ima');},'product_icount_use_ims' => function($ims){$ims->where('company_type','ims');}]);
            }

            if ($post['outlet_id'] ?? false) {
                $outletBrand = BrandOutlet::where('id_outlet', $post['outlet_id'])->pluck('id_brand');
                $productsId = BrandProduct::whereIn('id_brand', $outletBrand)->pluck('id_product');
                $product->whereIn('id_product', $productsId);
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
            $product->with(['global_price','product_special_price','product_tags','brands','product_promo_categories'=>function($q){$q->select('product_promo_categories.id_product_promo_category');},'product_detail'=>function($detail){
                $detail->join('outlets','outlets.id_outlet','=', 'product_detail.id_outlet');
                $detail->groupBy('product_detail.id_outlet');
                $detail->SelectRaw( 'id_product,
                                    product_detail_stock_item,
                                    product_detail_stock_status,
                                    product_detail.id_outlet,
                                    outlet_name');
            }])->where('products.product_code', $post['product_code']);
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
            $product = $product->orderBy('position');
        }

        if(isset($post['admin_list'])){
            $product = $product->withCount('product_detail')->withCount('product_detail_hiddens')->with(['brands']);
        }

        if(isset($post['product_type'])){
            $product = $product->where('product_type', $post['product_type']);
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

    function listProductImage(Request $request) {
        $post = $request->json()->all();

        if (isset($post['image']) && $post['image'] == 'null') {
            $product = Product::leftJoin('product_photos','product_photos.id_product','=','products.id_product')
                            ->whereNull('product_photos.product_photo')->get();
        } else {
            $product = Product::get();
            if (!empty($product)) {
                foreach ($product as $key => $value) {
                    unset($product[$key]['product_price_base']);
                    unset($product[$key]['product_price_tax']);
                    $product[$key]['photos'] = ProductPhoto::select('*', DB::raw('if(product_photo is not null, (select concat("'.config('url.storage_url_api').'", product_photo)), "'.config('url.storage_url_api').'img/default.jpg") as url_product_photo'))->where('id_product', $value['id_product'])->orderBy('product_photo_order', 'ASC')->get()->toArray();
                }
            }
        }

        $product = $product->toArray();

        return response()->json(MyHelper::checkGet($product));
    }

    function imageOverride(Request $request) {
        $post = $request->json()->all();

        if (isset($post['status'])) {
            try {
                Setting::where('key', 'image_override')->update(['value' => $post['status']]);
                return response()->json(MyHelper::checkGet('true'));
            } catch (\Exception $e) {
                return response()->json(MyHelper::checkGet($e));
            }
        }

        $setting = Setting::where('key', 'image_override')->first();

        if (!$setting) {
            Setting::create([
                'key'       => 'image_override',
                'value'     => 0
            ]);

            $setting = 'false';
        } else {
            if ($setting->value == 0) {
                $setting = 'false';
            } else {
                $setting = 'true';
            }
        }

        return response()->json(MyHelper::checkGet($setting));
    }

    /**
     * create  product
     */
    function create(Create $request) {
        $post = $request->json()->all();

        // check data
        $data = $this->checkInputProduct($post, $type="create");
        // return $data;
        $save = Product::create($data);

		if($save){
			$listOutlet = Outlet::get()->toArray();
			foreach($listOutlet as $outlet){
				$dataPrice = [];
				$dataPrice['id_product'] = $save->id_product;
				$dataPrice['id_outlet'] = $outlet['id_outlet'];
				$dataPrice['product_price'] = null;
				// $data['product_visibility'] = 'Visible';

                ProductPrice::create($dataPrice);
            }

            if(is_array($brands=$data['product_brands']??false)){
                foreach ($brands as $id_brand) {
                    BrandProduct::create([
                        'id_product'=>$save['id_product'],
                        'id_brand'=>$id_brand,
                        'id_product_category' => $data['id_product_category']
                    ]);
                }
            }

            //create photo
            if(isset($post['photo'])){

                $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300);

                if (isset($upload['status']) && $upload['status'] == "success") {
                    $dataPhoto['product_photo'] = $upload['path'];
                }
                else {
                    $result = [
                        'status'   => 'fail',
                        'messages' => ['fail upload image']
                    ];

                    return response()->json($result);
                }

                $dataPhoto['id_product']          = $save->id_product;
                $dataPhoto['product_photo_order'] = $this->cekUrutanPhoto($save['id_product']);
                $save                             = ProductPhoto::create($dataPhoto);
            }
            if(isset($post['product_global_price'])){
                ProductGlobalPrice::updateOrCreate(['id_product' => $save['id_product']],
                    ['product_global_price' => str_replace(".","",$post['product_global_price'])]);
            }

		}

        return response()->json(MyHelper::checkCreate($save));
    }

    /**
     * update product
     */
    function update(Update $request) {
    	$post = $request->json()->all();
    	// check data
        DB::beginTransaction();
        if(!empty($post['product_brands'])){
            $brands=$post['product_brands']??false;
            if(!$brands){
                $brands = ['0'];
                $post['product_brands'] = ['0'];
            }
            if(in_array('*', $post['product_brands'])){
                $brands=Brand::select('id_brand')->get()->toArray();
                $brands=array_column($brands, 'id_brand');
            }
            BrandProduct::where('id_product',$request->json('id_product'))->delete();
            foreach ($brands as $id_brand) {
                BrandProduct::create([
                    'id_product'=>$request->json('id_product'),
                    'id_brand'=>$id_brand,
                    'id_product_category'=>$request->json('id_product_category')
                ]);
            }
        }
        unset($post['product_brands']);

        if(!empty($post['product_icount_ima'])){
            $product_use = [
                "product_icount" => $post['product_icount_ima'],
                "id_product" => $post['id_product'],
                "company_type" => 'ima'
            ];
            $store_icount = app('\Modules\Product\Http\Controllers\ApiProductProductIcountController')->update(New Request($product_use));
            unset($post['product_icount_ima']);
        }

        if(!empty($post['product_icount_ims'])){
            $product_use = [
                "product_icount" => $post['product_icount_ims'],
                "id_product" => $post['id_product'],
                "company_type" => 'ims'
            ];
            $store_icount = app('\Modules\Product\Http\Controllers\ApiProductProductIcountController')->update(New Request($product_use));
            unset($post['product_icount_ima']);
        }

        if(!empty($post['product_hs_category'])){
            ProductHairstylistCategory::where('id_product', $post['id_product'])->delete();
            $insertProductHsCategory = [];
            foreach ($post['product_hs_category'] as $hsCat){
                $insertProductHsCategory[] = [
                    "id_product" => $post['id_product'],
                    'id_hairstylist_category' => $hsCat
                ];
            }
            ProductHairstylistCategory::insert($insertProductHsCategory);
            unset($post['product_hs_category']);
        }

        // promo_category
        ProductProductPromoCategory::where('id_product',$post['id_product'])->delete();
        ProductProductPromoCategory::insert(array_map(function($id_product_promo_category) use ($post) {
            return [
                'id_product' =>$post['id_product'],
                'id_product_promo_category' => $id_product_promo_category
            ];
        },$post['id_product_promo_category']??[]));
        unset($post['id_product_promo_category']);

    	$data = $this->checkInputProduct($post);

        if (isset($post['product_code'])) {
            if (!$this->cekUnique($post['id_product'], $post['product_code'])) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['product code already used.']
                ]);
            }
        }

    	$save = Product::where('id_product', $post['id_product'])->update($data);

    	if($save){
            if(isset($post['photo'])){
                //delete all photo
                $delete = $this->deletePhoto($post['id_product']);


                    //create photo
                    $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300);

                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $dataPhoto['product_photo'] = $upload['path'];
                    }
                    else {
                        $result = [
                            'status'   => 'fail',
                            'messages' => ['fail upload image']
                        ];

                        return response()->json($result);
                    }

                    $dataPhoto['id_product']          = $post['id_product'];
                    $dataPhoto['product_photo_order'] = $this->cekUrutanPhoto($post['id_product']);
                    $save                        = ProductPhoto::create($dataPhoto);


            }

            if(isset($post['product_global_price']) && !empty($post['product_global_price'])){
                if(strpos($post['product_global_price'], '.') === false){
                    $globalPrice = str_replace(",","",$post['product_global_price']);
                }else{
                    $globalPrice = str_replace(".","",$post['product_global_price']);
                }

                ProductGlobalPrice::updateOrCreate(['id_product' => $post['id_product']],
                    ['product_global_price' => (int)$globalPrice]);
            }
        }
        if($save){
            DB::commit();
        }else{
            DB::rollBack();
        }


    	return response()->json(MyHelper::checkUpdate($save));
    }

    /**
     * delete product
     */
    function delete(Delete $request) {
        $product = Product::with('prices')->find($request->json('id_product'));

    	$check = $this->checkDeleteProduct($request->json('id_product'));

    	if ($check) {
    		// delete photo
    		$deletePhoto = $this->deletePhoto($request->json('id_product'));

    		// delete product
    		$delete = Product::where('id_product', $request->json('id_product'))->delete();

            if($delete){
                $result = [
                    'status' => 'success',
                    'product' => [
                        'id_product' => $product['id_product'],
                        'plu_id' => $product['product_code'],
                        'product_name' => $product['product_name'],
                        'product_name_pos' => $product['product_name_pos'],
                        'product_prices' => $product['prices'],
                    ],
                ];
            }
			else{
                $result = ['status' => 'fail', 'messages' => ['failed to delete data']];
            }

    		return response()->json($result);

    	}
    	else {
    		return response()->json([
				'status'   => 'fail',
				'messages' => ['product has been used.']
    		]);
    	}

    }

    /**
     * delete photo product
     */
    function deletePhoto($id) {
        // info photo
        $dataPhoto = ProductPhoto::where('id_product', $id)->get()->toArray();

        if (!empty($dataPhoto)) {
            foreach ($dataPhoto as $key => $value) {
                MyHelper::deletePhoto($value['product_photo']);
            }
        }

    	$delete = ProductPhoto::where('id_product', $id)->delete();

    	return $delete;
    }

    /**
     * checking delete
     */
    function checkDeleteProduct($id) {

    	// jika true semua maka boleh dihapus
    	if ( ($this->checkAtNews($id)) && ($this->checkAtTrx($id)) && $this->checkAtDiskon($id)) {
    		return true;
    	}
    	// klo ada yang sudah digunakan
    	else {
    		return false;
    	}
    }

    // check produk di transaksi
    function checkAtTrx($id) {
    	$check = TransactionProduct::where('id_product', $id)->count();

    	if ($check > 0) {
    		return false;
    	}
    	else {
    		return true;
    	}
    }

    // check product di diskon
    function checkAtDiskon($id) {
    	$check = ProductDiscount::where('id_product', $id)->count();

    	if ($check > 0) {
    		return false;
    	}
    	else {
    		return true;
    	}
    }

    // check product di news
    function checkAtNews($id) {
    	$check = NewsProduct::where('id_product', $id)->count();

    	if ($check > 0) {
    		return false;
    	}
    	else {
    		return true;
    	}
    }

    /**
     * upload photo
     */
    function uploadPhotoProduct(UploadPhoto $request) {
    	$post = $request->json()->all();

    	$data = [];

    	if (isset($post['photo'])) {

    	    $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300);

    	    if (isset($upload['status']) && $upload['status'] == "success") {
    	        $data['product_photo'] = $upload['path'];
    	    }
    	    else {
    	        $result = [
    	            'status'   => 'fail',
    	            'messages' => ['fail upload image']
    	        ];

    	        return response()->json($result);
    	    }
    	}

    	if (empty($data)) {
    		return reponse()->json([
    			'status' => 'fail',
    			'messages' => ['fail save to database']
    		]);
    	}
    	else {
            $data['id_product']          = $post['id_product'];
            $data['product_photo_order'] = $this->cekUrutanPhoto($post['id_product']);
            $save                        = ProductPhoto::create($data);

    		return response()->json(MyHelper::checkCreate($save));
    	}
    }

    function uploadPhotoProductAjax(Request $request) {
    	$post = $request->json()->all();
    	$data = [];
        $checkCode = Product::where('product_code', $post['name'])->first();
    	if ($checkCode) {
            $checkSetting = Setting::where('key', 'image_override')->first();
            if ($checkSetting['value'] == 1) {
                if(isset($post['detail'])){
                    if ($checkCode->product_photo_detail && file_exists($checkCode->product_photo_detail)) {
                        unlink($checkCode->product_photo_detail);
                    }
                }else{
                    $productPhoto = ProductPhoto::where('id_product', $checkCode->id_product)->first();
                    if (file_exists($productPhoto->product_photo)) {
                        unlink($productPhoto->product_photo);
                    }
                }
            }

            if(isset($post['detail'])){
                $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/product/item/detail/', 720, 360, $post['name'].'-'.strtotime("now"));
            }else{
                $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300, $post['name'].'-'.strtotime("now"));
            }

    	    if (isset($upload['status']) && $upload['status'] == "success") {
    	        $data['product_photo'] = $upload['path'];
    	    }
    	    else {
    	        $result = [
    	            'status'   => 'fail',
    	            'messages' => ['fail upload image']
    	        ];
    	        return response()->json($result);
    	    }
    	}
    	if (empty($data)) {
    		return reponse()->json([
    			'status' => 'fail',
    			'messages' => ['fail save to database']
    		]);
    	}
    	else {
            if(isset($post['detail'])){
                $save = Product::where('id_product', $checkCode->id_product)->update(['product_photo_detail' => $data['product_photo']]);
            }else{
                $data['id_product']          = $checkCode->id_product;
                $data['product_photo_order'] = $this->cekUrutanPhoto($checkCode->id_product);
                $save                        = ProductPhoto::updateOrCreate(['id_product' => $checkCode->id_product],$data);
            }
    		return response()->json(MyHelper::checkCreate($save));
    	}
    }

    /*
    cek urutan
    */
    function cekUrutanPhoto($id) {
        $cek = ProductPhoto::where('id_product', $id)->orderBy('product_photo_order', 'DESC')->first();

        if (empty($cek)) {
            $cek = 1;
        }
        else {
            $cek = $cek->product_photo_order + 1;
        }

        return $cek;
    }

    /**
     * update photo
     */
    function updatePhotoProduct(UpdatePhoto $request) {
        $update = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->update([
            'product_photo_order' => $request->json('product_photo_order')
        ]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
     * delete photo
     */
    function deletePhotoProduct(DeletePhoto $request) {
        // info photo
        $dataPhoto = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->get()->toArray();

        $delete    = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->delete();

        if (!empty($dataPhoto)) {
            MyHelper::deletePhoto($dataPhoto[0]['product_photo']);
        }

        return response()->json(MyHelper::checkDelete($delete));
    }

    /* harga */
    function productPrices(Request $request)
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['id_product'])) {
            $data['id_product'] = $post['id_product'];
        }

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        if($post['id_outlet'] == 0){
            if (isset($post['product_price'])) {
                $dataGlobalPrice['product_global_price'] = $post['product_price'];
            }
            $save = ProductGlobalPrice::updateOrCreate([
                'id_product' => $data['id_product']
            ], $dataGlobalPrice);
        }else{
            if (isset($post['product_price'])) {
                $dataSpecialPrice['product_special_price'] = $post['product_price'];
            }
            $save = ProductSpecialPrice::updateOrCreate([
                'id_product' => $data['id_product'],
                'id_outlet'  => $data['id_outlet']
            ], $dataSpecialPrice);
        }

        return response()->json(MyHelper::checkUpdate($save));
    }

    function allProductPrices(Request $request)
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        $getAllProduct = Product::where('product_type', 'product')->pluck('id_product');
        if($post['id_outlet'] == 0){
            if (isset($post['product_price'])) {
                $dataGlobalPrice['product_global_price'] = $post['product_price'];
            }
            foreach ($getAllProduct as $id_product){
                $save = ProductGlobalPrice::updateOrCreate([
                    'id_product' => $id_product
                ], $dataGlobalPrice);
            }
        }else{
            if (isset($post['product_price'])) {
                $dataSpecialPrice['product_special_price'] = $post['product_price'];
            }
            foreach ($getAllProduct as $id_product){
                $save = ProductSpecialPrice::updateOrCreate([
                    'id_product' => $id_product,
                    'id_outlet'  => $data['id_outlet']
                ], $dataSpecialPrice);
            }
        }

        return response()->json(MyHelper::checkUpdate($save));
    }


    function productDetail(Request $request)
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['id_product'])) {
            $data['id_product'] = $post['id_product'];
        }

        if (isset($post['product_visibility']) || $post['product_visibility'] == null) {
            if($post['product_visibility'] == null){
                $data['product_detail_visibility'] = 'Hidden';
            }else{
                $data['product_detail_visibility'] = $post['product_visibility'];
            }
        }

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        if (isset($post['product_stock_status'])) {
            $data['product_detail_stock_status'] = $post['product_stock_status'];
        }
        $product = ProductDetail::where([
            'id_product' => $data['id_product'],
            'id_outlet'  => $data['id_outlet']
        ])->first();

        if(($data['product_detail_stock_status']??false) && (($data['product_detail_stock_status']??false) != $product['product_detail_stock_status']??false)){
            $create = ProductStockStatusUpdate::create([
                'id_product' => $data['id_product'],
                'id_user' => $request->user()->id,
                'user_type' => 'users',
                'id_outlet' => $data['id_outlet'],
                'date_time' => date('Y-m-d H:i:s'),
                'new_status' => $data['product_detail_stock_status'],
                'id_outlet_app_otp' => null
            ]);
        }

        $save = ProductDetail::updateOrCreate([
            'id_product' => $data['id_product'],
            'id_outlet'  => $data['id_outlet']
        ], $data);

        return response()->json(MyHelper::checkUpdate($save));
    }

    function allProductDetail(Request $request)
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['product_visibility']) || $post['product_visibility'] == null) {
            if($post['product_visibility'] == null){
                $data['product_detail_visibility'] = 'Hidden';
            }else{
                $data['product_detail_visibility'] = $post['product_visibility'];
            }
        }

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        if (isset($post['product_stock_status'])) {
            $data['product_detail_stock_status'] = $post['product_stock_status'];
        }

        $getAllProduct = Product::where('product_type', 'plastic')->pluck('id_product');

        foreach ($getAllProduct as $id_product){
            $product = ProductDetail::where([
                'id_product' => $id_product,
                'id_outlet'  => $data['id_outlet']
            ])->first();

            if(($data['product_detail_stock_status']??false) && (($data['product_detail_stock_status']??false) != $product['product_detail_stock_status']??false)){
                $create = ProductStockStatusUpdate::create([
                    'id_product' => $id_product,
                    'id_user' => $request->user()->id,
                    'user_type' => 'users',
                    'id_outlet' => $data['id_outlet'],
                    'date_time' => date('Y-m-d H:i:s'),
                    'new_status' => $data['product_detail_stock_status'],
                    'id_outlet_app_otp' => null
                ]);
            }

            $save = ProductDetail::updateOrCreate([
                'id_product' => $id_product,
                'id_outlet'  => $data['id_outlet']
            ], $data);
        }

        return response()->json(MyHelper::checkUpdate($save));
    }

    function updateAllowSync(UpdateAllowSync $request) {
        $post = $request->json()->all();

        if($post['product_allow_sync'] == "true"){
            $allow = '1';
        }else{
            $allow = '0';
        }
    	$update = Product::where('id_product', $post['id_product'])->update(['product_allow_sync' => $allow]);

    	return response()->json(MyHelper::checkUpdate($update));
    }

    function visibility(Request $request)
    {
        $post = $request->json()->all();
        foreach ($post['id_visibility'] as $key => $value) {
            if($value){
                $id = explode('/', $value);
                $save = ProductDetail::updateOrCreate(['id_product' => $id[0], 'id_outlet' => $id[1]], ['product_detail_visibility' => $post['visibility']]);
                if(!$save){
                    return response()->json(MyHelper::checkUpdate($save));
                }
            }
        }

        return response()->json(MyHelper::checkUpdate($save));
    }


    /* product position */
    public function positionProductAssign(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['product_ids'])) {
            return [
                'status' => 'fail',
                'messages' => ['Product id is required']
            ];
        }
        // update position
        foreach ($post['product_ids'] as $key => $product_id) {
            $update = Product::find($product_id)->update(['position'=>$key+1]);
        }

        return ['status' => 'success'];
    }

    public function photoDefault(Request $request){
        $post = $request->json()->all();

        
        //product detail
        if(isset($post['photo_detail'])){
            if (!file_exists('img/product/item/detail')) {
                mkdir('img/product/item/detail', 0777, true);
            }
            $upload = MyHelper::uploadPhotoStrict($post['photo_detail'], 'img/product/item/detail/', 720, 360, 'default', '.png');
        }
        
        //product
        if(isset($post['photo'])){
            if (!file_exists('img/product/item/')) {
                mkdir('img/product/item/', 0777, true);
            }
            $upload = MyHelper::uploadPhotoStrict($post['photo'], 'img/product/item/', 300, 300, 'default', '.png');
        }

        if (isset($upload['status']) && $upload['status'] == "success") {
            $result = [
                'status'   => 'success',
            ];
        }
        else {
            $result = [
                'status'   => 'fail',
                'messages' => ['fail upload image']
            ];

        }
        return response()->json($result);
    }

    public function updateVisibility(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['id_product'])) {
            return [
                'status' => 'fail',
                'messages' => ['Id product is required']
            ];
        }
        if (!isset($post['product_visibility'])) {
            return [
                'status' => 'fail',
                'messages' => ['Product visibility is required']
            ];
        }
        // update visibility
        $update = Product::find($post['id_product'])->update(['product_visibility'=>$post['product_visibility']]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    function listProductPriceByOutlet(Request $request, $id_outlet) {
        $product = Product::with(['all_prices'=> function($q) use ($id_outlet){
            $q->where('id_outlet', $id_outlet);
        }])->get();
        return response()->json(MyHelper::checkGet($product));
    }

    function listProductDetailByOutlet(Request $request, $id_outlet){
        $outlet = Outlet::with('brand_outlets')->find($id_outlet);
        $product = Product::select('products.*')->distinct()->with(['product_detail_all'=> function($q) use ($outlet){
            $q->where('id_outlet', $outlet->id_outlet);
        }])
            ->join('brand_product', function ($join) use ($outlet) {
                $join->on('brand_product.id_product', 'products.id_product')
                    ->whereIn('brand_product.id_brand', $outlet->brands->pluck('id_brand'));
            })
            ->get();
        return response()->json(MyHelper::checkGet($product));
    }

    function getNextID($id){
        $product = Product::where('id_product', '>', $id)->orderBy('id_product')->first();
        return response()->json(MyHelper::checkGet($product));
    }
    public function detail(Request $request) {
        $post = $request->json()->all();
        $bearerToken = $request->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);

        if(!($post['id_outlet']??false) && empty($post['outlet_code'])){
            $post['id_outlet'] = Setting::where('key','default_outlet')->pluck('value')->first();
        }

        if(!empty($post['outlet_code'])){
            $outlet = Outlet::where('outlet_code', $post['outlet_code'])->first();
            $post['id_brand'] = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
                ->where('id_outlet', $outlet['id_outlet'])->first()['id_brand']??null;
        }else{
            $outlet = Outlet::find($post['id_outlet']);
        }

        if(!$outlet){
            return MyHelper::checkGet([],'Outlet not found');
        }

        $post['id_outlet'] = $outlet['id_outlet'];

        if(!empty($post['product_code'])){
            $post['id_product'] = Product::where('product_code', $post['product_code'])->first()['id_product']??null;
        }
        //get product
        $product = Product::select('id_product','product_code','product_name','product_description','product_code','product_visibility','product_photo_detail', 'product_variant_status')
        ->where('id_product',$post['id_product'])
        ->whereHas('brand_category')
        ->whereRaw('products.id_product in (CASE
                    WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                    is NULL THEN products.id_product
                    ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                END)')
        ->with(['brand_category'=>function($query) use ($post){
            $query->where('id_product',$post['id_product']);
            $query->where('id_brand',$post['id_brand']);
        }])
        ->first();
        if(!$product){
            return MyHelper::checkGet([]);
        }else{
            // toArray error jika $product Null,
            $product = $product->toArray();
            if($product['product_photo_detail']){
                $product['photo'] = config('url.storage_url_api').$product['product_photo_detail'];
            }else{
                $product['photo'] = config('url.storage_url_api').'img/product/item/detail/default.png';
            }
        }
        $product['product_detail'] = ProductDetail::where(['id_product' => $post['id_product'], 'id_outlet' => $post['id_outlet']])->first();

        if(empty($product['product_detail'])){
            $product['product_detail']['product_detail_visibility'] = $product['product_visibility'];
            $product['product_detail']['product_detail_status'] = 'Active';
        }
        $max_order = null;

        if(isset($product['product_detail']['max_order'])){
            $max_order = $product['product_detail']['max_order'];
        }
        if($max_order==null){
            $max_order = Outlet::select('max_order')->where('id_outlet',$post['id_outlet'])->pluck('max_order')->first();
            if($max_order == null){
                $max_order = Setting::select('value')->where('key','max_order')->pluck('value')->first();
                if($max_order == null){
                    $max_order = 100;
                }
            }
        }
        
        if(isset($product['product_detail']['product_detail_visibility']) && $product['product_detail']['product_detail_visibility']=='Hidden'){
            return MyHelper::checkGet([]);
        }
        unset($product['product_detail']);
        $post['id_product_category'] = $product['brand_category'][0]['id_product_category']??0;
        if($post['id_product_category'] === 0){
            return MyHelper::checkGet([]);
        }
        //get modifiers
        $product_modifiers = ProductModifier::select('product_modifiers.id_product_modifier','code','text','product_modifier_stock_status','product_modifier_price as price')
            ->where(function($query) use($post){
                $query->where('modifier_type','Global')
                ->orWhere(function($query) use ($post){
                    $query->whereHas('products',function($query) use ($post){
                        $query->where('products.id_product',$post['id_product']);
                    });
                    $query->orWhereHas('product_categories',function($query) use ($post){
                        $query->where('product_categories.id_product_category',$post['id_product_category']);
                    });
                    $query->orWhereHas('brands',function($query) use ($post){
                        $query->where('brands.id_brand',$post['id_brand']);
                    });
                });
            })
            ->leftJoin('product_modifier_details', function($join) use ($post) {
                $join->on('product_modifier_details.id_product_modifier','=','product_modifiers.id_product_modifier')
                    ->where('product_modifier_details.id_outlet',$post['id_outlet']);
            })
            ->where(function($q){
                $q->where('product_modifier_stock_status','Available')->orWhereNull('product_modifier_stock_status');
            })
            ->where(function($q){
                $q->where('product_modifier_status','Active')->orWhereNull('product_modifier_status');
            })
            ->where(function($query){
                $query->where('product_modifier_details.product_modifier_visibility','=','Visible')
                        ->orWhere(function($q){
                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                        });
            });

        $product['product_price'] = 0;
        if($outlet->outlet_different_price){
            $product_modifiers->join('product_modifier_prices',function($join) use ($post){
                $join->on('product_modifier_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                $join->where('product_modifier_prices.id_outlet',$post['id_outlet']);
            });

            $productSpecialPrice = ProductSpecialPrice::where('id_product',$post['id_product'])
                ->where('id_outlet',$post['id_outlet'])->first();
            if($productSpecialPrice){
                $product['product_price'] = $productSpecialPrice['product_special_price'];
            }
        }else{
            $product_modifiers->join('product_modifier_global_prices',function($join) use ($post){
                $join->on('product_modifier_global_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
            });

            $productGlobalPrice = ProductGlobalPrice::where('id_product',$post['id_product'])->first();
            if($productGlobalPrice){
                $product['product_price'] = $productGlobalPrice['product_global_price'];
            }
        }
        if(isset($post['id_bundling_product']) && !empty($post['id_bundling_product'])){
            $getProductBundling = BundlingProduct::where('id_bundling_product', $post['id_bundling_product'])->first();
            $product['variants'] = Product::getSingleVariantTree($product['id_product'], $getProductBundling['id_product_variant_group'], $outlet, false, $product['product_price'], $product['product_variant_status'])['variants_tree']??null;
        }else{
            $product['variants'] = Product::getVariantTree($product['id_product'], $outlet, false, $product['product_price'], $product['product_variant_status'])['variants_tree']??null;
        }
        if ($product['variants'] && $scopeUser != 'web-apps') {
            $appliedPromo = UserPromo::where('id_user', $request->user()->id)->first();
            if ($appliedPromo) {
                switch ($appliedPromo->promo_type) {
                    case 'deals':
                        $query = Deal::select('*', 'deals.id_deals as id_deals')->join('deals_vouchers', 'deals_vouchers.id_deals', 'deals.id_deals')
                            ->join('deals_users', 'deals_users.id_deals_voucher', 'deals_vouchers.id_deals_voucher')
                            ->where('id_deals_user', $appliedPromo->id_reference)
                            ->with([
                                'deals_product_discount_rules',
                                'deals_discount_bill_rules',
                                'deals_tier_discount_rules',
                                'deals_buyxgety_rules',
                                'deals_product_discount',
                                'deals_tier_discount_product',
                                'deals_buyxgety_product_requirement',
                                'deals_discount_bill_products'
                            ])
                            ->first();
                        if (!$query) {
                            goto skip;
                        }
                        break;

                    case 'promo_campaign':
                        $query = PromoCampaign::join('promo_campaign_promo_codes', 'promo_campaign_promo_codes.id_promo_campaign', 'promo_campaigns.id_promo_campaign')
                            ->where('id_promo_campaign_promo_code', $appliedPromo->id_reference)
                            ->with([
                                'promo_campaign_product_discount_rules',
                                'promo_campaign_discount_bill_rules',
                                'promo_campaign_tier_discount_rules',
                                'promo_campaign_buyxgety_rules',
                                'promo_campaign_product_discount',
                                'promo_campaign_tier_discount_product',
                                'promo_campaign_buyxgety_product_requirement',
                                'promo_campaign_discount_bill_products'
                            ])
                            ->first();
                        if (!$query) {
                            goto skip;
                        }
                        break;
                    
                    case 'subscription':
                        $query = Subscription::join('subscription_users', 'subscription_users.id_subscription', 'subscriptions.id_subscription')
                            ->join('subscription_user_vouchers', 'subscription_users.id_subscription_user', 'subscription_user_vouchers.id_subscription_user')
                            ->where('id_subscription_user_voucher', $appliedPromo->id_reference)
                            ->with([
                                'subscription_products',
                                'subscription_products.product'
                            ])
                            ->first();
                        if (!$query) {
                            goto skip;
                        }
                        break;
                    
                    default:
                        goto skip;
                        break;

                }
                $promoVariant = app('\Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign')->getProduct($appliedPromo->promo_type, $query->toArray(), $post['id_outlet'])['applied_product'] ?? [];
                $productVariantIdPromo = [];
                if (is_array($promoVariant)) {
                    $productVariantIdPromo = array_filter(array_column($promoVariant, 'id_product_variant_group'));
                    if (!$productVariantIdPromo) {
                        $productPromo = array_filter(array_column($promoVariant, 'id_product'));
                        if (in_array($product['id_product'], $productPromo)) {
                            $productVariantIdPromo = ProductVariantGroup::where('id_product', $product['id_product'])->pluck('id_product_variant_group')->toArray();
                        }
                    };
                } elseif ($promoVariant == '*') {
                    $productVariantIdPromo = ProductVariantGroup::where('id_product', $product['id_product'])->pluck('id_product_variant_group')->toArray();
                }

                if ($productVariantIdPromo) {
                    $product['variants'] = $this->addPromoFlag($product['variants'], $productVariantIdPromo);
                    unset($product['variants']['promo']);
                }
                skip:
            }
        }
        $product['selected_available'] = 1;
        if ($post['selected']['id_product_variant_group'] ?? false) {
            $product['selected_available'] = (!!Product::getVariantParentId($post['selected']['id_product_variant_group'], $product['variants'], $post['selected']['extra_modifiers'] ?? []))?1:0;
        }
        $product['popup_message'] = $product['selected_available'] ? '' : 'Varian yang dipilih tidak tersedia';
        $product['modifiers'] = $product_modifiers->orderBy('product_modifier_order', 'asc')->orderBy('text', 'asc')->get()->toArray();
        foreach ($product['modifiers'] as $key => &$modifier) {
            $modifier['price'] = (int) $modifier['price'];
            unset($modifier['product_modifier_prices']);
        }
        $product['max_order'] = (int) $max_order;
        $product['max_order_alert'] = MyHelper::simpleReplace(Setting::select('value_text')->where('key','transaction_exceeds_limit_text')->pluck('value_text')->first()?:'Transaksi anda melebihi batas! Maksimal transaksi untuk %product_name% : %max_order%',
                    [
                        'product_name' => $product['product_name'],
                        'max_order' => $max_order
                    ]
                );
        $product['outlet'] = Outlet::select('id_outlet','outlet_code','outlet_address','outlet_name')->find($post['id_outlet']);

        return MyHelper::checkGet($product);
    }

    protected function addPromoFlag($variant_tree, $id_variant_group_promo)
    {
        if (!$variant_tree || !$id_variant_group_promo) {
            return $variant_tree;
        }
        if ($variant_tree['childs'] ?? false) {
            foreach ($variant_tree['childs'] as $key => $child) {
                if ($variant_tree['childs'][$key]['variant'] ?? false) {
                    $variant_tree['childs'][$key]['variant'] = $this->addPromoFlag($variant_tree['childs'][$key]['variant'], $id_variant_group_promo);
                    // read flag promo from child
                    if ($variant_tree['childs'][$key]['variant']['promo'] ?? false) {
                        // flag promo last child
                        $variant_tree['childs'][$key]['promo'] = 1;
                        // set flag promo for parent
                        $variant_tree['promo'] = 1;
                        // unset promo from child
                        unset($variant_tree['childs'][$key]['variant']['promo']);
                    }
                } else {
                    if (in_array($variant_tree['childs'][$key]['id_product_variant_group'] ?? false, $id_variant_group_promo)) {
                        // flag promo last child
                        $variant_tree['childs'][$key]['promo'] = 1;
                        // flag promo parent of last child
                        $variant_tree['promo'] = 1;
                    }
                }
            }
        }
        return $variant_tree;
    }

    public function ajaxProductBrand(Request $request)
    {
    	$post=$request->except('_token');
        $q= (new Product)->newQuery();
        if($post['select']??false){
            $q->select($post['select']);
        }

        if($condition=$post['condition']??false){
            $this->filterList($q,$condition['rules']??'',$condition['operator']??'and');
        }
        return MyHelper::checkGet($q->get());
    }

    public function filterList($model,$rule,$operator='and'){
        $newRule=[];
        $where=$operator=='and'?'where':'orWhere';
        foreach ($rule as $var) {
            $var1=['operator'=>$var['operator']??'=','parameter'=>$var['parameter']??null];
            if($var1['operator']=='like'){
                $var1['parameter']='%'.$var1['parameter'].'%';
            }
            $newRule[$var['subject']][]=$var1;
        }

        if($rules=$newRule['id_brand']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('brands',function($query) use ($rul){
                    $query->where('brands.id_brand',$rul['operator'],$rul['parameter']);
                });
            }
        }
    }

    public function listProductAjaxSimple(){
        return MyHelper::checkGet(Product::select('id_product', 'product_name')->get());
    }

    public function getProductByBrand(Request $request){
        $post = $request->json()->all();
        $data = Product::join('brand_product','products.id_product','=','brand_product.id_product');

        if(isset($post['id_brand']) && !empty($post['id_brand'])){
            $data->where('brand_product.id_brand', $post['id_brand']);
        }
        $data = $data->get()->toArray();

        return response()->json(MyHelper::checkGet($data));
    }

    public function outletServiceListProduct(Request $request){
        $post = $request->json()->all();
        if(empty($post['id_outlet']) && empty($post['outlet_code'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID/Code outlet can not be empty']]);
        }

        $outlet = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'provinces.id_province', 'cities.id_province')
                ->with(['outlet_schedules', 'holidays.date_holidays', 'today'])
                ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc');

        if(!empty($post['id_outlet'])){
            $outlet = $outlet->where('id_outlet', $post['id_outlet'])->first();
        }

        if(!empty($post['outlet_code'])){
            $outlet = $outlet->where('outlet_code', $post['outlet_code'])->first();
        }

        if (!$outlet) {
            return [
                'status' => 'fail',
                'messages' => ['Outlet not found']
            ];
        }

        $isClose = false;
        $timeZone = (empty($outlet['province_time_zone_utc']) ? 7:$outlet['province_time_zone_utc']);
        $diffTimeZone = $timeZone - 7;
        $date = date('Y-m-d H:i:s');
        // $date = date('Y-m-d H:i:s', strtotime("+".$diffTimeZone." hour", strtotime($date)));
        $currentDate = date('Y-m-d', strtotime($date));
        $currentHour = date('H:i:s', strtotime($date));
        if(empty($outlet['today']['open']) || empty( $outlet['today']['close'])){
            $isClose = true;
        }else{
            $open = date('H:i:s', strtotime($outlet['today']['open']));
            $close = date('H:i:s', strtotime($outlet['today']['close']));
            foreach ($outlet['holidays'] as $holidays){
                $holiday = $holidays['date_holidays']->toArray();
                $dates = array_column($holiday, 'date');
                if(array_search($currentDate, $dates) !== false){
                    $isClose = true;
                    break;
                }
            }

            if(empty($outlet['today']) || strtotime($currentHour) < strtotime($open) || strtotime($currentHour) > strtotime($close) || $outlet['today']['is_closed'] == 1){
                $isClose = true;
            }
        }

        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
                ->where('id_outlet', $outlet['id_outlet'])->first();

        if(empty($brand)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
        }

        //get data service
        $productServie = Product::select([
            'products.id_product', 'products.product_name', 'products.product_code', 'products.product_description', 'product_variant_status',
            DB::raw('(CASE
                        WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $outlet['id_outlet'] . ' ) = 1 
                        THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . ' )
                        ELSE product_global_price.product_global_price
                    END) as product_price')
        ])
            ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('brand_outlet', 'brand_outlet.id_brand', '=', 'brand_product.id_brand')
            ->where('brand_outlet.id_outlet', '=', $outlet['id_outlet'])
            ->where('brand_product.id_brand', '=', $brand['id_brand'])
            ->where('product_type', 'service')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility is NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                    END)')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1)
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1)
                    END)')
            ->where(function ($query) use ($outlet) {
                $query->WhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_special_price desc limit 1) is NOT NULL');
                $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product order by id_product_global_price desc limit 1) is NOT NULL');
            })
            ->with(['photos', 'product_service_use'])
            ->having('product_price', '>', 0)
            ->groupBy('products.id_product')
            ->orderByRaw('CASE WHEN products.position = 0 THEN 1 ELSE 0 END')
            ->orderBy('products.position')
            ->orderBy('products.id_product')
            ->get()->toArray();

        $resProdService = [];
        foreach ($productServie as $val){
            $stockStatus = 'Available';
            $getProductDetail = ProductDetail::where('id_product', $val['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();

            if(!is_null($getProductDetail['product_detail_stock_item']) && $getProductDetail['product_detail_stock_item'] <= 0){
                $stockStatus = 'Sold Out';
            }elseif (is_null($getProductDetail['product_detail_stock_item']) && ($getProductDetail['product_detail_stock_status'] == 'Sold Out' || $getProductDetail['product_detail_status'] == 'Inactive')){
                $stockStatus = 'Sold Out';
            }elseif(empty($getProductDetail)){
                $stockStatus = 'Sold Out';
            }

            $resProdService[] = [
                'id_product' => $val['id_product'],
                'id_brand' => $brand['id_brand'],
                'product_type' => 'service',
                'product_code' => $val['product_code'],
                'product_name' => $val['product_name'],
                'product_description' => $val['product_description'],
                'product_price' => (int)$val['product_price'],
                'string_product_price' => 'Rp '.number_format((int)$val['product_price'],0,",","."),
                'product_stock_status' => $stockStatus,
                'photo' => (empty($val['photos'][0]['product_photo']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$val['photos'][0]['product_photo'])
            ];
        }

        //get data product
        $products = Product::select([
            'products.id_product', 'products.product_name', 'products.product_code', 'products.product_description', 'product_variant_status',
            DB::raw('(CASE
                        WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $outlet['id_outlet'] . ' ) = 1 
                        THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . ' )
                        ELSE product_global_price.product_global_price
                    END) as product_price'),
            DB::raw('(select product_detail.product_detail_stock_item from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1) as product_stock_status')
        ])
            ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('brand_outlet', 'brand_outlet.id_brand', '=', 'brand_product.id_brand')
            ->where('brand_outlet.id_outlet', '=', $outlet['id_outlet'])
            ->where('brand_product.id_brand', '=', $brand['id_brand'])
            ->where('product_type', 'product')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility is NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                    END)')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1)
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1)
                    END)')
            ->where(function ($query) use ($outlet) {
                $query->WhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_special_price desc limit 1) is NOT NULL');
                $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product order by id_product_global_price desc limit 1) is NOT NULL');
            })
            ->with(['photos'])
            ->having('product_price', '>', 0)
            ->groupBy('products.id_product')
            ->orderByRaw('CASE WHEN products.position = 0 THEN 1 ELSE 0 END')
            ->orderBy('products.position')
            ->orderBy('products.id_product')
            ->get()->toArray();

        $resProducts = [];
        foreach ($products as $val){
            if ($val['product_variant_status'] && $val['product_stock_status'] == 'Available') {
                $variantTree = Product::getVariantTree($val['id_product'], ['id_outlet' => $outlet['id_outlet'], 'outlet_different_price' => $outlet['outlet_different_price']]);
                $val['product_price'] = ($variantTree['base_price']??false)?:$val['product_price'];
            }

            $stock = 'Available';
            if($val['product_stock_status'] <= 0){
                $stock = 'Sold Out';
            }

            $resProducts[] = [
                'id_product' => $val['id_product'],
                'id_brand' => $brand['id_brand'],
                'product_type' => 'product',
                'product_code' => $val['product_code'],
                'product_name' => $val['product_name'],
                'product_description' => $val['product_description'],
                'product_price' => (int)$val['product_price'],
                'string_product_price' => 'Rp '.number_format((int)$val['product_price'],0,",","."),
                'product_stock_status' => $stock,
                'qty_stock' => (int)$val['product_stock_status'],
                'photo' => (empty($val['photos'][0]['product_photo']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$val['photos'][0]['product_photo'])
            ];
        }

        if(!empty($post['latitude']) && !empty($post['longitude'])){
            $distance = (float)app('Modules\Outlet\Http\Controllers\ApiOutletController')->distance($post['latitude'], $post['longitude'], $outlet['outlet_latitude'], $outlet['outlet_longitude'], "K");
            if($distance < 1){
                $distance = number_format($distance*1000, 0, '.', '').' m';
            }else{
                $distance = number_format($distance, 2, '.', '').' km';
            }
        }

        $messagesFailOutlet = '';
        if ($outlet['today']['is_closed']) {
            $messagesFailOutlet = 'Maaf, outlet tutup hari ini';
        } elseif (empty($outlet['today']) && $isClose == true){
            $messagesFailOutlet = 'Maaf, outlet belum buka.';
        }elseif(!empty($outlet['today']) && !empty($open) && !empty($close) && $isClose == true){
            $messagesFailOutlet = 'Maaf, outlet belum buka. Silahkan berkunjung kembali diantara pukul '.MyHelper::adjustTimezone($open, $timeZone, 'H:i').' sampai '.MyHelper::adjustTimezone($close, $timeZone, 'H:i');
        }elseif(!empty($outlet['today']) && (empty($open) || empty($close)) && $isClose == true){
            $messagesFailOutlet = 'Maaf, outlet belum buka.';
        }

        $resOutlet = [
            'is_close' => $isClose,
            'id_outlet' => $outlet['id_outlet'],
            'outlet_code' => $outlet['outlet_code'],
            'outlet_name' => $outlet['outlet_name'],
            'outlet_image' => $outlet['outlet_image'],
            'outlet_address' => $outlet['outlet_address'],
            'distance' => $distance??'',
            'color' => $brand['color_brand']??''
        ];

        $resBrand = [
            'id_brand' => $brand['id_brand'],
            'brand_code' => $brand['code_brand'],
            'brand_name' => $brand['name_brand'],
            'brand_logo' => $brand['logo_brand'],
            'brand_logo_landscape' => $brand['logo_landscape_brand']
        ];

        $result = [
            'color' => $brand['color_brand'],
            'outlet' => $resOutlet,
            'brand' => $resBrand,
            'list_product' => [
                'service' => $resProdService,
                'products' => $resProducts
            ],
            'message_fail' => $messagesFailOutlet
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function outletServiceDetailProductService(Request $request){
        $bearerToken = $request->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);

        $post = $request->json()->all();
        if(empty($post['id_outlet']) && empty($post['outlet_code'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID/Code outlet can not be empty']]);
        }

        $outlet = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
            ->join('provinces', 'provinces.id_province', 'cities.id_province')
            ->with(['outlet_schedules'])
            ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc');

        if(!empty($post['id_outlet'])){
            $outlet = $outlet->where('id_outlet', $post['id_outlet'])->first();
        }

        if(!empty($post['outlet_code'])){
            $outlet = $outlet->where('outlet_code', $post['outlet_code'])->first();
        }

        if (!$outlet) {
            return [
                'status' => 'fail',
                'messages' => ['Outlet not found']
            ];
        }

        if(empty($post['id_product']) && empty($post['product_code'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID/Code product can not be empty']]);
        }

        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
            ->where('id_outlet', $outlet['id_outlet'])->first();

        if(empty($brand)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
        }

        $product = Product::where('product_type', 'service')
                    ->select([
                    'products.id_product', 'products.product_name', 'products.product_code', 'products.product_description', 'product_variant_status', 'processing_time_service',
                    DB::raw('(CASE
                                WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $outlet['id_outlet'] . ' ) = 1 
                                THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . ' )
                                ELSE product_global_price.product_global_price
                            END) as product_price'),
                    ])
                    ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
                    ->with(['photos']);
        if(!empty($post['id_product'])){
            $product = $product->where('products.id_product', $post['id_product'])->first();
        }

        if (!empty($post['product_code'])){
            $product = $product->where('products.product_code', $post['product_code'])->first();
        }

        if (!$product) {
            return [
                'status' => 'fail',
                'messages' => ['Product not found']
            ];
        }

        if ($product['product_variant_status']) {
            $variantTree = Product::getVariantTree($product['id_product'], ['id_outlet' => $outlet['id_outlet'], 'outlet_different_price' => $outlet['outlet_different_price']]);
            $product['product_price'] = ($variantTree['base_price']??false)?:$product['product_price'];
        }

        $day = [
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu'
        ];

        $outletSchedules= $outlet['outlet_schedules']->toArray();
        $allDay = array_column($outletSchedules, 'day');

        //total date
        $totalDateShow = Setting::where('key', 'total_show_date_booking_service')->first()->value??1;
        $timeZone = (empty($outlet['province_time_zone_utc']) ? 7:$outlet['province_time_zone_utc']);
        $diffTimeZone = $timeZone - 7;
        $date = date('Y-m-d H:i:s');
        // $date = date('Y-m-d H:i:s', strtotime("+".$diffTimeZone." hour", strtotime($date)));
        $today = date('Y-m-d', strtotime($date));
        $currentTime = date('H:i', strtotime($date));
        $processingTime = (int)(empty($product['processing_time_service']) ? 30:$product['processing_time_service']);
        $listDate = [];

        if($scopeUser == 'apps'){
            $x = 0;
            $count = 1;
            while($count <= (int)$totalDateShow) {
                $close = 0;
                $date = date('Y-m-d', strtotime('+'.$x.' day', strtotime($today)));
                $dayConvert = $day[date('D', strtotime($date))];

                $outletSchedule = OutletSchedule::where('id_outlet', $outlet['id_outlet'])->where('day', $dayConvert)->first();
                if($outletSchedule['is_closed'] == 1){
                    $close = 1;
                }

                $holiday = Holiday::join('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')->join('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                    ->where('id_outlet', $outlet['id_outlet'])->whereDay('date_holidays.date', date('d', strtotime($date)))->whereMonth('date_holidays.date', date('m', strtotime($date)))->get();
                if(count($holiday) > 0){
                    foreach($holiday as $i => $holi){
                        if($holi['yearly'] == '0'){
                            if($holi['date'] == $date){
                                $close = 1;
                            }
                        }else{
                            $close = 1;
                        }
                    }
                }

                if($close == 0 && array_search($dayConvert, $allDay) !== false){
                    $getTime = array_search($dayConvert, array_column($outletSchedules, 'day'));
                    $open = date('H:i', strtotime($outletSchedules[$getTime]['open'] . "+ $diffTimeZone hour"));
                    $close = date('H:i', strtotime($outletSchedules[$getTime]['close'] . "+ $diffTimeZone hour"));
                    $times = [];
                    $tmpTime = $open;
                    if(strtotime($date.' '.$open) > strtotime($today.' '.$currentTime . "+ $diffTimeZone hour")) {
                        $times[] = $open;
                    }
                    while(strtotime($tmpTime) < strtotime($close)) {
                        $dateTimeConvert = date('Y-m-d H:i', strtotime("+".$processingTime." minutes", strtotime($date.' '.$tmpTime)));
                        if(strtotime($dateTimeConvert) > strtotime($date.' '.$close)){
                            break;
                        }

                        $timeConvert = date('H:i', strtotime("+".$processingTime." minutes", strtotime($tmpTime)));
                        if(strtotime($date.' '.$timeConvert) > strtotime($today.' '.$currentTime . "+ $diffTimeZone hour") && $close!=$timeConvert){
                            $times[] = $timeConvert;
                        }
                        $tmpTime = $timeConvert;
                    }
                    if(!empty($times)){
                        $listDate[] = [
                            'date' => $date,
                            'times' => $times
                        ];
                    }
                    $count++;
                }
                $x++;
            }
        }else{
            $date = $today;
            $dayConvert = $day[date('D', strtotime($date))];
            if(array_search($dayConvert, $allDay) !== false){
                $getTime = array_search($dayConvert, array_column($outletSchedules, 'day'));
                $open = date('H:i', strtotime($outletSchedules[$getTime]['open'] . "+ $diffTimeZone hour"));
                $close = date('H:i', strtotime($outletSchedules[$getTime]['close'] . "+ $diffTimeZone hour"));
                $times = [];
                $tmpTime = $open;
                if(strtotime($date.' '.$open) > strtotime($today.' '.$currentTime . "+ $diffTimeZone hour")) {
                    $times[] = $open;
                }
                while(strtotime($tmpTime) < strtotime($close)) {
                    $dateTimeConvert = date('Y-m-d H:i', strtotime("+".$processingTime." minutes", strtotime($date.' '.$tmpTime)));
                    if(strtotime($dateTimeConvert) > strtotime($date.' '.$close)){
                        break;
                    }

                    $timeConvert = date('H:i', strtotime("+".$processingTime." minutes", strtotime($tmpTime)));
                    if(strtotime($date.' '.$timeConvert) > strtotime($today.' '.$currentTime . "+ $diffTimeZone hour") && $close!=$timeConvert){
                        $times[] = $timeConvert;
                    }
                    $tmpTime = $timeConvert;
                }
                $listDate = [
                    'date' => $date,
                    'date_convert' => MyHelper::dateFormatInd($date,true,false,true),
                    'times' => $times
                ];
            }
        }


        $result = [
            'color' => $brand['color_brand'],
            'id_product' => $product['id_product'],
            'id_brand' => $brand['id_brand']??null,
            'product_code' => $product['product_code'],
            'product_name' => $product['product_name'],
            'product_description' => (empty($product['product_description']) ? '':$product['product_description']),
            'product_price' => (int)$product['product_price'],
            'string_product_price' => 'Rp '.number_format((int)$product['product_price'],0,",","."),
            'photo' => (empty($product['photos'][0]['product_photo']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$product['photos'][0]['product_photo']),
            'list_date' => $listDate
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function outletServiceAvailableHs(AvailableHs $request){
        $post = $request->json()->all();
        $bookDate = date('Y-m-d', strtotime($post['booking_date']));
        $bookTimeOrigin = date('H:i:s', strtotime($post['booking_time']));
        $bookTime = date('H:i:s', strtotime($post['booking_time']));

        if(!empty($post['outlet_code'])){
            $outlet = Outlet::where('outlet_code', $post['outlet_code'])
                ->join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'provinces.id_province', 'cities.id_province')
                ->select('outlets.*', 'provinces.time_zone_utc as province_time_zone_utc')
                ->first();
        }elseif(!empty($post['id_outlet'])){
            $outlet = Outlet::where('id_outlet', $post['id_outlet'])
                ->join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'provinces.id_province', 'cities.id_province')
                ->select('outlets.*', 'provinces.time_zone_utc as province_time_zone_utc')
                ->first();
        }

        if(empty($outlet)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
        }

        $timeZone = (empty($outlet['province_time_zone_utc']) ? 7:$outlet['province_time_zone_utc']);
        $diffTimeZone = $timeZone - 7;
        $bookTime = date('H:i:s', strtotime($bookTime . "- $diffTimeZone hour"));
        $post['id_outlet'] = $outlet['id_outlet'];

        //product category hs
        $hsCat = [];
        if(!empty($post['id_product'])){
            $hsCat = ProductHairstylistCategory::where('id_product', $post['id_product'])->pluck('id_hairstylist_category')->toArray();
        }

        //get Schedule
        $day = [
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu'
        ];
        $bookDay = $day[date('D', strtotime($bookDate))];
        $idOutletSchedule = OutletSchedule::where('id_outlet', $outlet['id_outlet'])
                          ->where('day', $bookDay)->first()['id_outlet_schedule']??null;

        $hsNotAvailable = HairstylistNotAvailable::where('id_outlet', $post['id_outlet'])
                            ->where('booking_start', $bookDate.' '.$bookTimeOrigin)
                            ->pluck('id_user_hair_stylist')->toArray();

        $listHs = UserHairStylist::where('id_outlet', $post['id_outlet'])
                    ->where('user_hair_stylist_status', 'Active')->get()->toArray();

        $res = [];
        foreach ($listHs as $val){
            $availableStatus = false;

            //check schedule hs
            $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                ->whereNotNull('approve_at')->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                ->whereDate('date', $bookDate)
                ->first();

            if(empty($shift)){
                continue;
            }

            if($bookDate == date('Y-m-d') && strtotime($bookTime) < strtotime($shift['time_end'])){
                $clockInOut = HairstylistAttendance::where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                    ->where('id_hairstylist_schedule_date', $shift['id_hairstylist_schedule_date'])->orderBy('updated_at', 'desc')->first();

                if(!empty($clockInOut) && !empty($clockInOut['clock_in']) && strtotime($bookTime) >= strtotime($clockInOut['clock_in'])){
                    $availableStatus = true;
                    $lastAction = HairstylistAttendanceLog::where('id_hairstylist_attendance', $clockInOut['id_hairstylist_attendance'])->orderBy('datetime', 'desc')->first();
                    if(!empty($clockInOut['clock_out']) && $lastAction['type'] == 'clock_out' && strtotime($bookTime) > strtotime($clockInOut['clock_out'])){
                        $availableStatus = false;
                    }
                }
            }elseif($bookDate > date('Y-m-d')){
                $shiftTimeStart = date('H:i:s', strtotime($shift['time_start']));
                $shiftTimeEnd = date('H:i:s', strtotime($shift['time_end']));
                if(strtotime($bookTime) >= strtotime($shiftTimeStart) && strtotime($bookTime) < strtotime($shiftTimeEnd)){
                    //check available in transaction
                    $checkAvailable = array_search($val['id_user_hair_stylist'], $hsNotAvailable);
                    if($checkAvailable === false){
                        $availableStatus = true;
                    }
                }
            }

            $bookTimeOrigin = date('H:i:s', strtotime($bookTimeOrigin . "+ 1 minutes"));
            $notAvailable = HairstylistNotAvailable::where('id_outlet', $post['id_outlet'])
                ->whereRaw('"'.$bookDate.' '.$bookTimeOrigin. '" BETWEEN booking_start AND booking_end')
                ->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                ->first();

            if(!empty($notAvailable)){
                $availableStatus = false;
            }

            if(!empty($hsCat) && !in_array($val['id_hairstylist_category'], $hsCat)){
                $availableStatus = false;
            }

            $res[] = [
                'id_user_hair_stylist' => $val['id_user_hair_stylist'],
                'name' => "$val[fullname] ($val[nickname])",
                'nickname' => $val['nickname'],
                'photo' => (empty($val['user_hair_stylist_photo']) ? config('url.storage_url_api').'img/product/item/default.png':$val['user_hair_stylist_photo']),
                'rating' => $val['total_rating'],
                'available_status' => $availableStatus,
                'order' => ($availableStatus ? $val['id_user_hair_stylist']:1000)
            ];
        }

        if(!empty($res)){
            usort($res, function($a, $b) {
                return $a['order'] - $b['order'];
            });
        }

        return response()->json(['status' => 'success', 'result' => $res]);
    }

    public function outletServiceAvailableHsV2(Request $request){
        $post = $request->json()->all();
        $bookDate = date('Y-m-d', strtotime($post['booking_date']));

        if(!empty($post['outlet_code'])){
            $outlet = Outlet::where('outlet_code', $post['outlet_code'])
                ->join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'provinces.id_province', 'cities.id_province')
                ->select('outlets.*', 'provinces.time_zone_utc as province_time_zone_utc')
                ->first();
        }elseif(!empty($post['id_outlet'])){
            $outlet = Outlet::where('id_outlet', $post['id_outlet'])
                ->join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'provinces.id_province', 'cities.id_province')
                ->select('outlets.*', 'provinces.time_zone_utc as province_time_zone_utc')
                ->first();
        }

        if(empty($outlet)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
        }

        $post['id_outlet'] = $outlet['id_outlet'];

        //get Schedule
        $day = [
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu'
        ];
        $bookDay = $day[date('D', strtotime($bookDate))];
        $idOutletSchedule = OutletSchedule::where('id_outlet', $outlet['id_outlet'])
                          ->where('day', $bookDay)->first()['id_outlet_schedule']??null;

        $listHs = UserHairStylist::where('id_outlet', $post['id_outlet'])
                    ->where('user_hair_stylist_status', 'Active')->get()->toArray();

        $res = [];
        foreach ($listHs as $val){
            $availableStatus = false;

            //check schedule hs
            $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                ->whereNotNull('approve_at')->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                ->whereDate('date', $bookDate)
                ->first();

            if(empty($shift)){
                continue;
            }

            $dateShiftStart = date("Y-m-d H:i:s", strtotime($bookDate.' '.$shift['time_start']));
            $dateShiftEnd = date("Y-m-d H:i:s", strtotime($bookDate.' '.$shift['time_end']));

            if($bookDate == date('Y-m-d')){
                $clockInOut = HairstylistAttendance::where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                    ->where('id_hairstylist_schedule_date', $shift['id_hairstylist_schedule_date'])->orderBy('updated_at', 'desc')->first();

                if(!empty($clockInOut) && !empty($clockInOut['clock_in'])){
                    $availableStatus = true;
                    $lastAction = HairstylistAttendanceLog::where('id_hairstylist_attendance', $clockInOut['id_hairstylist_attendance'])->orderBy('datetime', 'desc')->first();
                    if(!empty($clockInOut['clock_out']) && $lastAction['type'] == 'clock_out'){
                        $availableStatus = false;
                    }
                }
            }else{
                $hsNotAvailable = HairstylistNotAvailable::where('id_outlet', $outlet['id_outlet'])
                ->whereRaw('((booking_start = "'.$dateShiftStart.'" AND booking_end = "'.$dateShiftEnd.'"))')
                ->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                ->first();
                if(!$hsNotAvailable){
                    $availableStatus = true;
                }

            }

            $res[] = [
                'id_user_hair_stylist' => $val['id_user_hair_stylist'],
                'name' => "$val[fullname] ($val[nickname])",
                'nickname' => $val['nickname'],
                'shift_time' => date('H:i', strtotime($shift['time_start'])).' - '.date('H:i', strtotime($shift['time_end'])),
                'photo' => (empty($val['user_hair_stylist_photo']) ? null:$val['user_hair_stylist_photo']),
                'gender' => $val['gender'] ?? 'Male',
                'rating' => $val['total_rating'],
                'available_status' => $availableStatus,
                'order' => ($availableStatus ? $val['id_user_hair_stylist']:1000)
            ];
        }

        if(!empty($res)){
            usort($res, function($a, $b) {
                return $a['order'] - $b['order'];
            });
        }

        return response()->json(['status' => 'success', 'result' => $res]);
    }

    function getTimeShift($shift, $id_outlet, $id_outlet_schedule){
        $outletShift = OutletTimeShift::where('id_outlet', $id_outlet)
                        ->where('id_outlet_schedule', $id_outlet_schedule)->get()->toArray();
        $data = [];
        if(!empty($outletShift)){
            foreach ($outletShift as $value){
                $data[strtolower($value['shift'])] = [
                    'start' => date('H:i', strtotime($value['shift_time_start'])),
                    'end' => date('H:i', strtotime($value['shift_time_end']))
                ];
            }
        }

        return $data[$shift]??[];
    }

    public function shopListProduct(Request $request) {
        $post = $request->json()->all();
        if(empty($post['id_outlet']) && empty($post['outlet_code'])) {
        	$post['id_outlet'] = Setting::where('key', 'default_outlet')->pluck('value');
        }
        $outlet = Outlet::with(['outlet_schedules', 'holidays.date_holidays', 'today']);

        if(!empty($post['id_outlet'])){
            $outlet = $outlet->where('id_outlet', $post['id_outlet'])->first();
        }

        if(!empty($post['outlet_code'])){
            $outlet = $outlet->where('outlet_code', $post['outlet_code'])->first();
        }

        if (!$outlet) {
            return [
                'status' => 'fail',
                'messages' => ['Outlet tidak ditemukan']
            ];
        }

        $isClose = false;
        $currentDate = date('Y-m-d');
        $currentHour = date('H:i:s');
        $open = date('H:i:s', strtotime($outlet['today']['open']));
        $close = date('H:i:s', strtotime($outlet['today']['close']));
        foreach ($outlet['holidays'] as $holidays){
            $holiday = $holidays['date_holidays']->toArray();
            $dates = array_column($holiday, 'date');
            if(array_search($currentDate, $dates) !== false){
                $isClose = true;
                break;
            }
        }

        if(strtotime($currentHour) < strtotime($open) || strtotime($currentHour) > strtotime($close) || $outlet['today']['is_closed'] == 1){
            $isClose = true;
        }

        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
                ->where('id_outlet', $outlet['id_outlet'])->first();

        if(empty($brand)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet tidak memiliki brand']]);
        }

        //get data product
        $products = Product::select([
	            'products.id_product', 
	            'products.product_name', 
	            'products.product_code', 
	            'products.product_description', 
	            'product_variant_status',
	            'product_groups.id_product_group',
	            'product_groups.product_group_code',
	            'product_groups.product_group_name',
	            'product_groups.product_group_description',
	            'product_groups.product_group_photo',
	            DB::raw('
	            	MIN(CASE
                        WHEN (select outlets.outlet_different_price from outlets 
                    			where outlets.id_outlet = ' . $outlet['id_outlet'] . ' 
            				) = 1 
                        THEN (select product_special_price.product_special_price from product_special_price 
                        		where product_special_price.id_product = products.id_product 
                        		AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . ' 
                    		)
                        ELSE product_global_price.product_global_price
                    	END
                    ) as product_price
                '),
	            DB::raw('
	            	(select product_detail.product_detail_stock_item from product_detail 
	            		JOIN products ON product_detail.id_product = products.id_product
	            		WHERE products.id_product_group = product_groups.id_product_group 
	            		AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' 
	            		order by product_detail.product_detail_stock_item desc limit 1
            		) as product_stock_status
            	')
	        ])
            ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('brand_outlet', 'brand_outlet.id_brand', '=', 'brand_product.id_brand')
            ->join('product_groups', 'product_groups.id_product_group', '=', 'products.id_product_group')
            ->join('product_detail', 'product_detail.id_product', '=', 'products.id_product')
            ->where('brand_outlet.id_outlet', '=', $outlet['id_outlet'])
            ->where('product_detail.id_outlet', '=', $outlet['id_outlet'])
            // ->where('brand_product.id_brand', '=', $brand['id_brand'])
            ->where('product_type', 'product')
            ->whereRaw('
            	products.id_product in (
            		CASE
                    WHEN (select product_detail.id_product from product_detail  
                    		where product_detail.id_product = products.id_product 
                    		AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  
                    		order by id_product_detail desc limit 1
                		) is NULL AND products.product_visibility = "Visible" 
                    THEN products.id_product
                    WHEN (select product_detail.id_product from product_detail  
                    		where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility is NULL) 
                    		AND product_detail.id_product = products.id_product 
                    		AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  
                    		order by id_product_detail desc limit 1
                		) is NOT NULL AND products.product_visibility = "Visible" 
                    THEN products.id_product
                    ELSE (select product_detail.id_product from product_detail  
                    		where product_detail.product_detail_visibility = "Visible" 
                    		AND product_detail.id_product = products.id_product 
                    		AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  
                    		order by id_product_detail desc limit 1
                		)
                    END
                )
            ')
            ->whereRaw('products.id_product in (
            	CASE
                WHEN (select product_detail.id_product from product_detail  
                		where product_detail.id_product = products.id_product 
                		AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' 
                		order by id_product_detail desc limit 1
            		) is NULL 
        		THEN products.id_product
                ELSE (select product_detail.id_product from product_detail 
                		where product_detail.product_detail_status = "Active" 
                		AND product_detail.id_product = products.id_product 
                		AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' 
                		order by id_product_detail desc limit 1
            		)
                END
                )
            ')
            ->where(function ($query) use ($outlet) {
                $query->WhereRaw('
                	(select product_special_price.product_special_price from product_special_price  
                		where product_special_price.id_product = products.id_product 
                		AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . '  
                		order by id_product_special_price desc limit 1
            		) is NOT NULL
            	');
                $query->orWhereRaw('
                	(select product_global_price.product_global_price from product_global_price  
                		where product_global_price.id_product = products.id_product 
                		order by id_product_global_price desc limit 1
                	) is NOT NULL
            	');
            })
            ->having('product_price', '>', 0)
            ->groupBy('products.id_product_group')
            ->orderByRaw('CASE WHEN products.position = 0 THEN 1 ELSE 0 END')
            ->orderBy('products.position')
            ->orderBy('products.id_product')
            ->get()
            ->toArray();

        $available = [];
        $soldOut = [];
        foreach ($products as $val){
            $stock = 'Available';
            if(empty($val['product_stock_status'])){
                $stock = 'Sold Out';
            }

            $temp = [
                'id_product' => $val['id_product'],
                'id_product_group' => $val['id_product_group'],
                'id_brand' => $brand['id_brand'],
                'product_type' => 'product',
                'product_group_code' => $val['product_group_code'],
                'product_group_name' => $val['product_group_name'],
                'product_group_description' => $val['product_group_description'],
                'product_price' => (int)$val['product_price'],
                'string_product_price' => 'Rp '.number_format((int)$val['product_price'],0,",","."),
                'product_stock_status' => $stock,
                'qty_stock' => (int)$val['product_stock_status'],
                'photo' => (empty($val['product_group_photo']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$val['product_group_photo'])
            ];

            if ($stock == 'Available') {
            	$available[] = $temp;
            } else {
            	$soldOut[] = $temp;
            }
        }

        $resProducts = array_merge($available, $soldOut);

        if(!empty($post['latitude']) && !empty($post['longitude'])){
            $distance = (float)app('Modules\Outlet\Http\Controllers\ApiOutletController')->distance($post['latitude'], $post['longitude'], $outlet['outlet_latitude'], $outlet['outlet_longitude'], "K");
            if($distance < 1){
                $distance = number_format($distance*1000, 0, '.', '').' m';
            }else{
                $distance = number_format($distance, 2, '.', '').' km';
            }
        }

        $resOutlet = [
            'is_close' => $isClose,
            'id_outlet' => $outlet['id_outlet'],
            'outlet_code' => $outlet['outlet_code'],
            'outlet_name' => $outlet['outlet_name'],
            'outlet_image' => $outlet['outlet_image'],
            'outlet_address' => $outlet['outlet_address'],
            'distance' => $distance??'',
            'color' => $brand['color_brand']??''
        ];

        $resBrand = [
            'id_brand' => $brand['id_brand'],
            'brand_code' => $brand['code_brand'],
            'brand_name' => $brand['name_brand'],
            'brand_logo' => $brand['logo_brand'],
            'brand_logo_landscape' => $brand['logo_landscape_brand']
        ];

        $result = [
            /*'color' => $brand['color_brand'],
            'outlet' => $resOutlet,
            'brand' => $resBrand,*/
            'products' => $resProducts
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function shopDetailProduct(Request $request) {

    	if (!$request->id_product_group) {
    		return [
    			'status' => 'fail', 
    			'messages' => ['Produk tidak ditemukan']
    		];
    	}

    	$id_outlet = $request->id_outlet ?? Setting::where('key','default_outlet')->pluck('value')->first();
        $outlet = Outlet::find($id_outlet);
        if(!$outlet){
    		return ['status' => 'fail', 'messages' => ['Outlet tidak ditemukan']];
        }

        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
                ->where('id_outlet', $id_outlet)->first();
        if(!$brand){
    		return ['status' => 'fail', 'messages' => ['Brand tidak ditemukan']];
        }

        $id_brand = $brand->id_brand;

    	$products = Product::select(
    					'products.*',
    					'product_groups.product_group_code',
    					'product_groups.product_group_name',
    					'product_groups.product_group_description',
    					'brand_product.id_brand',
			        	DB::raw('
			            	(CASE
		                        WHEN ' . $outlet->outlet_different_price . ' = 1 
		                        THEN (select product_special_price.product_special_price from product_special_price 
	                        		where product_special_price.id_product = products.id_product 
	                        		AND product_special_price.id_outlet = ' . $id_outlet . ' 
	                    		)
		                        ELSE product_global_price.product_global_price
		                    	END
		                    ) as product_price
		                '),
		                DB::raw('
		                	(select product_detail.product_detail_stock_item from product_detail
			                	where product_detail.id_product = products.id_product 
			                	AND product_detail.id_outlet = ' . $id_outlet . ' 
			                	order by id_product_detail desc limit 1
		                	) as product_stock_status
	                	')
			        )
    				->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
		            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
		            ->join('brand_outlet', 'brand_outlet.id_brand', '=', 'brand_product.id_brand')
		            ->join('product_groups', 'product_groups.id_product_group', '=', 'products.id_product_group')
		            ->where('brand_outlet.id_outlet', '=', $id_outlet)
		            ->where('brand_product.id_brand', '=', $id_brand)
		            ->where('product_type', 'product')
		            ->whereRaw('products.id_product in (
		            	CASE
		                WHEN (select product_detail.id_product from product_detail  
		                		where product_detail.id_product = products.id_product 
		                		AND product_detail.id_outlet = ' . $id_outlet . ' 
		                		order by id_product_detail desc limit 1
		            		) is NULL 
		        		THEN products.id_product
		                ELSE (select product_detail.id_product from product_detail 
		                		where product_detail.product_detail_status = "Active" 
		                		AND product_detail.id_product = products.id_product 
		                		AND product_detail.id_outlet = ' . $id_outlet . ' 
		                		order by id_product_detail desc limit 1
		            		)
		                END
		                )
		            ')
		            ->where(function ($query) use ($id_outlet) {
		                $query->WhereRaw('
		                	(select product_special_price.product_special_price from product_special_price  
		                		where product_special_price.id_product = products.id_product 
		                		AND product_special_price.id_outlet = ' . $id_outlet . '  
		                		order by id_product_special_price desc limit 1
		            		) is NOT NULL
		            	');
		                $query->orWhereRaw('
		                	(select product_global_price.product_global_price from product_global_price  
		                		where product_global_price.id_product = products.id_product 
		                		order by id_product_global_price desc limit 1
		                	) is NOT NULL
		            	');
		            })
    				->where('products.id_product_group', $request->id_product_group)
		            ->having('product_price', '>', 0)
		            ->groupBy('products.id_product')
		            ->orderByRaw('CASE WHEN products.position = 0 THEN 1 ELSE 0 END')
		            ->orderBy('products.position')
		            ->orderBy('products.id_product')
		            ->with(['photos'])
		            ->get()
		            ->toArray();

		usort($products, function ($a, $b) {
			return $a['product_price'] <=> $b['product_price'];
		});

		$selectedProduct = null;
		$photos = [];
		$variants = [];
		foreach ($products as $product) {
	        $product['product_detail'] = ProductDetail::where(['id_product' => $product['id_product'], 'id_outlet' => $id_outlet])->first();

	        if (empty($product['product_detail'])) {
	            $product['product_detail']['product_detail_visibility'] = $product['product_visibility'];
	            $product['product_detail']['product_detail_status'] = 'Active';
	        }
	        $max_order = null;

	        if (isset($product['product_detail']['max_order'])) {
	            $max_order = $product['product_detail']['max_order'];
	        }
	        if ($max_order == null) {
	            $max_order = Outlet::select('max_order')->where('id_outlet', $id_outlet)->pluck('max_order')->first();
	            if ($max_order == null) {
	                $max_order = Setting::select('value')->where('key','max_order')->pluck('value')->first();
	                if($max_order == null){
	                    $max_order = 100;
	                }
	            }
	        }
	        
	        if(($product['product_detail']['product_detail_visibility'] ?? false) == 'Hidden') {
	            continue;
	        }
	        unset($product['product_detail']);
	        
	        $product['max_order'] = (int) $max_order;
	        $product['max_order_alert'] = MyHelper::simpleReplace(Setting::select('value_text')->where('key','transaction_exceeds_limit_text')->pluck('value_text')->first()?:'Transaksi anda melebihi batas! Maksimal transaksi untuk %product_name% : %max_order%',
	                    [
	                        'product_name' => $product['product_name'],
	                        'max_order' => $max_order
	                    ]
	                );

        	$disable = 0;
	        if (empty($product['product_stock_status'])) {
	        	$disable = 1;
	        }

	        $variant = [
	        	'id_product' => $product['id_product'],
	        	'id_product_group' => $product['id_product_group'],
	        	'id_brand' => $product['id_brand'],
		        'product_code' => $product['product_code'],
		        'product_name' => $product['product_name'],
		        'variant_name' => $product['variant_name'],
		        'product_description' => $product['product_description'],
		        'product_visibility' => $product['product_visibility'],
                'product_group_code' => $product['product_group_code'],
                'product_group_name' => $product['product_group_name'],
                'product_group_description' => $product['product_group_description'],
                'product_price' => (int)$product['product_price'],
                'string_product_price' => 'Rp '.number_format((int)$product['product_price'],0,",","."),
                'qty_stock' => (int)$product['product_stock_status'],
		        'max_order' => $product['max_order'],
		        'max_order_alert' => $product['max_order_alert'],
		        'disable' => $disable
	        ];

	        $variants[] = $variant;
			$photos[] = config('url.storage_url_api') . ($product['product_photo_detail'] ?? 'img/product/item/detail/default.png');
			if (!$selectedProduct && (!$request->id_product || $request->id_product == $product['id_product'])) {
				$selectedProduct = $variant;
			}
		}

		if (!$selectedProduct) {
    		return ['status' => 'fail', 'messages' => ['Produk tidak ditemukan']];
        }

        if (!count($variants)) {
        	$variants = [];
        }

        $selectedProduct['photos'] = $photos;

        $res = [
        	'detail' => $selectedProduct,
        	// 'outlet' => Outlet::select('id_outlet','outlet_code','outlet_address','outlet_name')->find($id_outlet),
        	'variants' => $variants,
        	'popup_message' => $selectedProduct['disable'] ? 'Produk yang dipilih tidak tersedia' : '',
            'complete_profile' => !!$request->user()->complete_profile,
        ];

        return MyHelper::checkGet($res);
    }

    public function item_icount(){
        $icount = new Icount();
        $data = $icount->ItemList();
        if(isset($data)){
            if($data['response']['Message']=='Success'){
                $list = array();
                foreach ($data['response']['Data'] as $value) {
                    if($value['Name']=='Penjualan Outlet'||$value['Name']=='Revenue Sharing'||$value['Name']=='Management Fee'){
                        $dat = array(
                        'ItemID'=>$value['ItemID'], 
                        'Name'=>$value['Name'], 
                        );
                        array_push($list,$dat);
                    }
                }
                return response()->json($list);
            }
        }
        return ['status' => 'fail', 'messages' => ['Produk tidak ditemukan']];
    }

    public function syncIcount(){
        $log = MyHelper::logCron('Sync Item Icount');
        try{
            $setting = Setting::where('key' , 'Sync Product Icount')->first();
            if($setting){
                if($setting['value'] != 'finished'){
                    return ['status' => 'fail', 'messages' => ['Cant sync now, because sync is in progress']]; 
                }
                $update_setting = Setting::where('key', 'Sync Product Icount')->update(['value' => 'start']);
            }else{
                $create_setting = Setting::updateOrCreate(['key' => 'Sync Product Icount'],['value' => 'start']);
            }
            $send = [
                'page' => 1,
                'id_items' => null,
                'ima' => true,
                'ims' => false, 
            ];
            $sync_job = SyncIcountItems::dispatch($send)->onConnection('syncicountitems');
            $log->success('success');
            return ['status' => 'success', 'messages' => ['Success to sync with ICount']]; 
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }    
    }


    function listProductIcount(Request $request) {
        $post = $request->json()->all();

		if (isset($post['id_outlet']) && !$request->for_select2) {
            $product = ProductIcount::join('product_detail','product_detail.id_product','=','products.id_product')
                                ->leftJoin('product_special_price','product_special_price.id_product','=','products.id_product')
									->where('product_detail.id_outlet','=',$post['id_outlet'])
									->where('product_detail.product_detail_visibility','=','Visible')
                                    ->where('product_detail.product_detail_status','=','Active')
                                    
                                    ->with(['category', 'discount']);

            if (isset($post['visibility'])) {

                if($post['visibility'] == 'Hidden'){
                    $product = ProductIcount::join('product_detail','product_detail.id_product','=','products.id_product')
                        ->where('product_detail.id_outlet','=',$post['id_outlet'])
                        ->where('product_detail.product_detail_visibility','=','Hidden')
                        ->with(['category', 'discount']);
                }else{
                    $ids = ProductIcount::join('product_detail','product_detail.id_product','=','products.id_product')
                        ->where('product_detail.id_outlet','=',$post['id_outlet'])
                        ->where('product_detail.product_detail_visibility','=','Hidden')
                        ->pluck('products.id_product')->toArray();
                    $product = ProductIcount::whereNotIn('id_product', $ids)
                        ->with(['category', 'discount']);
                }

                unset($post['id_outlet']);
            }
		} else {
		    if(isset($post['product_setting_type']) && $post['product_setting_type'] == 'product_price'){
                $product = ProductIcount::with(['category', 'discount', 'product_special_price', 'global_price']);
            }elseif(isset($post['product_setting_type']) && $post['product_setting_type'] == 'outlet_product_detail'){
                $product = ProductIcount::with(['category', 'discount', 'product_detail']);
            }else{
                $product = ProductIcount::with(['unit_icount'])->where('is_actived', 'true');
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
        
        if (isset($post['type'])) {
            if($post['type'] == 'product'){
                $product->where('product_icounts.item_group', '=', 'Inventory')->where('id_category', 1);
            }else{
                $product->where(function($q){
                    $q->where('product_icounts.item_group', '=', 'Inventory')->orWhere('product_icounts.item_group', '=', 'Service');
                });
            }
        }

        if (isset($post['company_type'])) {
            if(isset($post['from'])){
                $product->where(function($q) use($post){
                    $q->where('product_icounts.company_type', $post['company_type'])->orWhere('product_icounts.item_group', '=', 'Assets');
                });
            }else{
                $product->where('product_icounts.company_type', $post['company_type']);
            }
        }

        if (isset($post['buyable'])) {
            $product->where('product_icounts.is_buyable', $post['buyable'])->where('product_icounts.is_sellable', $post['buyable']);
        }

        if (isset($post['product_code'])) {
            $product->where('code', $post['product_code']);
        }
        if (isset($post['id_item'])) {
            $product->where('id_item', $post['id_item'])->with(['product_icount_outlet_stocks'  => function($query){
                $query->join('outlets','outlets.id_outlet','=', 'product_icount_outlet_stocks.id_outlet');
                $query->groupBy('product_icount_outlet_stocks.id_outlet');
                $query->SelectRaw( 'group_concat(product_icount_outlet_stocks.unit) as units, 
                                    group_concat(product_icount_outlet_stocks.stock) as stock,
                                    id_product_icount,
                                    product_icount_outlet_stocks.id_outlet,
                                    outlet_name');
            }]);
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
            $product = $product->orderBy('id_product_icount','ASC');
        }

        if(isset($post['admin_list'])){
            $product = $product->withCount('product_detail')->withCount('product_detail_hiddens')->with(['brands']);
        }

        if ($request->q) {
            $product->where('name', 'like', '%' . $request->q . '%');
        }

        if ($request->for_select2) {
            if ($request->id_outlet && $outlet = Outlet::with('location_outlet')->find($request->id_outlet)) {
                $product->with(['product_icount_outlet_stocks' => function($query) use ($request) {
                    $query->where('id_outlet', $request->id_outlet);
                }]);
                $product->where('company_type', $outlet->location_outlet->company_type == 'PT IMS' ? 'ims' : 'ima');
            }
            $product->select('id_product_icount', 'name');
            return MyHelper::checkGet($product->get()->toArray());
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

        foreach($product as $key => $p){
            if(isset($p['unit_icount'])){
                $unit_while = [];
                foreach($p['unit_icount'] as $unit){
                    $unit_while[] = $unit['unit'];
                }
                $unit_while = implode(",",$unit_while);
                $product[$key]['units'] = $unit_while;
            }
        }

        if(isset($post['catalog'])){
            $catalog = ProductCatalogDetail::where('id_product_catalog',$post['catalog']);
            if(isset($post['from'])){
                $catalog = $catalog->where('budget_code', $post['from']);
            }
            $catalog = $catalog->get()->toArray();
            $new_product = [];
            foreach($product as $val){
                $check = false;
                foreach($catalog as $cat){
                    if($val['id_product_icount'] == $cat['id_product_icount']){
                        $check = true;
                        $val['budget_code'] = $cat['budget_code'];
                    }
                }
                if($check){
                    $new_product[] = $val;
                }
            }
            return $new_product;

        }

        return response()->json(MyHelper::checkGet($product));
    }

    function deleteIcount(DeleteIcount $request) {
        $product = ProductIcount::find($request->json('id_product_icount'));

    	if ($product) {

    		// delete product
    		$delete = ProductIcount::where('id_product_icount', $request->json('id_product_icount'))->delete();

            if($delete){
                $result = [
                    'status' => 'success',
                    'product' => [
                        'id_product_icount' => $product['id_product_icount'],
                        'plu_id' => $product['code'],
                        'product_name' => $product['name'],
                    ],
                ];
            }
			else{
                $result = ['status' => 'fail', 'messages' => ['failed to delete data']];
            }

    		return response()->json($result);

    	}
    	else {
    		return response()->json([
				'status'   => 'fail',
				'messages' => ['product has been used.']
    		]);
    	}

    }
    public function commission(Request $request){
        if(isset($request->product_code)){
            $product = Product::where(array('product_code'=>$request->product_code))->first();
            $commission = ProductCommissionDefault::where(array('id_product'=>$product->id_product))->with(['dynamic_rule'=> function($d){$d->orderBy('qty','desc');}])->first();
            if($commission && ($commission['dynamic_rule'])){
                $dynamic_rule = [];
                $count = count($commission['dynamic_rule']) - 1;
                foreach($commission['dynamic_rule'] as $key => $value){
                    if($count==$key || $count==0){
                        $for_null = $value['qty']-1;
                        if($count!=0){
                            $dynamic_rule[] = [
                                'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                'qty' => $value['qty'].' - '.($commission['dynamic_rule'][$key-1]['qty']-1),
                                'value' => $value['value']
                            ];
                        }else{
                            $dynamic_rule[] = [
                                'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                'qty' => '>= '.$value['qty'],
                                'value' => $value['value']
                            ];
                        }
                        if($value['qty']!=1){
                            $dynamic_rule[] = [
                                'id_product_commission_default_dynamic' => null,
                                'qty' => '0 - '.$for_null,
                                'value' => 0
                            ];
                        }
                    }else{
                        if($key==0){
                            $dynamic_rule[] = [
                                'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                'qty' => '>= '.$value['qty'],
                                'value' => $value['value']
                            ];
                        }else{
                            $before = $commission['dynamic_rule'][$key-1]['qty'] - 1;
                            if($before == $value['qty']){
                                $qty = $value['qty'];
                            }else{
                                $qty = $value['qty'].' - '.$before;
                            }
                            $dynamic_rule[] = [
                                'id_product_commission_default_dynamic' => $value['id_product_commission_default_dynamic'],
                                'qty' => $qty,
                                'value' => $value['value']
                            ];
                        }
                    }
                }
                $commission['dynamic_rule_list'] = $dynamic_rule;
            }
            return MyHelper::checkGet($commission);
        }

        return MyHelper::checkGet(null);
    }
    public function commission_create(Commission $request){
        if($request->percent == 'on'){
            $percent = 1;
        }else{
            $percent = 0;
        }
        if(isset($request->type)){
            if($request->type == 'Static'){
                $dynamic = 0;
            }else{
                $dynamic = 1;
            }
        }
        DB::beginTransaction();
        if(isset($request->product_code)){
            $code = Product::where('product_code',$request->product_code)->first();
            if($code){
                $product = ProductCommissionDefault::where(array('id_product'=>$code->id_product))->first();
                if($product){
                    $product->percent = $percent;
                    $product->commission = $request->commission;
                    $product->dynamic = $dynamic;
                    $product->save();
                }else{
                    $product = ProductCommissionDefault::create([
                        'id_product'=>$code->id_product,
                        'percent'=>$percent,
                        'commission'=>$request->commission,
                        'dynamic'=>$dynamic
                    ]);
                }
                if(!$product){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed update commission product']]);
                }

                if($dynamic==1){
                    $delete = ProductCommissionDefaultDynamic::where('id_product_commission_default',$product->id_product_commission_default)->delete();
                    $dynamic_rule = [];
                    $check_unique = [];
                    if(!isset($request->dynamic_rule) && empty($request->dynamic_rule)){
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Dynamic rule cant be null']]);
                    }
                    foreach($request->dynamic_rule ?? [] as $data_rule){
                        $dynamic_rule[] = [
                            'id_product_commission_default' => $product->id_product_commission_default,
                            'qty' => $data_rule['qty'],
                            'value' => $data_rule['value'],
                        ];
                        if(isset($check_unique[$data_rule['qty']])){
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Duplicated range']]);
                        }else{
                            $check_unique[$data_rule['qty']] = $data_rule;
                        }
                    }
                    
                    $create = ProductCommissionDefaultDynamic::insert($dynamic_rule);
                }else{
                    $delete = ProductCommissionDefaultDynamic::where('id_product_commission_default',$product->id_product_commission_default)->delete();
                }

                DB::commit();
                return response()->json(MyHelper::checkCreate($product));
            }
        }
        DB::rollback();
        $result = ['status' => 'fail', 'messages' => ['failed to delete data']];
        return response()->json($result);
    }

    public function deleteCommission(Request $request){
        $post = $request->all();
        if(isset($post['id_product_commission_default_dynamic'])){
            $delete = ProductCommissionDefaultDynamic::where('id_product_commission_default_dynamic',$post['id_product_commission_default_dynamic'])->delete();
            return response()->json([
                'status'   => 'success',
            ]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }

    public function deleteProductCommission(Request $request){
        $post = $request->all();
        if(isset($post['id_product_commission_default'])){
            $delete = ProductCommissionDefault::where('id_product_commission_default',$post['id_product_commission_default'])->delete();
            return response()->json([
                'status'   => 'success',
            ]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }

    public function unitDetailIcount(Request $request){
        $post = $request->all();
        if(isset($post['id_product_icount']) && !empty($post['id_product_icount'])){
            $units = UnitIcount::with(['conversion'])->where('id_product_icount',$post['id_product_icount'])->get()->toArray();
            return response()->json(['status' => 'success', 'result' => $units]);
        }else{
            return response()->json([
				'status'   => 'fail',
				'messages' => ['Incompleted Data']
    		]);
        }
    }

    public function saveUnitDetailIcount(Request $request){
        $post = $request->all();
        if(isset($post) && !empty($post)){
            DB::beginTransaction();
            foreach($post['conversion'] as $unit => $value){
                $save_unit = UnitIcount::updateOrCreate(['id_product_icount' => $post['id_product_icount'], 'unit' => $unit],[]);
                if($save_unit){
                    $conversion = $this->saveUnitDetailIcountConversion($save_unit['id_unit_icount'],$value);
                    if(!$conversion){
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Incompleted Data']
                        ]);
                    }
                }
            }
            DB::commit();
            return response()->json([
                'status'   => 'success',
            ]);
        }else{
            return response()->json([
				'status'   => 'fail',
				'messages' => ['Incompleted Data']
    		]);
        }
    }

    public function saveNewUnit(Request $request){
        $post = $request->all();
        $save_unit = UnitIcount::updateOrCreate(['id_product_icount' => $post['id_product_icount'], 'unit' => $post['unit']],[]);
        if($save_unit){
            return response()->json([
                'status'   => 'success',
            ]);
        }else{
            return response()->json([
				'status'   => 'fail',
				'messages' => ['Incompleted Data']
    		]);
        }

    }
    
    public function saveUnitDetailIcountConversion($id,$values){
        $post = $values;
        unset($post['id_product_icount']);
        DB::beginTransaction();
        if(isset($post) && !empty($post)){
            $table = new UnitIcountConversion;
            $col = 'id_unit_icount';
    
            $data = [];
            foreach ($post as $value) {
                if(isset($value['qty_conversion']) && isset($value['unit_conversion'])){
                    $push =  [
                        $col 	=> $id,
                        'unit_conversion'  => $value['unit_conversion'],
                    ];
                    $val = [
                        'qty_conversion'  => $value['qty_conversion'],
                    ];
                    $save = $table::updateOrCreate($push,$val);
                    if(!$save){
                        DB::rollback();
                        return false;
                    }
                }
            }
        }
        DB::commit();
        return true;
    }
      public function setting_service(Request $request){
     
        $product = Product::where(
                array(
                    'product_type'=>'service',
                    'product_visibility'=>'Visible',
                    ))
                ->select('id_product','product_name')
                ->get();
           
        return MyHelper::checkGet($product);
    }

    public function productIcountSyncUpdate(){
        $array = [];

        $product_product_icounts = ProductProductIcount::select('id_product_icount')->groupBy('id_product_icount')->get()->toArray();
        $product_product_icounts = array_column($product_product_icounts, 'id_product_icount');
        $array = array_merge($array, $product_product_icounts);

        $location_bundligs = LocationOutletStarterBundlingProduct::select('id_product_icount')->groupBy('id_product_icount')->get()->toArray();
        $location_bundligs = array_column($location_bundligs, 'id_product_icount');
        $array = array_merge($array, $location_bundligs);

        $outlet_bundling = OutletStarterBundlingProduct::select('id_product_icount')->groupBy('id_product_icount')->get()->toArray();
        $outlet_bundling = array_column($outlet_bundling, 'id_product_icount');
        $array = array_merge($array, $outlet_bundling);

        $rembus = EmployeeReimbursement::select('id_product_icount')->groupBy('id_product_icount')->get()->toArray();
        $rembus = array_column($rembus, 'id_product_icount');
        $array = array_merge($array, $rembus);

        $cash = EmployeeCashAdvance::select('id_product_icount')->groupBy('id_product_icount')->get()->toArray();
        $cash = array_column($cash, 'id_product_icount');
        $array = array_merge($array, $cash);

        $cash_prod = EmployeeCashAdvanceProductIcount::select('id_product_icount')->groupBy('id_product_icount')->get()->toArray();
        $cash_prod = array_column($cash_prod, 'id_product_icount');
        $array = array_merge($array, $cash_prod);

        $req_prod = RequestProductDetail::select('id_product_icount')->groupBy('id_product_icount')->get()->toArray();
        $req_prod = array_column($req_prod, 'id_product_icount');
        $array = array_merge($array, $req_prod);

        $dev_prod = DeliveryProductDetail::select('id_product_icount')->groupBy('id_product_icount')->get()->toArray();
        $dev_prod = array_column($dev_prod, 'id_product_icount');
        $array = array_merge($array, $dev_prod);

        $catlog = ProductCatalogDetail::select('id_product_icount')->groupBy('id_product_icount')->get()->toArray();
        $catlog = array_column($catlog, 'id_product_icount');
        $array = array_merge($array, $catlog);

        $array = array_unique($array);

        $exclude = ProductIcount::whereNotIn('id_product_icount', $array)->select('id_product_icount')->groupBy('id_product_icount')->get()->toArray();
        $exclude = array_column($exclude, 'id_product_icount');

        DB::beginTransaction();
        try {
            
            $unit_icount = UnitIcount::whereIn('id_product_icount', $exclude)->select('id_unit_icount')->get()->toArray();
            $unit_icount = array_column($unit_icount, 'id_unit_icount');
            
            $unit_icount_con = UnitIcountConversion::whereIn('id_unit_icount', $unit_icount)->delete();
            if($unit_icount_con){
                $delete_unit_icount = UnitIcount::whereIn('id_product_icount', $exclude)->delete();
                if(!$delete_unit_icount){
                    DB::rollBack();
                }
            }else{
                DB::rollBack();
            }

            $stock = ProductIcountOutletStock::whereIn('id_product_icount', $exclude)->get();
            if($stock){
                $delete_stock = ProductIcountOutletStock::whereIn('id_product_icount', $exclude)->delete();
                if(!$delete_stock){
                    DB::rollBack();
                }
            }

            $delete_icount = ProductIcount::whereIn('id_product_icount', $exclude)->delete();
            if(!$delete_icount){
                DB::rollBack();
            }

            DB::commit();

            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::debug($e);
        }

    }
}
