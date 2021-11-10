<?php

namespace Modules\PortalPartner\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignOutlet;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignProductDiscount;
use Modules\PromoCampaign\Entities\PromoCampaignProductDiscountRule;
use Modules\PromoCampaign\Entities\PromoCampaignTierDiscountProduct;
use Modules\PromoCampaign\Entities\PromoCampaignTierDiscountRule;
use Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyProductRequirement;
use Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyRule;
use Modules\PromoCampaign\Entities\PromoCampaignHaveTag;
use Modules\PromoCampaign\Entities\PromoCampaignTag;
use Modules\PromoCampaign\Entities\PromoCampaignReport;
use Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\PromoCampaignDiscountBillRule;
use Modules\PromoCampaign\Entities\PromoCampaignDiscountBillProduct;
use Modules\PromoCampaign\Entities\PromoCampaignDiscountDeliveryRule;
use Modules\PromoCampaign\Entities\PromoCampaignShipmentMethod;
use Modules\PromoCampaign\Entities\PromoCampaignPaymentMethod;
use Modules\PromoCampaign\Entities\PromoCampaignBrand;
use Modules\PromoCampaign\Entities\PromoCampaignBuyxgetyProductModifier;
use Modules\PromoCampaign\Entities\PromoCampaignOutletGroup;

use Modules\Deals\Entities\DealsProductDiscount;
use Modules\Deals\Entities\DealsProductDiscountRule;
use Modules\Deals\Entities\DealsTierDiscountProduct;
use Modules\Deals\Entities\DealsTierDiscountRule;
use Modules\Deals\Entities\DealsBuyxgetyProductRequirement;
use Modules\Deals\Entities\DealsBuyxgetyRule;
use Modules\Deals\Entities\DealsBuyxgetyProductModifier;
use Modules\Deals\Entities\DealsDiscountBillRule;
use Modules\Deals\Entities\DealsDiscountBillProduct;
use Modules\Deals\Entities\DealsDiscountDeliveryRule;
use Modules\Deals\Entities\DealsShipmentMethod;
use Modules\Deals\Entities\DealsPaymentMethod;

use Modules\Promotion\Entities\DealsPromotionProductDiscount;
use Modules\Promotion\Entities\DealsPromotionProductDiscountRule;
use Modules\Promotion\Entities\DealsPromotionTierDiscountProduct;
use Modules\Promotion\Entities\DealsPromotionTierDiscountRule;
use Modules\Promotion\Entities\DealsPromotionBuyxgetyProductRequirement;
use Modules\Promotion\Entities\DealsPromotionBuyxgetyRule;
use Modules\Promotion\Entities\DealsPromotionBuyxgetyProductModifier;
use Modules\Promotion\Entities\DealsPromotionDiscountBillRule;
use Modules\Promotion\Entities\DealsPromotionDiscountBillProduct;
use Modules\Promotion\Entities\DealsPromotionDiscountDeliveryRule;
use Modules\Promotion\Entities\DealsPromotionShipmentMethod;
use Modules\Promotion\Entities\DealsPromotionPaymentMethod;

use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;

use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\BrandOutlet;

use App\Http\Models\User;
use App\Http\Models\Campaign;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\Setting;
use App\Http\Models\Voucher;
use App\Http\Models\Treatment;
use App\Http\Models\Deal;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsPromotionTemplate;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantPivot;

use Modules\Product\Entities\ProductModifierGroupPivot;

use Modules\Outlet\Entities\OutletGroup;

use Modules\PromoCampaign\Http\Requests\Step1PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\Step2PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\DeletePromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\ValidateCode;
use DataTables;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Lib\MyHelper;
use App\Jobs\GeneratePromoCode;
use App\Jobs\ExportPromoCodeJob;
use DB;
use Hash;
use Modules\SettingFraud\Entities\DailyCheckPromoCode;
use Modules\SettingFraud\Entities\LogCheckPromoCode;
use Illuminate\Support\Facades\Auth;
use File;
use App\Lib\WeHelpYou;
use Modules\PortalPartner\Http\Requests\Promo\DetailPromoCampaign;

class ApiPromoCampaign extends Controller
{

