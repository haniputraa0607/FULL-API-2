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

   public function listPromoCampaignBefore(Request $request)
    {
        $result = array();
        $post = $request->json()->all();
        $promo_type = $request->promo_type;
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        try {
            $query = PromoCampaign::where('promo_campaigns.date_start','>', date('Y-m-d H:i:s')) 
                 ->join('promo_campaign_brands','promo_campaign_brands.id_promo_campaign','promo_campaigns.id_promo_campaign')
                 ->join('brands','brands.id_brand','promo_campaign_brands.id_brand')
                 ->join('brand_outlet','brand_outlet.id_brand','brands.id_brand')
                 ->join('outlets','outlets.id_outlet','brand_outlet.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner))
                 ->OrderBy('promo_campaigns.id_promo_campaign', 'DESC');
                    
            $count = (new PromoCampaign)->newQuery();

            if (isset($promo_type)) {

                $query = $query->where('promo_campaigns.promo_type', '=' ,$promo_type);

            }

            if ($request->json('rule')) {
                $filter = $this->listPromoCampaignFilterList($query, $request);
                $this->listPromoCampaignFilterList($count, $request);
                }
            
            if ($filter??false) {
                $result = array_merge($result, $filter);
            }
            $query = $query->get()->toArray();
            
            $query = $this->dealsPaginate($query);
            return response()->json(MyHelper::checkGet($query));
        } catch (\Exception $e) {
            
            return response()->json(['status' => 'error', 'messages' => [$e->getMessage()]]);
        }
    }
   public function listPromoCampaignActive(Request $request)
    {
        $result = array();
        $post = $request->json()->all();
        $promo_type = $request->promo_type;
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        try {
            $query = PromoCampaign::where('promo_campaigns.date_start','<', date('Y-m-d H:i:s')) 
                 ->where('promo_campaigns.date_end','>', date('Y-m-d H:i:s')) 
                 ->join('promo_campaign_brands','promo_campaign_brands.id_promo_campaign','promo_campaigns.id_promo_campaign')
                 ->join('brands','brands.id_brand','promo_campaign_brands.id_brand')
                 ->join('brand_outlet','brand_outlet.id_brand','brands.id_brand')
                 ->join('outlets','outlets.id_outlet','brand_outlet.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner))
                 ->OrderBy('promo_campaigns.id_promo_campaign', 'DESC');
                    
            $count = (new PromoCampaign)->newQuery();

            if (isset($promo_type)) {

                $query = $query->where('promo_campaigns.promo_type', '=' ,$promo_type);

            }

            if ($request->json('rule')) {
                $filter = $this->listPromoCampaignFilterList($query, $request);
                $this->listPromoCampaignFilterList($count, $request);
            }
            
            if ($filter??false) {
                $result = array_merge($result, $filter);
            }
            $query = $query->get()->toArray();
            $query = $this->dealsPaginate($query);
            return response()->json(MyHelper::checkGet($query));
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'messages' => [$e->getMessage()]]);
        }
    }
  
    protected function listPromoCampaignFilterList($query, $request)
    {
        $allowed = array(
            'operator' => ['=', 'like', '<', '>', '<=', '>='],
            'subject' => [
                'campaign_name', 
                'promo_title', 
                'code_type', 
                'prefix_code', 
                'number_last_code', 
                'total_code', 
                'date_start', 
                'date_end', 
                'is_all_outlet', 
                'promo_type', 
                'used_code', 
                'id_outlet', 
                'id_product', 
                'id_user',
                'used_by_user',
                'used_at_outlet',
                'promo_code'
            ],
            'mainSubject' => [
                'campaign_name', 
                'promo_title', 
                'code_type', 
                'prefix_code', 
                'number_last_code', 
                'total_code', 
                'date_start', 
                'date_end', 
                'is_all_outlet', 
                'promo_type', 
                'used_code'
            ]
        );
        $request->validate([
            'operator' => 'required|in:or,and',
            'rule.*.subject' => 'required|in:' . implode(',', $allowed['subject']),
            'rule.*.operator' => 'in:' . implode(',', $allowed['operator']),
            'rule.*.parameter' => 'required'
        ]);
        $return = [];
        $where = $request->json('operator') == 'or' ? 'orWhere' : 'where';
        if ($request->json('date_start')) {
            $query->where('promo_campaigns.date_start', '>=', $request->json('date_start'));
        }
        if ($request->json('date_end')) {
            $query->where('promo_campaigns.date_end', '<=', $request->json('date_end'));
        }
        $rule = $request->json('rule');
        foreach ($rule as $value) {
            if (in_array($value['subject'], $allowed['mainSubject'])) {
                if (!in_array($value['subject'], $allowed['subject'])) {
                    continue;
                }
                if (!(isset($value['operator']) && $value['operator'] && in_array($value['operator'], $allowed['operator']))) {
                    $value['operator'] = '=';
                }
                if ($value['operator'] == 'like') {
                    $query->$where($value['subject'], $value['operator'], '%' . $value['parameter'] . '%');
                } else {
                    $query->$where($value['subject'], $value['operator'], $value['parameter']);
                }
            } else {
                switch ($value['subject']) {
                    case 'id_outlet':
                    if ($value['parameter'] == '0') {
                        $query->$where('is_all_outlet', '1');
                    } else {
                        $query->leftJoin('promo_campaign_outlets', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_outlets.id_promo_campaign');
                        $query->$where(function ($query) use ($value) {
                            $query->where('promo_campaign_outlets.id_outlet', $value['parameter']);
                            $query->orWhere('is_all_outlet', '1');
                        });
                    }
                    break;

                    case 'id_user':
                    $query->leftJoin('promo_campaign_user_filters', 'promo_campaign_user_filters.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                    switch ($value['parameter']) {
                        case 'all user':
                        $query->$where('promo_campaign_user_filters.subject', 'all_user');
                        break;

                        case 'new user':
                        $query->$where(function ($query) {
                            $query->where('promo_campaign_user_filters.subject', 'count_transaction');
                            $query->where('promo_campaign_user_filters.parameter', '0');
                        });
                        break;

                        case 'existing user':
                        $query->$where(function ($query) {
                            $query->where('promo_campaign_user_filters.subject', 'count_transaction');
                            $query->where('promo_campaign_user_filters.parameter', '1');
                        });
                        break;

                        default:
                                # code...
                        break;
                    }
                    break;

                    case 'id_product':
                    $query->leftJoin('promo_campaign_buyxgety_product_requirements', 'promo_campaign_buyxgety_product_requirements.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                    $query->leftJoin('promo_campaign_product_discounts', 'promo_campaign_product_discounts.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                    $query->leftJoin('promo_campaign_tier_discount_products', 'promo_campaign_tier_discount_products.id_promo_campaign', '=', 'promo_campaigns.id_promo_campaign');
                    if ($value['parameter'] == '0') {
                        $query->$where(function ($query) {
                            $query->where('promo_type', 'Product discount');
                            $query->where('promo_campaign_product_discounts.id_product', null);
                        });
                    } else {
                        $query->$where(DB::raw('IF(promo_type=\'Product discount\',promo_campaign_product_discounts.id_product,IF(promo_type=\'Tier discount\',promo_campaign_tier_discount_products.id_product,promo_campaign_buyxgety_product_requirements.id_product))'), $value['parameter']);
                    }
                    break;

                    case 'used_by_user':
                    $wherein=$where.'In';
                    $query->$wherein('id_promo_campaign',function($query) use ($value,$where){
                        $query->select('id_promo_campaign')->from(with(new Reports)->getTable())->where('user_phone',$value['operator'],$value['operator'] == 'like'?'%'.$value['parameter'].'%':$value['parameter'])->groupBy('id_promo_campaign');
                    });
                    break;

                    case 'used_at_outlet':
                    $wherein=$where.'In';
                    $query->$wherein('id_promo_campaign',function($query) use ($value,$where){
                        $query->select('id_promo_campaign')->from(with(new Reports)->getTable())->where('id_outlet',$value['parameter'])->groupBy('id_promo_campaign');
                    });
                    break;

                    case 'promo_code':
                    $wherein=$where.'In';
                    $query->$wherein('id_promo_campaign',function($query) use ($value,$where){
                        $query->select('id_promo_campaign')->from(with(new PromoCode)->getTable())->where('promo_code',$value['operator'],$value['operator'] == 'like'?'%'.$value['parameter'].'%':$value['parameter'])->groupBy('id_promo_campaign');
                    });
                    break;

                    default:
                        # code...
                    break;
                }
            }
            $return[] = $value;
        }
        return [
            'rule' => $return, 
            'operator' => $request->json('operator')
        ];
    }
    function dealsPaginate($query)
	{
		
		$query = DataTables::of($query)
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
}