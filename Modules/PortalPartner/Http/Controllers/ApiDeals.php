<?php

namespace Modules\PortalPartner\Http\Controllers;

use App\Http\Models\Configs;
use App\Http\Models\DealTotal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use App\Http\Models\Outlet;
use App\Http\Models\Deal;
use App\Http\Models\DealsOutlet;
use App\Http\Models\DealsPaymentManual;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsVoucher;
use App\Http\Models\SpinTheWheel;
use App\Http\Models\Setting;
use Modules\Brand\Entities\Brand;
use App\Http\Models\DealsPromotionTemplate;
use Modules\ProductVariant\Entities\ProductGroup;
use App\Http\Models\Product;
use Modules\Promotion\Entities\DealsPromotionBrand;
use Modules\Promotion\Entities\DealsPromotionOutlet;
use Modules\Promotion\Entities\DealsPromotionOutletGroup;
use Modules\Promotion\Entities\DealsPromotionContent;
use Modules\Promotion\Entities\DealsPromotionContentDetail;

use Modules\Deals\Entities\DealsProductDiscount;
use Modules\Deals\Entities\DealsProductDiscountRule;
use Modules\Deals\Entities\DealsTierDiscountProduct;
use Modules\Deals\Entities\DealsTierDiscountRule;
use Modules\Deals\Entities\DealsBuyxgetyProductRequirement;
use Modules\Deals\Entities\DealsBuyxgetyRule;
use Modules\Deals\Entities\DealsUserLimit;
use Modules\Deals\Entities\DealsContent;
use Modules\Deals\Entities\DealsContentDetail;
use Modules\Deals\Entities\DealsBrand;
use Modules\Deals\Entities\DealsOutletGroup;

use DB;

use Modules\PortalPartner\Http\Requests\Promo\ListDeal;
use Modules\PortalPartner\Http\Requests\Promo\DetailDealsRequest;

use Illuminate\Support\Facades\Schema;

use Image;
use DataTables;
use App\Jobs\SendDealsJob;