	function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->online_transaction   = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->fraud   = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->deals   = "Modules\Deals\Http\Controllers\ApiDeals";
        $this->voucher   = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->subscription   = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
        $this->promo       	= "Modules\PromoCampaign\Http\Controllers\ApiPromo";
        $this->autocrm      = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
    
  
     function listPromoCampaignBefore(Request $request) 
    {
    	$post = $request->json()->all();
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        $query = PromoCampaign::join('promo_campaign_brands','promo_campaign_brands.id_promo_campaign','promo_campaigns.id_promo_campaign')
                 ->join('brands','brands.id_brand','promo_campaign_brands.id_brand')
                 ->join('brand_outlet','brand_outlet.id_brand','brands.id_brand')
                 ->join('outlets','outlets.id_outlet','brand_outlet.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner))
                 ->OrderBy('promo_campaigns.id_promo_campaign', 'DESC')
                ->Select('promo_campaigns.*','brands.*','outlets.*');
        
        if ($request->json('rule')){
             $this->filterList($query,$request->json('rule'),$request->json('operator')??'and');
        }
        $query = $query->distinct()->get()->toArray();
        $result = array();
        foreach ($query as $value) {
            if(strtotime($value['date_start'])>= strtotime(date('Y-m-d H:i:s'))) { 
                array_push($result,$value);
            }
        }    
        //jika mobile di pagination
        if (!$request->json('web')) {
            $result = $this->paginate($result);
            $resultMessage = 'Nantikan penawaran menarik dari kami';
            return response()->json(MyHelper::checkGet($result, $resultMessage));
        }
        else{
            $result = $this->paginate($result);
            return response()->json(MyHelper::checkGet($result));
        }
       
    }
   public function listPromoCampaignActive(Request $request)
    {
        $post = $request->json()->all();
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        $query = PromoCampaign::join('promo_campaign_brands','promo_campaign_brands.id_promo_campaign','promo_campaigns.id_promo_campaign')
                 ->join('brands','brands.id_brand','promo_campaign_brands.id_brand')
                 ->join('brand_outlet','brand_outlet.id_brand','brands.id_brand')
                 ->join('outlets','outlets.id_outlet','brand_outlet.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner))
                 ->OrderBy('promo_campaigns.id_promo_campaign', 'DESC')
                ->Select('promo_campaigns.*','brands.*','outlets.*');
        
        if ($request->json('rule')){
             $this->filterList($query,$request->json('rule'),$request->json('operator')??'and');
        }
        $query = $query->distinct()->get()->toArray();
        $result = array();
        foreach ($query as $value) {
            if(strtotime($value['date_start'])<= strtotime(date('Y-m-d H:i:s'))&&strtotime($value['date_end'])>= strtotime(date('Y-m-d H:i:s'))) { 
                array_push($result,$value);
            }
        }    
        //jika mobile di pagination
        if (!$request->json('web')) {
            $result = $this->paginate($result);
            $resultMessage = 'Nantikan penawaran menarik dari kami';
            return response()->json(MyHelper::checkGet($result, $resultMessage));
        }
        else{
            $result = $this->paginate($result);
            return response()->json(MyHelper::checkGet($result));
        }
    }
  
   public function filterList($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }
        $where=$operator=='and'?'where':'orWhere';
        $subjects=['campaign_name','promo_title','code_type','user_type','product_type','promo_type'];
        $i = 1;
        foreach ($subjects as $subject) {
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    if($i<=1){
                    $query->where($subject,$rule[0],$rule[1]);
                    }else{
                    $query->$where($subject,$rule[0],$rule[1]);    
                    }
                    $i++;
                }
            }
        }
       
       
        if($rules2=$newRule['id_outlet']??false){
            foreach ($rules2 as $rule) {
                $query->$where('outlets.id_outlet',$rule[0],$rule[1]);
            }
        }
        if($rules2=$newRule['id_brand']??false){
            foreach ($rules2 as $rule) {
                    $query->$where('brands.id_brand',$rule[0],$rule[1]);
            }
        }
    }
   
    function paginate($query)
	{
		$query = DataTables::of($query)
                        ->addColumn('action', function ($data) {
                            $slug = MyHelper::createSlug($data['id_promo_campaign'], $data['created_at']);
                            $btnDelete = '<a class="btn btn-sm btn-primary text-nowrap" href="'. env('APP_URL').'report/promo/promo-campaign/detail/'.$slug.'"><i class="fa fa-search" style="font-size : 14px; padding-right : 0px"></i></a>';  
                             return '<div class="btn-group btn-group" role="group">'. $btnDelete.'</div>';
                         })
                        ->editColumn('date_start', function($row) {
                            $publish_start = date('d M Y', strtotime($row['date_start']));
                            return $publish_start;
                          })
                        ->editColumn('date_end', function($row) {
                            $publish_start = date('d M Y', strtotime($row['date_end']));
                            return $publish_start;
                          })
                ->make(true);
                
		return $query;
	}
    public function detail(DetailPromoCampaign $request)
    {
        
        $post = $request->json()->all();
        $data = [
            'user' => function($q){
            	$q->select('id', 'name', 'level');
            },
            'promo_campaign_have_tags.promo_campaign_tag',
            'outlets',
            'outlet_groups',
            'promo_campaign_product_discount_rules',
            'promo_campaign_discount_bill_rules',
            'promo_campaign_discount_bill_products.product',
            'promo_campaign_discount_bill_products.brand',
            'promo_campaign_discount_bill_products.product_variant_pivot.product_variant',
            'promo_campaign_discount_delivery_rules',
            'promo_campaign_product_discount.product.category',
            'promo_campaign_product_discount.brand',
            'promo_campaign_product_discount.product_variant_pivot.product_variant',
            'promo_campaign_tier_discount_rules',
            'promo_campaign_tier_discount_product.product',
            'promo_campaign_tier_discount_product.brand',
            'promo_campaign_tier_discount_product.product_variant_pivot.product_variant',
            'promo_campaign_buyxgety_rules.product',
            'promo_campaign_buyxgety_rules.brand',
            'promo_campaign_buyxgety_rules.product_variant_pivot.product_variant',
            'promo_campaign_buyxgety_rules.promo_campaign_buyxgety_product_modifiers.modifier',
            'promo_campaign_buyxgety_product_requirement.product',
            'promo_campaign_buyxgety_product_requirement.brand',
            'promo_campaign_buyxgety_product_requirement.product_variant_pivot.product_variant',
            'promo_campaign_shipment_method',
            'promo_campaign_payment_method',
            'brands',
            'brand',
            'promo_campaign_reports'
        ];
        $id = MyHelper::explodeSlug($post['id_promo_campaign'])[0]??'';
        $promoCampaign = PromoCampaign::with($data)->where('id_promo_campaign', '=',$id)->first();
        if ($promoCampaign['code_type'] == 'Single') {
        	$promoCampaign->load('promo_campaign_promo_codes');
        }
        $promoCampaign = $promoCampaign->toArray();
        if ($promoCampaign) {

            $promoCampaign['used_code'] = PromoCampaignReport::where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign'])->count();
            $total = PromoCampaignReport::where('promo_campaign_reports.id_promo_campaign', $post['id_promo_campaign']);
            $this->filterReport($total,$request,$foreign);
            foreach ($foreign as $value) {
                $total->leftJoin(...$value);
            }
            $promoCampaign['total'] = $total->count();

            $total2 = PromoCampaignPromoCode::join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')->where('promo_campaign_promo_codes.id_promo_campaign', $post['id_promo_campaign']);
            $this->filterCoupon($total2,$request,$foreign);
            foreach ($foreign as $value) {
                $total->leftJoin(...$value);
            }
            $promoCampaign['total2'] = $total2->count();
            $getProduct = $this->getProduct('promo_campaign',$promoCampaign);
    		$desc = $this->getPromoDescription('promo_campaign', $promoCampaign, $getProduct['product']??'', true);

    		$promoCampaign['description'] = $desc;

            $result = [
                'status'  => 'success',
                'result'  => $promoCampaign
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'message'  => ['Promo Campaign Not Found']
            ];
        }
        return response()->json($result);
    }
    protected function filterReport($query, $request,&$foreign='')
    {
        // $query->groupBy('promo_campaign_reports.id_promo_campaign_report');
        $allowed = array(
            'operator' => ['=', 'like', '<', '>', '<=', '>='],
            'subject' => ['promo_code','user_phone','created_at','receipt_number','id_outlet','device_type','outlet_count','user_count'],
            'mainSubject' => ['user_phone','created_at','id_outlet','device_type']
        );
        $return = [];
        $where = $request->json('operator') == 'or' ? 'orWhere' : 'where';
        $rule = $request->json('rule');
        $query->where(function($queryx) use ($rule,$allowed,$where,$query,&$foreign,$request){
            $foreign=array();
            $outletCount=0;
            $userCount=0;
            foreach ($rule??[] as $value) {
                if (!in_array($value['subject'], $allowed['subject'])) {
                    continue;
                }
                if (!(isset($value['operator']) && $value['operator'] && in_array($value['operator'], $allowed['operator']))) {
                    $value['operator'] = '=';
                }
                if ($value['operator'] == 'like') {
                    $value['parameter'] = '%' . $value['parameter'] . '%';
                }
                if (in_array($value['subject'], $allowed['mainSubject'])) {
                    if($value['subject']=='created_at'){
                        $queryx->$where(\DB::raw('UNIX_TIMESTAMP(promo_campaign_reports.'.$value['subject'].')'), $value['operator'], strtotime($value['parameter']));
                    }else{
                        $queryx->$where('promo_campaign_reports.'.$value['subject'], $value['operator'], $value['parameter']);
                    }
                } else {
                    switch ($value['subject']) {
                        case 'promo_code':
                        $foreign['promo_campaign_promo_codes']=['promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign_promo_code','=','promo_campaign_reports.id_promo_campaign_promo_code'];
                        $queryx->$where('promo_code', $value['operator'], $value['parameter']);
                        break;
                        
                        case 'receipt_number':
                        $foreign['transactions']=['transactions','transactions.id_transaction','=','promo_campaign_reports.id_transaction'];
                        $queryx->$where('transaction_receipt_number', $value['operator'], $value['parameter']);
                        break;

                        case 'outlet_count':
                        if(!$outletCount){
                            $query->addSelect('outlet_total');
                            $outletCount=1;
                        }
                        $foreign['t2']=[\DB::raw('(SELECT COUNT(*) AS outlet_total, id_outlet FROM `promo_campaign_reports` WHERE id_promo_campaign = '.$request->json('id_promo_campaign').' GROUP BY id_outlet) AS `t2`'),'promo_campaign_reports.id_outlet','=','t2.id_outlet'];
                        $queryx->$where('outlet_total', $value['operator'], $value['parameter']);
                        break;


                        case 'user_count':
                        if(!$userCount){
                            $query->addSelect('user_total');
                            $userCount=1;
                        }
                        $foreign['t3']=[\DB::raw('(SELECT COUNT(*) AS user_total, id_user FROM `promo_campaign_reports` WHERE id_promo_campaign = '.$request->json('id_promo_campaign').' GROUP BY id_user) AS `t3`'),'promo_campaign_reports.id_user','=','t3.id_user'];
                        $queryx->$where('user_total', $value['operator'], $value['parameter']);
                        break;

                        default:
                            # code...
                        break;
                    }
                }
                $return[] = $value;
            }
        });
        return ['filter' => $return, 'filter_operator' => $request->json('operator')];
    }
    protected function filterCoupon($query, $request,&$foreign='')
    {
        // $query->groupBy('promo_campaign_promo_codes.id_promo_campaign_promo_code');
        $allowed = array(
            'operator' => ['=', 'like', '<', '>', '<=', '>='],
            'subject' => ['coupon_code','status','used','available','max_used'],
        );
        $return = [];
        $where = $request->json('operator2') == 'or' ? 'orWhere' : 'where';
        $whereRaw = $request->json('operator2') == 'or' ? 'orWhereRaw' : 'whereRaw';
        $rule = $request->json('rule2');
        $query->where(function($queryx) use ($rule,$allowed,$where,$query,&$foreign,$request,$whereRaw){
            $foreign=array();
            $outletCount=0;
            $userCount=0;
            foreach ($rule??[] as $value) {
                if (!in_array($value['subject'], $allowed['subject'])) {
                    continue;
                }
                if (!(isset($value['operator']) && $value['operator'] && in_array($value['operator'], $allowed['operator']))) {
                    $value['operator'] = '=';
                }
                if ($value['operator'] == 'like') {
                    $value['parameter'] = '%' . $value['parameter'] . '%';
                }
                switch ($value['subject']) {
                    case 'coupon_code':
                    $queryx->$where('promo_code', $value['operator'], $value['parameter']);
                    break;
                    
                    case 'status':
                    if ($value['parameter'] == 'Not used') 
                    {
                        $queryx->$where('usage', '=', 0);
                    }
                    elseif( $value['parameter'] == 'Used' )
                    {
                        $queryx->$where('usage', '=', 'code_limit');
                    }
                    else
                    {
                        $queryx->$where('usage', '!=', 0)->$where('usage', '!=', 'code_limit');
                    }

                    break;

                    case 'used':
                    $queryx->$where('usage', $value['operator'], $value['parameter']);
                    break;

                    case 'available':
                    $queryx->$whereRaw('code_limit - promo_campaign_promo_codes.usage '.$value['operator'].' '.$value['parameter']);
                    break;

                    case 'max_used':
                    $queryx->$where('code_limit', $value['operator'], $value['parameter']);
                    break;

                    default:
                        # code...
                    break;
                }

                $return[] = $value;
            }
        });
        return ['filter' => $return, 'filter_operator' => $request->json('operator')];
    }
    public function getProduct($source, $query, $id_outlet=null)
    {
    	$default_product = $query['product_rule'] === 'and' ? 'semua produk bertanda khusus' : 'produk bertanda khusus';

    	if ($source == 'subscription') 
    	{
    		if ( !empty($query['is_all_product']) || empty($query['subscription_products']) ) {
    			$applied_product = '*';
	        	$product = $default_product;
    		}
    		elseif( !empty($query['subscription_products']) )
    		{
    			if (!$query['id_brand']) {
    				$brand = BrandProduct::join('brand_outlet','brand_product.id_brand','=','brand_outlet.id_brand')
    						->where('brand_outlet.id_outlet',$id_outlet)
    						->where('brand_product.id_product',$query['subscription_products'][0]['id_product'])
    						->whereNotNull('brand_product.id_product_category')
    						->first();
    			}

    			$applied_product = $query['subscription_products'];
    			// $applied_product[0]['id_brand'] = $query['id_brand'] ?? $brand['id_brand'];
    			$applied_product[0]['product_code'] = $applied_product[0]['product']['product_code'];

    			$product_total = count($query['subscription_products']);
    			if ($product_total == 1) {
	        		$product = $query['subscription_products'][0]['product']['product_name'] ?? $default_product;
	        		if (isset($query['subscription_products'][0]['id_product_variant_group'])) {
	        			$variant = ProductVariantPivot::join('product_variants as pv', 'pv.id_product_variant', 'product_variant_pivot.id_product_variant')
	        						->where('product_variant_pivot.id_product_variant_group', $query['subscription_products'][0]['id_product_variant_group'])
	        						->pluck('product_variant_name')->toArray();
	        			$variant_text = implode(' ', $variant);
	        			$product .= ' '.$variant_text;
	        		}
    			}else{
	        		$product = $default_product;
    			}
    		}
    		else
    		{
    			$applied_product = [];
	        	$product = [];
    		}
    	}
    	else
    	{
    		if ( ($query[$source.'_product_discount_rules']['is_all_product']??false) == 1 
    			|| ($query['promo_type']??false) == 'Referral' 
    			|| ($query[$source.'_discount_bill_rules']['is_all_product']??false) == 1
    			|| ($query[$source.'_tier_discount_rules'][0]['is_all_product']??false) == 1
    			|| ($query[$source.'_buyxgety_rules'][0]['is_all_product']??false) == 1
    		) {
	        	$applied_product = '*';
	        	$product = $default_product;
	        }else{
	    		$applied_product = $query[$source.'_product_discount'] ?: $query[$source.'_tier_discount_product'] ?: $query[$source.'_buyxgety_product_requirement'] ?: $query[$source.'_discount_bill_products'] ?: [];

	    		if(empty($applied_product)){
	        		$product = null;
	    		}elseif (count($applied_product) == 1) {
	        		$product = $applied_product[0]['product']['product_name'] ?? $default_product;
	        		if (isset($applied_product[0]['id_product_variant_group'])) {
	        			$variant = ProductVariantPivot::join('product_variants as pv', 'pv.id_product_variant', 'product_variant_pivot.id_product_variant')
	        						->where('product_variant_pivot.id_product_variant_group', $applied_product[0]['id_product_variant_group'])
	        						->pluck('product_variant_name')->toArray();
	        			$variant_text = implode(' ', $variant);
	        			$product .= ' '.$variant_text;
	        		}
	        	}else{
	        		$product = $default_product;
	        	}
	        }

	    	/*
	    	if ( ($query[$source.'_product_discount_rules']['is_all_product']??false) == 1 || ($query['promo_type']??false) == 'Referral') 
	        {
	        	$applied_product = '*';
	        	$product = $default_product;
	        }
	        elseif ( !empty($query[$source.'_product_discount']) )
	        {
	        	$applied_product = $query[$source.'_product_discount'];
	        	if (count($applied_product) == 1) {
	        		$product = $applied_product[0]['product']['product_name'] ?? $default_product;
	        	}else{
	        		$product = $default_product;
	        	}
	        }
	        elseif ( !empty($query[$source.'_tier_discount_product']) )
	        {
	        	$applied_product = $query[$source.'_tier_discount_product'];
	        	if (count($applied_product) == 1) {
	        		$product = $applied_product[0]['product']['product_name'] ?? $default_product;
	        	}else{
	        		$product = $default_product;
	        	}
	        }
	        elseif ( !empty($query[$source.'_buyxgety_product_requirement']) )
	        {
	        	$applied_product = $query[$source.'_buyxgety_product_requirement'];
	        	if (count($applied_product) == 1) {
	        		$product = $applied_product[0]['product']['product_name'] ?? $default_product;
	        	}else{
	        		$product = $default_product;
	        	}
	        }
	        elseif ( !empty($query[$source.'_discount_bill_rules']) )
	        {
	        	if ($query[$source.'_discount_bill_rules']['is_all_product'] === 0) {
	        		$applied_product = $query[$source.'_discount_bill_products'];
		        	if (count($applied_product) == 1) {
		        		$product = $applied_product[0]['product']['product_name'] ?? $default_product;
		        	}else{
		        		$product = $default_product;
		        	}
	        	}
	        	else{
		        	$applied_product = '*';
		        	$product = $default_product;
	        	}
	        }
	        else
	        {
	        	$applied_product = [];
	        	$product = [];
	        }*/
    	}

        $result = [
        	'applied_product' => $applied_product,
        	'product' => $product
        ];
        return $result;
    }
     public function getPromoDescription($source, $query, $product, $use_global = false)
    {
    	if (!empty($query['promo_description']) && !$use_global) {
    		return $query['promo_description'];
    	}

    	$brand = $query['brand']['name_brand']??null;

    	$payment_text = null;
    	if (!empty($query[$source.'_payment_method']) && $query['is_all_payment'] != 1) {
    		$available_payment = config('payment_method');
    		$payment_list = [];
    		foreach ($available_payment as $key => $value) {
    			$payment_list[$value['payment_method']] = $value['text'];
    		}
    		$payment_text = '';
    		$payment_count 	= count($query[$source.'_payment_method']);
    		$i = 1;
    		foreach ($query[$source.'_payment_method'] as $key => $value) {
    			if ($i == 1) {
    				$payment_text .= $payment_list[$value['payment_method']];
    			}
    			elseif ($i == $payment_count) {
    				$payment_text .= ' maupun '.$payment_list[$value['payment_method']];
    			}
    			else {
    				$payment_text .= ', '.$payment_list[$value['payment_method']];
    			}

    			$i++;
    		}
    		if ($payment_text) {
    			$payment_text = 'berlaku untuk pembayaran menggunakan '.$payment_text;
    		}
    	}else{
    		// $payment_text = 'semua metode pembayaran';
    		$payment_text = null;
    	}

    	$shipment_text = null;
    	if (!empty($query[$source.'_shipment_method']) && $query['is_all_shipment'] != 1) {
    		$online_trx = app($this->online_transaction);
    		$shipment_list = array_column($query[$source.'_shipment_method'], 'shipment_method');

    		$shipment_list = array_flip($shipment_list);
    		if (isset($shipment_list['GO-SEND'])) {
    			$shipment_list['gosend'] = $shipment_list['GO-SEND'];
    			unset($shipment_list['GO-SEND']);
    		}
    		if (isset($shipment_list['Pickup Order'])) {
    			$temp['Pick Up'] = $shipment_list['Pickup Order'];
    			unset($shipment_list['Pickup Order']);
    			$shipment_list = $temp  + $shipment_list;

    		}

    		$shipment_list = array_flip($shipment_list);

			$setting_shipment_list =  $online_trx->listAvailableDelivery(WeHelpYou::listDeliveryRequest())['result']['delivery'] ?? [];
			$setting_shipment_list =  array_column($setting_shipment_list, 'code');

			$selected_shipment = [];
			if (array_diff($setting_shipment_list, $shipment_list)) {
	    		$shipment_list = array_map(function ($shipment) use ($online_trx) { 
	    			return $online_trx->getCourierName($shipment);
	    		}, $shipment_list);
			} else {
				$shipment_list[] = 'Delivery';
				$shipment_list = array_diff($shipment_list, $setting_shipment_list);
			}

    		$shipment_text = '';
    		$shipment_count = count($shipment_list);
    		$i = 1;
    		foreach ($shipment_list as $key => $value) {
    			if ($i == 1) {
    				$shipment_text .= $value;
    			}
    			elseif ($i == $shipment_count) {
    				$shipment_text .= ' maupun '.$value;
    			}
    			else {
    				$shipment_text .= ', '.$value;
    			}
    			$i++;
    		}

    		if ($shipment_text) {
    			$shipment_text = 'berlaku untuk '.$shipment_text;
    		}
    	}else{
    		// $shipment_text = 'semua tipe order';
    		$shipment_text = null;
    	}

		$global_text = '';
		if ($source == 'promo_campaign') {
			$promo = 'Kode Promo';
		}elseif ($source == 'subscription') {
			$promo = 'Subscription';
		}elseif ($source == 'deals' || $source == 'deals_promotion') {
			$promo = 'Voucher';
		}
		if (isset($payment_text) && isset($shipment_text)) {
			$global_text .= $promo.' '.'%shipment_text% dan %payment_text%';
		}elseif (isset($payment_text)) {
			$global_text .= $promo.' '.'%payment_text%';
		}elseif (isset($shipment_text)) {
			$global_text .= $promo.' '.'%shipment_text%';
		}

    	if ($source == 'subscription') 
    	{
    		if ( !empty($query['subscription_voucher_percent']) ) 
    		{
    			$discount = 'Percent';
    		}
    		else
    		{
    			$discount = 'Nominal';
    		}

        	if ( !empty($query['subscription_voucher_percent']) ) {
        		$discount = ($query['subscription_voucher_percent']??0).'%';
        	}else{
        		$discount = 'Rp '.number_format(($query['subscription_voucher_nominal']??0),0,',','.');
        	}

			if ($query['subscription_discount_type'] == 'discount_delivery') {
				$desc = 'Diskon ongkos kirim %discount%';
			}else{
	    		// $desc = Setting::where('key', '=', $key)->first()['value']??$key_null;
	    		$desc = 'Diskon %discount% untuk pembelian %product%';
			}

	    	$desc = MyHelper::simpleReplace($desc,['discount'=>$discount, 'product'=>$product, 'brand'=>$brand]);
    	}
    	else
    	{
	        if ($query['promo_type'] == 'Product discount') 
	        {
	        	$discount = $query[$source.'_product_discount_rules']['discount_type']??'Nominal';
	        	$qty = $query[$source.'_product_discount_rules']['max_product']??0;

	        	if ($discount == 'Percent') {
	        		$discount = ($query[$source.'_product_discount_rules']['discount_value']??0).'%';
	        	}else{
	        		$discount = 'Rp '.number_format(($query[$source.'_product_discount_rules']['discount_value']??0),0,',','.');
	        	}

	        	if ( empty($qty) ) {
        			$key = 'description_product_discount_brand_no_qty';
    				// $key_null = 'Anda berhak mendapatkan potongan %discount% untuk pembelian %product%';
    				$key_null = 'Diskon %discount% untuk pembelian %product%';
	        	}else{
	        		$key = 'description_product_discount_brand';
	    			// $key_null = 'Anda berhak mendapatkan potongan %discount% untuk pembelian %product%. Maksimal %qty% buah untuk setiap produk';
	    			$key_null = 'Diskon %discount% untuk pembelian %product%. Maksimal %qty% item untuk setiap produk';
	        	}

	    		// $desc = Setting::where('key', '=', $key)->first()['value']??$key_null;
	    		$desc = $key_null;

	    		$desc = MyHelper::simpleReplace($desc,['discount'=>$discount, 'product'=>$product, 'qty'=>$qty, 'brand'=>$brand]);
	    	}
	    	elseif ($query['promo_type'] == 'Tier discount') 
	    	{
	    		$min_qty = null;
	    		$max_qty = null;
	    		$discount_rule = [];

	    		foreach ($query[$source.'_tier_discount_rules'] as $key => $rule) {
					$min_req = $rule['min_qty'];
					$max_req = $rule['max_qty'];

					if($min_qty === null || $rule['min_qty'] < $min_qty){
						$min_qty = $min_req;
						$min_rule = $rule;
					}
					if($max_qty === null || $rule['max_qty'] > $max_qty){
						$max_qty = $max_req;
						$max_rule = $rule;
					}

					if ($rule['discount_type'] == 'Percent') {
		        		$discount_rule[] = ($rule['discount_value']??0).'%';
		        	}else{
		        		$discount_rule[] = 'Rp '.number_format(($rule['discount_value']??0),0,',','.');
		        	}
	    		}

	    		// if single rule
	    		if (count($discount_rule) == 1) {
	    			$discount = $discount_rule[0];
	    		}else{
	    			$discount_first = reset($discount_rule);
	    			$discount_last 	= end($discount_rule);
	    			$discount = $discount_first.' sampai '.$discount_last;
	    			if ($discount_first == $discount_last) {
	    				$discount = $discount_first;
	    			}
	    		}

	    		$key = 'description_tier_discount_brand';
	    		// $key_null = 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %minmax%';
	    		$key_null = 'Diskon %discount% setelah pembelian minimal %min_qty% %product%';

	    		$minmax = $min_qty != $max_qty ? "$min_qty - $max_qty" : $min_qty;
	    		// $desc = Setting::where('key', '=', $key)->first()['value']??$key_null;
	    		$desc = $key_null;

	    		$desc = MyHelper::simpleReplace($desc,['product'=>$product, 'minmax'=>$minmax, 'brand'=>$brand, 'min_qty' => $min_qty, 'discount' => $discount]);
	    	}
	    	elseif ($query['promo_type'] == 'Buy X Get Y') 
	    	{
	    		$min_qty = null;
	    		$max_qty = null;
	    		$discount_rule = [];
	    		$product_benefit = null;
	    		$promo_rules = $query[$source.'_buyxgety_rules'];
	    		foreach ($promo_rules as $key => $rule) {
					$min_req = $rule['min_qty_requirement'];
					$max_req = $rule['max_qty_requirement'];

					if($min_qty === null || $rule['min_qty_requirement'] < $min_qty){
						$min_qty = $min_req;
					}
					if($max_qty === null || $rule['max_qty_requirement'] > $max_qty){
						$max_qty = $max_req;
					}

					if ($rule['discount_type'] == 'percent') {
						if ($rule['discount_value'] == 100) {
							$discount_rule[] = 'gratis '.$rule['benefit_qty'].' item';
						}else{
		        			$discount_rule[] = ($rule['discount_value']??0).'%';
						}
		        	}else{
		        		$discount_rule[] = 'Rp '.number_format(($rule['discount_value']??0),0,',','.');
		        	}

		        	$product_benefit[$rule['id_brand']][$rule['benefit_id_product']][$rule['id_product_variant_group']] = [
		        		'id_brand' => $rule['id_brand'],
		        		'id_product' => $rule['benefit_id_product'],
		        		'id_product_variant_group' => $rule['id_product_variant_group']
		        	];
	    		}

	    		$discount = '';
	    		if(count($promo_rules) == 1){ // only 1 rule available
	    			$product_benefit = Product::where('id_product', $promo_rules[0]['benefit_id_product'])->select('product_name')->first();
	    			$variant = ProductVariantPivot::join('product_variants', 'product_variants.id_product_variant', 'product_variant_pivot.id_product_variant')
	    						->where('id_product_variant_group', $promo_rules[0]['id_product_variant_group'])->pluck('product_variant_name');

    				$product_benefit = $product_benefit['product_name']??'';
    				if ($variant->isNotEmpty()) {
    					$variant = implode(' ', $variant->toArray());
    					$product_benefit = $product_benefit.' '.$variant;
    				}

	    			if ($promo_rules[0]['discount_type'] == 'percent') {
	    				if ($promo_rules[0]['discount_value'] == 100) {
	    					$discount = 'Gratis '.$promo_rules[0]['benefit_qty'];
	    				}else{
	    					$discount = 'Diskon '.$promo_rules[0]['discount_value'].'%';
	    				}
	    			}else{
	    				$discount = 'Diskon '.MyHelper::requestNumber($promo_rules[0]['discount_value'],'_CURRENCY');
	    			}

		    		$req_product = $query[$source.'_buyxgety_product_requirement'];
		    		if (count($req_product) == 1 
		    			&& count($promo_rules) == 1 
		    			&& $req_product[0]['id_product'] == $promo_rules[0]['benefit_id_product']
		    			&& (($promo_rules[0]['discount_value'] != 100 && $promo_rules[0]['discount_type'] == 'percent') || $promo_rules[0]['discount_type'] != 'percent')
		    		) {
		    			$product_benefit = $product_benefit.' selanjutnya';
		    		}

		    		$desc = '%discount% %product_benefit% setelah pembelian minimal %min_qty% %product%';
	    		}else{
	    			// multi rule
	    			$product_benefit = 'product tertentu';
	    			$desc = 'Diskon untuk %product_benefit% setelah pembelian minimal %min_qty% %product%';
	    		}

	    		/*$discount = '';
	    		$discount_count = count($discount_rule);
	    		$i = 1;
	    		foreach ($discount_rule as $key => $value) {
	    			if ($i == 1) {
	    				$discount .= $value;
	    			}
	    			elseif ($i == $discount_count) {
	    				$discount .= ' atau '.$value;
	    			}
	    			else {
	    				$discount .= ', '.$value;
	    			}

	    			$i++;
	    		}*/

	    		$key = 'description_buyxgety_discount_brand';
	    		// $key_null = 'Anda berhak mendapatkan potongan setelah melakukan pembelian %product% sebanyak %minmax%';
	    		// $key_null = 'Diskon %discount% untuk %product_benefit% setelah pembelian minimal %min_qty% %product%';
	    		$minmax = $min_qty != $max_qty? "$min_qty - $max_qty" : $min_qty;
	    		// $desc = Setting::where('key', '=', $key)->first()['value']??$key_null;
	    		// $desc = $key_null;

	    		$desc = MyHelper::simpleReplace($desc,['product'=>$product, 'minmax'=>$minmax, 'brand'=>$brand, 'min_qty' => $min_qty, 'discount' => $discount, 'product_benefit' => $product_benefit]);
	    	}
	    	elseif ($query['promo_type'] == 'Discount bill') 
	    	{
	    		$discount = $query[$source.'_discount_bill_rules']['discount_type']??'Nominal';
	        	$max_percent_discount = $query[$source.'_discount_bill_rules']['max_percent_discount']??0;

	        	if ($discount == 'Percent') {
	        		$discount = ($query[$source.'_discount_bill_rules']['discount_value']??0).'%';
	        	}else{
	        		$discount = 'Rp '.number_format(($query[$source.'_discount_bill_rules']['discount_value']??0),0,',','.');
	        	}

				$text = 'Diskon %discount% untuk pembelian %product%';

	    		$desc = MyHelper::simpleReplace($text,['discount'=>$discount, 'product'=>$product]);
	    	}
	    	elseif ($query['promo_type'] == 'Discount delivery')
	        {
	        	$discount = $query[$source.'_discount_delivery_rules']['discount_type']??'Nominal';
	        	$max_percent_discount = $query[$source.'_discount_delivery_rules']['max_percent_discount']??0;

	        	if ($discount == 'Percent') {
	        		$discount = ($query[$source.'_discount_delivery_rules']['discount_value']??0).'%';
	        	}else{
	        		$discount = 'Rp '.number_format(($query[$source.'_discount_delivery_rules']['discount_value']??0),0,',','.');
	        	}

				$text = 'Diskon ongkos kirim %discount%';

	    		$desc = MyHelper::simpleReplace($text,['discount'=>$discount]);
	    	}
	    	else
	    	{
	    		$key = null;
	    		$desc = null;
	    	}
    	}

    	if ($desc) {
    		$text = $desc;
    		if (substr($text, -1) != '.') {
    			$separator = '. ';
    			$global_text = ucfirst($global_text);
    		}else{
    			$separator = ' ' ;
    		}

    		if ($global_text) {
	    		$text = $text.$separator.$global_text;
    		}

	    	$desc = MyHelper::simpleReplace($text,['payment_text'=>$payment_text, 'shipment_text'=>$shipment_text]);
    	}else{
    		$desc = 'no description';
    	}

    	return $desc;
    }
}