class ApiDeals extends Controller
{

    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->user     = "Modules\Users\Http\Controllers\ApiUser";
        $this->hidden_deals     = "Modules\Deals\Http\Controllers\ApiHiddenDeals";
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->subscription = "Modules\Subscription\Http\Controllers\ApiSubscription";
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->promotion_deals      = "Modules\Promotion\Http\Controllers\ApiPromotionDeals";
        $this->deals_claim    = "Modules\Deals\Http\Controllers\ApiDealsClaim";
        $this->promo       	= "Modules\PromoCampaign\Http\Controllers\ApiPromo";
    }

    public $saveImage = "img/deals/";


  

    /* LIST */
    function listDealBefore(ListDeal $request) 
    {
    	$post = $request->json()->all();
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        $deals = Deal::join('deals_brands','deals_brands.id_deals','deals.id_deals')
                 ->join('brands','brands.id_brand','deals_brands.id_brand')
                 ->join('deals_outlets','deals_outlets.id_deals','deals.id_deals')
                 ->join('outlets','outlets.id_outlet','deals_outlets.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner))
                 ->Select('deals.*','brands.*','outlets.*');
        
        
        if ($request->json('rule')){
             $this->filterList($deals,$request->json('rule'),$request->json('operator')??'and');
        }
        $deals = $deals->distinct()->get()->toArray();
        $result = array();
        foreach ($deals as $value) {
            if(strtotime($value['deals_start'])>= strtotime(date('Y-m-d H:i:s'))) { 
                array_push($result,$value);
            }
        }    
        if (!empty($result)) {
            $city = "";
            if ($request->json('id_city')) {
                $city = $request->json('id_city');
            }

            $deals = $this->kotacuks($deals, $city,$request->json('admin'));
        }
        
        //jika mobile di pagination
        if (!$request->json('web')) {
            $result = $this->dealsPaginate($result);
            $resultMessage = 'Nantikan penawaran menarik dari kami';
            return response()->json(MyHelper::checkGet($result, $resultMessage));
        }
        else{
            $result = $this->dealsPaginate($result);
            return response()->json(MyHelper::checkGet($result));
        }
    }
    function listDealActive(ListDeal $request) 
    {
    	$post = $request->json()->all();
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        $deals = Deal::join('deals_brands','deals_brands.id_deals','deals.id_deals')
                 ->join('brands','brands.id_brand','deals_brands.id_brand')
                 ->join('deals_outlets','deals_outlets.id_deals','deals.id_deals')
                 ->join('outlets','outlets.id_outlet','deals_outlets.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner))
                ->Select('deals.*','brands.*','outlets.*');
        if ($request->json('rule')){
             $this->filterList($deals,$request->json('rule'),$request->json('operator')??'and');
        }

        $deals = $deals->distinct()->get()->toArray();
        $result = array();
        foreach ($deals as $value) {
            if(strtotime($value['deals_start'])<= strtotime(date('Y-m-d H:i:s'))&&strtotime($value['deals_end'])>= strtotime(date('Y-m-d H:i:s'))) { 
                array_push($result,$value);
            }
        }    
        if (!empty($result)) {
            $city = "";
            if ($request->json('id_city')) {
                $city = $request->json('id_city');
            }

            $deals = $this->kotacuks($deals, $city,$request->json('admin'));
        }
        
        //jika mobile di pagination
        if (!$request->json('web')) {
            $result = $this->dealsPaginate($result);
            $resultMessage = 'Nantikan penawaran menarik dari kami';
            return response()->json(MyHelper::checkGet($result, $resultMessage));
        }
        else{
            $result = $this->dealsPaginate($result);
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
        $subjects=['deals_title','deals_second_title','deals_type','promo_type','deals_total_voucher'];
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
    /* UNLIMITED */
    function unlimited($deals)
    {
        $unlimited = array_filter(array_column($deals, "available_voucher"), function ($deals) {
            if ($deals == "*") {
                return $deals;
            }
        });

        return $unlimited;
    }

    function limited($deals)
    {
        $limited = array_filter(array_column($deals, "available_voucher"), function ($deals) {
            if ($deals != "*") {
                return $deals;
            }
        });

        return $limited;
    }

    /* SORT DEALS */
    function highestAvailableVoucher($deals)
    {
        usort($deals, function ($a, $b) {
            return $a['available_voucher'] < $b['available_voucher'];
        });

        return $deals;
    }

    function lowestAvailableVoucher($deals)
    {
        usort($deals, function ($a, $b) {
            return $a['available_voucher'] > $b['available_voucher'];
        });

        return $deals;
    }

    /* INI LIST KOTA */
    function kotacuks($deals, $city = "",$admin=false)
    {
        $timeNow = date('Y-m-d H:i:s');

        foreach ($deals as $key => $value) {
            $markerCity = 0;

            $deals[$key]['outlet_by_city'] = [];

            // set time
            $deals[$key]['time_server'] = $timeNow;

            if (!empty($value['outlets'])) {
                // ambil kotanya dulu
                $kota = array_column($value['outlets'], 'city');
                $kota = array_values(array_map("unserialize", array_unique(array_map("serialize", $kota))));

                // jika ada pencarian kota
                if (!empty($city)) {
                    $cariKota = array_search($city, array_column($kota, 'id_city'));

                    if (is_integer($cariKota)) {
                        $markerCity = 1;
                    }
                }

                foreach ($kota as $k => $v) {
                    if ($v) {

                        $kota[$k]['outlet'] = [];

                        foreach ($value['outlets'] as $outlet) {
                            if ($v['id_city'] == $outlet['id_city']) {
                                unset($outlet['pivot']);
                                unset($outlet['city']);

                                array_push($kota[$k]['outlet'], $outlet);
                            }
                        }
                    } else {
                        unset($kota[$k]);
                    }
                }

                $deals[$key]['outlet_by_city'] = $kota;
            }

            // unset($deals[$key]['outlets']);
            // jika ada pencarian kota
            if (!empty($city)) {
                if ($markerCity == 0) {
                    unset($deals[$key]);
                    continue;
                }
            }

            $calc = $value['deals_total_voucher'] - $value['deals_total_claimed'];

            if ($value['deals_voucher_type'] == "Unlimited") {
                $calc = '*';
            }

            if(is_numeric($calc) && $value['deals_total_voucher'] !== 0){
                if($calc||$admin){
                    $deals[$key]['percent_voucher'] = $calc*100/$value['deals_total_voucher'];
                }else{
                    unset($deals[$key]);
                    continue;
                }
            }else{
                $deals[$key]['percent_voucher'] = 100;
            }

            $deals[$key]['show'] = 1;
            $deals[$key]['available_voucher'] = (string) $calc;
            $deals[$key]['available_voucher_text'] = "";
            if ($calc != "*") {
            	$deals[$key]['available_voucher_text'] = $calc. " kupon tersedia";
            }
            // deals masih ada?
            // print_r($deals[$key]['available_voucher']);
        }

        // print_r($deals); exit();
        $deals = array_values($deals);

        return $deals;
    }

    
	function dealsPaginate($query)
	{
		
		$query = DataTables::of($query)
                ->addColumn('action', function ($data) {
                    $slug = MyHelper::createSlug($data['id_deals'], $data['created_at']);
                    $btnDelete = '<a class="btn btn-sm btn-primary text-nowrap" href="'. env('APP_URL').'report/promo/deals/detail/'.$slug.'"><i class="fa fa-search" style="font-size : 14px; padding-right : 0px"></i></a>';  
                     return '<div class="btn-group btn-group" role="group">'. $btnDelete.'</div>';
                 })
                ->editColumn('deals_voucher_price', function($row) {
                    if($row['deals_voucher_price_type'] == 'free'){ 
                        return $row['deals_voucher_price_type'];
                    } elseif (!empty($row['deals_voucher_price_point'])){
                        return number_format($row['deals_voucher_price_point']).' Points';
                    }elseif (!empty($row['deals_voucher_price_cash'])){
                        return 'IDR'.number_format($row['deals_voucher_price_cash']);
                    }
                })
                ->editColumn('deals_voucher_price', function($row) {
                    if($row['deals_voucher_price_type'] == 'free'){ 
                        return $row['deals_voucher_price_type'];
                    } elseif (!empty($row['deals_voucher_price_point'])){
                        return number_format($row['deals_voucher_price_point']).' Points';
                    }elseif (!empty($row['deals_voucher_price_cash'])){
                        return 'IDR'.number_format($row['deals_voucher_price_cash']);
                    }
                })
                ->editColumn('deals_publish_start', function($row) {
                  $publish_start = date('d M Y', strtotime($row['deals_publish_start'])).' - '.date('d M Y', strtotime($row['deals_publish_end']));
                  return $publish_start;
                })
                ->editColumn('deals_start', function($row) {
                  $publish_start = date('d M Y', strtotime($row['deals_start'])).' - '.date('d M Y', strtotime($row['deals_end']));
                  return $publish_start;
                })
                ->editColumn('deals_start', function($row) {
                  $publish_start = date('d M Y', strtotime($row['deals_start'])).' - '.date('d M Y', strtotime($row['deals_end']));
                  return $publish_start;
                })
                ->make(true);
                
		return $query;
	}
    
    public function detail(DetailDealsRequest $request)
    {
        $post = $request->json()->all();
        $user = $request->user();
        $step = 'all';
        $deals_type = "Deals";
        $id = MyHelper::explodeSlug($post['id_deals'])[0]??'';
        if(isset($post['step'])){
            $step = $post['step'];
        }
        if(isset($post['deals_type'])){
            $deals_type = $post['deals_type'];
        }
        $deals = $this->getDealsData($id, $step, $deals_type);
        if (isset($deals)) {
            $deals = $deals->toArray();
        }else{
            $deals = false;
        }

        if ($deals) {
            $result = [
                'status'  => 'success',
                'result'  => $deals
            ];
        } else {
            $result = [
                'status'  => 'fail',
                'messages'  => ['Deals Not Found']
            ];
        }

        return response()->json($result);
    }

    function getDealsData($id_deals, $step, $deals_type='Deals')
    {
    	$post['id_deals'] = $id_deals;
    	$post['step'] = $step;
    	$post['deals_type'] = $deals_type;

    	if ($deals_type == 'Promotion' || $deals_type == 'deals_promotion') {
    		$deals = DealsPromotionTemplate::where('id_deals_promotion_template', '=', $post['id_deals']);
    		$table = 'deals_promotion';
    	}else{
    		if ($deals_type == 'promotion-deals') {
    			$post['deals_type'] = 'promotion';
    		}
    		$deals = Deal::where('id_deals', '=', $post['id_deals'])->where('deals_type','=',$post['deals_type']);
    		$table = 'deals';
    	}

        if ( ($post['step'] == 1 || $post['step'] == 'all') ){
			$deals = $deals->with(['outlets', 'outlet_groups']);
        }

        if ( ($post['step'] == 1 || $post['step'] == 'all') ){
			$deals = $deals->with([$table.'_brands']);
        }

        if ($post['step'] == 2 || $post['step'] == 'all') {
			$deals = $deals->with([
                $table.'_product_discount.product',
                $table.'_product_discount.brand',
                $table.'_product_discount.product_variant_pivot.product_variant',
                $table.'_product_discount_rules',
                $table.'_tier_discount_product.product',
                $table.'_tier_discount_product.brand',
                $table.'_tier_discount_product.product_variant_pivot.product_variant',
                $table.'_tier_discount_rules',
                $table.'_buyxgety_product_requirement.product',
                $table.'_buyxgety_product_requirement.brand',
                $table.'_buyxgety_product_requirement.product_variant_pivot.product_variant',
                $table.'_buyxgety_rules.product',
                $table.'_buyxgety_rules.brand',
                $table.'_buyxgety_rules.product_variant_pivot.product_variant',
                $table.'_buyxgety_rules.deals_buyxgety_product_modifiers.modifier',
                $table.'_discount_bill_rules',
                $table.'_discount_bill_products.product',
                $table.'_discount_bill_products.brand',
                $table.'_discount_bill_products.product_variant_pivot.product_variant',
                $table.'_discount_delivery_rules',
                $table.'_shipment_method',
                $table.'_payment_method',
                'brand',
                'brands',
                'created_by_user' => function($q) {
                	$q->select('id', 'name', 'level');
                }
            ]);
        }

        if ($post['step'] == 3 || $post['step'] == 'all') {
			$deals = $deals->with([$table.'_content.'.$table.'_content_details']);
        }

        if ($post['step'] == 'all') {
			// $deals = $deals->with(['created_by_user']);
        }

        $deals = $deals->first();

        if ($deals) {
        	if ($post['step'] == 'all' && $deals_type != 'Promotion' && $deals_type != 'promotion-deals') {
	        	$deals_array = $deals->toArray();
	        	if ($deals_type == 'Deals' || $deals_type == 'Hidden' || $deals_type == 'WelcomeVoucher' || $deals_type == 'Quest') {
	        		$type = 'deals';
	        	}else{
	        		$type = $deals_type;
	        	}
	        	$getProduct = app($this->promo_campaign)->getProduct($type, $deals_array);
	    		$desc = app($this->promo_campaign)->getPromoDescription($type, $deals_array, $getProduct['product']??'', true);
	    		$deals['description'] = $desc;
        	}
        }
        if ($deals_type != 'Promotion' && $post['step'] == 'all') {
        	$used_voucher = DealsVoucher::join('transaction_vouchers', 'deals_vouchers.id_deals_voucher', 'transaction_vouchers.id_deals_voucher')
        					->where('id_deals', $deals->id_deals)
        					->where('transaction_vouchers.status', 'success')
        					->count();
        	$deals->deals_total_used = $used_voucher;
        }

        return $deals;
    }


}
