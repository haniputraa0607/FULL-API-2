<?php

namespace Modules\Transaction\Http\Controllers;

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
use Modules\PromoCampaign\Entities\UserPromo;;
use Modules\PromoCampaign\Entities\TransactionPromo;

use Modules\Deals\Entities\DealsProductDiscount;
use Modules\Deals\Entities\DealsProductDiscountRule;
use Modules\Deals\Entities\DealsTierDiscountProduct;
use Modules\Deals\Entities\DealsTierDiscountRule;
use Modules\Deals\Entities\DealsBuyxgetyProductRequirement;
use Modules\Deals\Entities\DealsBuyxgetyRule;

use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;

use Modules\ProductVariant\Entities\ProductGroup;

use App\Http\Models\User;
use App\Http\Models\Configs;
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
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionVoucher;
use App\Http\Models\OauthAccessToken;

use Modules\PromoCampaign\Http\Requests\Step1PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\Step2PromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\DeletePromoCampaignRequest;
use Modules\PromoCampaign\Http\Requests\ValidateCode;
use Modules\PromoCampaign\Http\Requests\UpdateCashBackRule;
use Modules\PromoCampaign\Http\Requests\CheckUsed;

use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Lib\MyHelper;
use App\Jobs\GeneratePromoCode;
use App\Lib\TemporaryDataManager;
use Lcobucci\JWT\Parser;
use DB;
use Hash;
use Modules\SettingFraud\Entities\DailyCheckPromoCode;
use Modules\SettingFraud\Entities\LogCheckPromoCode;

use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Outlet\Entities\DeliveryOutlet;

use App\Lib\WeHelpYou;

class ApiPromoTransaction extends Controller
{

	function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->online_trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->voucher = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->promo_campaign = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
    }

    public function availableVoucher()
    {
    	$user = request()->user();
    	if (!$user) {
    		return [];
    	}

    	$voucher = DealsUser::where('id_user', $user->id)
            ->whereIn('paid_status', ['Free', 'Completed'])
            ->whereNull('used_at')
            ->with(['dealVoucher', 'dealVoucher.deal', 'dealVoucher.deal.outlets.city', 'dealVoucher.deal.outlets.city'])
            ->where('deals_users.voucher_expired_at', '>', date('Y-m-d H:i:s'))
            ->orderBy('deals_users.is_used', 'desc')
            ->orderBy('deals_users.voucher_expired_at', 'asc')
            ->limit(5)
            ->get()
            ->toArray();

        $result = array_map(function($var) {
            return [
                'id_deals' => $var['deal_voucher']['id_deals'],
                'voucher_expired_at' => $var['voucher_expired_at'],
                'id_deals_voucher' => $var['id_deals_voucher'],
                'id_deals_user' => $var['id_deals_user'],
                'deals_title' => $var['deal_voucher']['deal']['deals_title'],
                'deals_second_title' => $var['deal_voucher']['deal']['deals_second_title'],
                'url_deals_image' => $var['deal_voucher']['deal']['url_deals_image'],
                'is_used' => $var['is_used'],
                'date_expired_indo' => MyHelper::adjustTimezone($var['voucher_expired_at'], $user->user_time_zone_utc ?? 7, 'd F Y', true),
                'time_expired_indo' => 'pukul '.date('H:i', strtotime($var['voucher_expired_at'])),
                'text' => null,
				'is_error' => false
            ];
        }, $voucher);
        
        return $result;
    }

    public function getScope()
    {
    	$bearerToken = request()->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);

        return $scopeUser;
    }

    public function failResponse($msg = null)
    {
    	$res['status'] = 'fail';
    	if (!empty($msg)) {
    		if (!is_array($msg)) {
    			$msg = [$msg];
    		}
    		$res['messages'] = $msg;
    	}
    	return $res;
    }

    public function serviceTrxToPromo($svcTrx)
    {
    	$svcPromo = [
    		'outlet-service' => 'Outlet Service',
			'home-service' => 'Home Service',
			'shop' => 'Online Shop',
			'academy' => 'Academy'
    	];

    	return $svcPromo[$svcTrx] ?? $svcTrx;
    }

    public function promoName($promoSource)
    {
    	$promoName = [
    		'deals' => 'Voucher',
			'promo_campaign' => 'Kode promo',
			'subscription' => 'Subscription'
    	];

    	return $promoName[$promoSource] ?? $promoSource;
    }

    public function applyPromoCheckout($data)
    {	
    	$user = request()->user();
    	$scopeUser = $this->getScope();
    	$resPromoCode = null;
    	$resDeals = null;
    	$continueCheckOut = $data['continue_checkout'];

    	$userPromo = null;
    	if ($scopeUser != 'web-apps') {
    		$userPromo = UserPromo::where('id_user', $user->id)->get()->keyBy('promo_type');
    	}

    	$data['discount'] = 0;
		$data['discount_delivery'] = 0;

    	if (isset($userPromo['deals'])) {
    		$this->createSharedPromoTrx($data);
    		$applyDeals = $this->applyDeals($userPromo['deals']->id_reference, $data);
    		$promoDeals = $applyDeals['result'] ?? null;

			$resDeals = [
				'id_deals_user' => $userPromo['deals']->id_reference,
				'title' => $applyDeals['result']['title'] ?? null,
				'discount' => $applyDeals['result']['discount'] ?? 0,
				'discount_delivery' => $applyDeals['result']['discount_delivery'] ?? 0,
				'text' => $applyDeals['result']['text'] ?? $applyDeals['messages'],
				'is_error' => ($applyDeals['status'] == 'fail') ? true : false
			];

			if ($applyDeals['status'] == 'fail') {
				$continueCheckOut = false;
			}

			$data = $this->reformatCheckout($data, $promoDeals ?? null);
    	}

    	if (isset($userPromo['promo_campaign'])) {
    		$this->createSharedPromoTrx($data);
			$dataDiscount['promo_source'] = 'promo_code';
    		$resPromo['promo_campaign'] = PromoCampaign::find($userPromo['promo_campaign']['id_reference']);
    	}
    	
		$data['promo_deals'] = $resDeals;
		$data['promo_code'] = $resPromoCode;
		$availableVoucher = $this->availableVoucher();

		if ($resDeals) {
			foreach ($availableVoucher as &$voucher) {
				if ($resDeals['id_deals_user'] == $voucher['id_deals_user']) {
					$voucher['text'] = $resDeals['text'];
					$voucher['is_error'] = $resDeals['is_error'];
				} else {
					$voucher['text'] = null;
					$voucher['is_error'] = false;
				}
			}
		}
		$data['available_voucher'] = $availableVoucher;
		$data['continue_checkout'] = $continueCheckOut;
		return $data;
    }

    public function reformatCheckout($dataTrx, $dataDiscount)
    {
    	if (empty($dataDiscount['discount']) && empty($dataDiscount['discount_delivery'])) {
    		return $dataTrx;
    	}
    	$user = request()->user();
    	$promoCashback = ($dataDiscount['promo_source'] == 'deals') ? 'voucher_online' : 'promo_code';
    	$discount = (int) abs($dataDiscount['discount'] ?? $dataDiscount['discount_delivery']);
    	$sharedPromo = $this->getSharedPromoTrx();
		$outlet = OUtlet::find($sharedPromo['id_outlet']);
		$dataTrx['subtotal'] = $sharedPromo['subtotal'];
		$dataTrx['discount'] = ($dataTrx['discount'] ?? 0) + ($dataDiscount['discount'] ?? 0);
		$dataTrx['discount_delivery'] = ($dataTrx['discount_delivery'] ?? 0) + ($dataDiscount['discount_delivery'] ?? 0);
		$dataTrx['tax'] = ($outlet['is_tax'] / 100) * ($sharedPromo['subtotal'] - $dataTrx['discount']);
		$dataTrx['grandtotal'] = (int) $sharedPromo['subtotal'] + (int) $sharedPromo['service'] + (int) $dataTrx['tax'] + (int) ($dataTrx['shipping'] ?? 0) - $discount;
		$dataTrx['total_payment'] = $dataTrx['grandtotal'] - ($dataTrx['used_point'] ?? 0);

		$promoGetPoint = app($this->online_trx)->checkPromoGetPoint($promoCashback);
        if (!$promoGetPoint) {
			$dataTrx['cashback'] = 0;
        }

    	return $dataTrx;

    }

    public function applyDeals($id_deals_user, $data = [])
    {
    	$validateDeals = $this->validateDeals($id_deals_user);
    	if ($validateDeals['status'] == 'fail') {
    		return $validateDeals;
    	}

    	$deals = $validateDeals['result']->dealVoucher->deal;

    	$validateGlobalRules = $this->validateGlobalRules('deals', $deals, $data);
    	if ($validateGlobalRules['status'] == 'fail') {
    		return $validateGlobalRules;
    	}

    	$getDiscount = $this->getDiscount('deals', $deals, $data);
    	if ($getDiscount['status'] == 'fail') {
    		return $getDiscount;
    	}

    	$validateGlobalRulesAfter = $this->validateGlobalRulesAfter('deals', $deals, $data);
    	if ($validateGlobalRulesAfter['status'] == 'fail') {
    		return $validateGlobalRulesAfter;
    	}

    	$getProduct = app($this->promo_campaign)->getProduct('deals',$deals);
    	$desc = app($this->promo_campaign)->getPromoDescription('deals', $deals, $getProduct['product']??'');

    	$res = [
    		'id_deals' => $deals->id_deals,
    		'id_deals_user' => $id_deals_user,
    		'title' => $deals->deals_title,
    		'discount' => $getDiscount['result']['discount'] ?? 0,
    		'discount_delivery' => $getDiscount['result']['discount_delivery'] ?? 0,
    		'promo_type' => $getDiscount['result']['promo_type'],
    		'text' => [$desc],
    		'promo_source' => 'deals'
    	];

    	return MyHelper::checkGet($res);
    }

    public function validateDeals($id_deals_user)
    {
    	$dealsUser = DealsUser::find($id_deals_user);
    	if (!$dealsUser) {
    		return $this->failResponse('Voucher tidak ditemukan');
    	}

    	if ($dealsUser['used_at']) {
    		return $this->failResponse('Voucher sudah pernah digunakan');
    	}

    	if ($dealsUser['voucher_expired_at'] < date('Y-m-d H:i:s')) {
    		return $this->failResponse('Voucher sudah melewati batas waktu penggunaan');
    	}

    	if (!empty($dealsUser['voucher_active_at']) && $dealsUser['voucher_active_at'] > date('Y-m-d H:i:s')) {
    		$dateStart = MyHelper::adjustTimezone($dealsUser['voucher_active_at'], null, 'l, d F Y H:i', true);
    		return $this->failResponse('Voucher mulai dapat digunakan pada ' . $dateStart);
    	}

    	return MyHelper::checkGet($dealsUser);
    }

    public function validateGlobalRules($promoSource, $promoQuery, $data)
    {
    	$promo = $promoQuery;
    	$sharedPromoTrx = TemporaryDataManager::create('promo_trx');
    	$promoName = $this->promoName($promoSource);
		$pct = new PromoCampaignTools;

    	$trxFrom = $this->serviceTrxToPromo(request()->transaction_from);
    	$promoServices = $promo->{$promoSource . '_services'}->pluck('service')->toArray();
    	if (!in_array($trxFrom, $promoServices)) {
    		$promoServices = implode(', ', $promoServices);
    		return $this->failResponse($promoName . ' hanya dapat digunakan untuk transaksi ' . $promoServices);
    	}
    	
    	switch ($trxFrom) {
    		case 'Home Service':
    			$id_outlet = Setting::where('key', 'default_outlet_home_service')->first()['value'] ?? null;
    			break;

			case 'Online Shop':
				$id_outlet = Setting::where('key', 'default_outlet')->first()['value'] ?? null;
    			break;
    		
    		default:
    			$id_outlet = request()->id_outlet;
    			break;
    	}

    	$sharedPromoTrx['id_outlet'] = $id_outlet;
    	$promoBrand = $promo->{$promoSource . '_brands'}->pluck('id_brand')->toArray();
    	$promoOutlet = $promo->{$promoSource . '_outlets'};
		$outlet = $pct->checkOutletBrandRule($id_outlet, $promo->is_all_outlet ?? 0, $promoOutlet, $promoBrand, $promo->brand_rule, $promo->outlet_groups);
		if (!$outlet) {
    		return $this->failResponse($promoName . ' tidak dapat digunakan di outlet ini');
		}

		if (request()->shipment_method) {
			$promoShipment = $promo->{$promoSource . '_shipment_method'}->pluck('shipment_method');
			$checkShipment = $pct->checkShipmentRule($promo->is_all_shipment ?? 0, request()->shipment_method, $promoShipment);
			if (!$checkShipment) {
    			return $this->failResponse($promoName . ' tidak dapat digunakan untuk pengiriman ini');
			}
		}

		if (request()->payment_method) {
			$promoPayment = $promo->{$promoSource . '_payment_method'}->pluck('payment_method');
			$checkPayment = $pct->checkPaymentRule($promo->is_all_payment ?? 0, request()->payment_method, $promoPayment);
			if (!$checkPayment) {
    			return $this->failResponse($promoName . ' tidak dapat digunakan untuk metode pembayaran ini');
			}
		}

		return ['status' => 'success'];
    }

    public function validateGlobalRulesAfter($promoSource, $promoQuery, $data)
    {
    	$promo = $promoQuery;
    	$sharedPromoTrx = TemporaryDataManager::create('promo_trx');
		if (isset($sharedPromoTrx['subtotal'])) {
			if ($sharedPromoTrx['subtotal'] < $promo->min_basket_size) {
				$min_basket_size = MyHelper::requestNumber($promo->min_basket_size,'_CURRENCY');
    			return $this->failResponse('Pembelian minimum ' . $min_basket_size);
			}
		}

		return ['status' => 'success'];
    }

    public function getDiscount($promoSource, $promoQuery, $data)
    {
    	$promo = $promoQuery;
    	$promoName = $this->promoName($promoSource);
    	switch ($promo->promo_type) {
    		case 'Product discount':
    			return $this->productDiscount($promoSource, $promoQuery, $data);
    			break;
    		
    		case 'Tier discount':
    			return $this->tierDiscount($promoSource, $promoQuery, $data);
    			break;
    		
    		case 'Buy X Get Y':
    			return $this->bxgyDiscount($promoSource, $promoQuery, $data);
    			break;
    		
    		case 'Discount bill':
    			return $this->billDiscount($promoSource, $promoQuery, $data);
    			break;
    		
    		case 'Discount delivery':
    			return $this->deliveryDiscount($promoSource, $promoQuery, $data);
    			break;
    		
    		default:
    			return $this->failResponse($promoName . ' tidak ditemukan');
    			break;
    	}
    }

    public function productDiscount($promoSource, $promoQuery, $data)
    {
    	$promo 			= $promoQuery;
    	$pct 			= new PromoCampaignTools;
    	$promo_rules 	= $promo->{$promoSource . '_product_discount_rules'};
		$promo_product 	= $promo->{$promoSource . '_product_discount'}->toArray();
		$promo_brand 	= $promo->{$promoSource . '_brands'}->pluck('id_brand')->toArray();
		$product_name 	= $pct->getProductName($promo_product, $promo->product_rule);
		$shared_promo 	= TemporaryDataManager::create('promo_trx');
		$promo_item 	= $shared_promo['items'];
		$discount 		= 0;

		if (!$promo_rules->is_all_product) {
			if ($promo[$promoSource.'_product_discount']->isEmpty()) {
				return $this->failResponse('Produk tidak ditemukan');
			}

			$check_product = $pct->checkProductRule($promo, $promo_brand, $promo_product, $promo_item);

			if (!$check_product) {
				$message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.'; 
				$message = MyHelper::simpleReplace($message,['product'=>$product_name]);
				return $this->failResponse($message);
			}

		} else {
			$promo_product = "*";
		}

		$get_promo_product = $pct->getPromoProduct($promo_item, $promo_brand, $promo_product);
		$product = $get_promo_product['product'];

		if (!$product) {
			$message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.'; 
			$message = MyHelper::simpleReplace($message,['product'=>$product_name]);
			return $this->failResponse($message);
		}

		// sort product by price desc
		uasort($product, function($a, $b){
			return $b['product_price'] - $a['product_price'];
		});

		$merge_product = [];
		foreach ($product as $key => $value) {
			if (isset($merge_product[$value['id_product']])) {
				$merge_product[$value['id_product']] += $value['qty'];
			}
			else {
				$merge_product[$value['id_product']] = $value['qty'];
			}
		}

		if ($promo->product_rule == 'and') {
			$max_promo_qty = 0;
			foreach ($merge_product as $value) {
				if ($max_promo_qty == 0 || $max_promo_qty > $value) {
					$max_promo_qty = $value;
				}
			}

			$promo_qty_each = $max_promo_qty;

			if ($max_promo_qty == 0 || (isset($promo_rules->max_product) && $promo_rules->max_product < $max_promo_qty)) {
				$promo_qty_each = $promo_rules->max_product;
			}
		} else {
			$promo_qty_each = $promo_rules->max_product;
		}

		// get max qty of product that can get promo
		foreach ($product as $key => $value) {

			if (!empty($promo_qty_each)) {
				if (!isset($qty_each[$value['id_brand']][$value['id_product']])) {
					$qty_each[$value['id_brand']][$value['id_product']] = $promo_qty_each;
				}

				if ($qty_each[$value['id_brand']][$value['id_product']] < 0) {
					$qty_each[$value['id_brand']][$value['id_product']] = 0;
				}

				if ($qty_each[$value['id_brand']][$value['id_product']] > $value['qty']) {
					$promo_qty = $value['qty'];
				}else{
					$promo_qty = $qty_each[$value['id_brand']][$value['id_product']];
				}

				$qty_each[$value['id_brand']][$value['id_product']] -= $value['qty'];
				
			}else{
				$promo_qty = $value['qty'];
			}

			$product[$key]['promo_qty'] = $promo_qty;
		}

		foreach ($promo_item as $key => &$item) {
			if (!isset($product[$key])) {
				continue;
			}

			$item['promo_qty'] = $product[$key]['promo_qty'];
			$discount += $this->discountPerItem($item, $promo_rules);
		}

		if ($discount <= 0) {
			$message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.'; 
			$message = MyHelper::simpleReplace($message,['product'=>'produk bertanda khusus']);

			return $this->failResponse($message);
		}

		$shared_promo['items'] = $promo_item;

		return MyHelper::checkGet([
			'discount'	=> $discount,
			'promo_type'=> $promo->promo_type
		]);
    }

    public function tierDiscount($promoSource, $promoQuery, $data)
    {
    	$promo 			= $promoQuery;
    	$pct 			= new PromoCampaignTools;
		$promo_rules 	= $promo->{$promoSource . '_tier_discount_rules'};
		$promo_product 	= $promo->{$promoSource . '_tier_discount_product'}->toArray();
		$promo_brand 	= $promo->{$promoSource . '_brands'}->pluck('id_brand')->toArray();
		$product_name 	= $pct->getProductName($promo_product, $promo->product_rule);
		$shared_promo 	= TemporaryDataManager::create('promo_trx');
		$promo_item 	= $shared_promo['items'];
		$discount 		= 0;

		// get min max required for error message
		$min_qty = null;
		$max_qty = null;
		foreach ($promo_rules as $rule) {
			if ($min_qty === null || $rule->min_qty < $min_qty) {
				$min_qty = $rule->min_qty;
			}
			if ($max_qty === null || $rule->max_qty > $max_qty) {
				$max_qty = $rule->max_qty;
			}
		}

		$minmax = ($min_qty != $max_qty ? "$min_qty sampai $max_qty" : $min_qty)." item";
		
		if (!$promo_rules[0]->is_all_product) {
			if ($promo[$source.'_tier_discount_product']->isEmpty()) {
				return $this->failResponse('Produk tidak ditemukan');
			}

			$check_product = $this->checkProductRule($promo, $promo_brand, $promo_product, $trxs);

			if (!$check_product) {
				$message = $this->getMessage('error_tier_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.'; 
				$message = MyHelper::simpleReplace($message,['product'=>$product_name, 'minmax'=>$minmax]);
				return $this->failResponse($message);
			}
		} else {
			$promo_product = "*";
		}

		$get_promo_product = $pct->getPromoProduct($promo_item, $promo_brand, $promo_product);
		$product = $get_promo_product['product'];
		$total_product = $get_promo_product['total_product'];

		if(!$product){
			$minmax = ($min_qty != $max_qty ? "$min_qty sampai $max_qty" : $min_qty) . " item";
			$message = $pct->getMessage('error_tier_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.'; 
			$message = MyHelper::simpleReplace($message, ['product' => $product_name, 'minmax' => $minmax]);

			return $this->failResponse($message);
		}


		// sum total quantity of same product
		$item_get_promo = []; // include brand
		$item_promo = []; // only product/item
		foreach ($product as $key => $value) {
			if (isset($item_promo[$value['id_product']])) {
				$item_promo[$value['id_product']] += $value['qty'];
			}
			else{
				$item_promo[$value['id_product']] = $value['qty'];
			}

			if (isset($item_get_promo[$value['id_brand'] . '-' . $value['id_product']])) {
				$item_get_promo[$value['id_brand'] . '-' . $value['id_product']] += $value['qty'];
			}
			else{
				$item_get_promo[$value['id_brand'] . '-' . $value['id_product']] = $value['qty'];
			}
		}

		//find promo rules
		$promo_rule = null;
		if ($promo->product_rule == "and" && $promo_product != "*") {
			$req_valid 	= true;
			$rule_key	= [];
			$promo_qty_each = 0;
			foreach ($product as $key => &$val) {
				$min_qty 	= null;
				$max_qty 	= null;
				$temp_rule_key[$key] = [];

				foreach ($promo_rules as $key2 => $rule) {
					if ($min_qty === null || $rule->min_qty < $min_qty) {
						$min_qty = $rule->min_qty;
					}

					if ($max_qty === null || $rule->max_qty > $max_qty) {
						$max_qty = $rule->max_qty;
					}
					
					if ($rule->min_qty > $item_get_promo[$val['id_brand'].'-'.$val['id_product']]) {
						if (empty($temp_rule_key[$key])) {
							$req_valid = false;
							break;
						} else {
							continue;
						}
					}
					$temp_rule_key[$key][] 	= $key2;
				}

				if ($item_get_promo[$val['id_brand'] . '-' . $val['id_product']] < $promo_qty_each || $promo_qty_each == 0) {
					$promo_qty_each = $item_get_promo[$val['id_brand'] . '-' . $val['id_product']];
				}

				if (!empty($rule_key)) {
					$rule_key = array_intersect($rule_key, $temp_rule_key[$key]);
				} else {
					$rule_key = $temp_rule_key[$key];
				}

				if (!$req_valid) {
					break;
				}
			}

			if ($req_valid && !empty($rule_key)) {
				$rule_key 	= end($rule_key);
				$promo_rule = $promo_rules[$rule_key];
				$promo_qty_each = $promo_qty_each > $promo_rule->max_qty ? $promo_rule->max_qty : $promo_qty_each;
			}
		} else {
			$min_qty 	= null;
			$max_qty 	= null;

			foreach ($promo_rules as $rule) {
				if ($min_qty === null || $rule->min_qty < $min_qty) {
					$min_qty = $rule->min_qty;
				}
				if ($max_qty === null || $rule->max_qty > $max_qty) {
					$max_qty = $rule->max_qty;
				}
				
				if ($rule->min_qty > $total_product) { // total keseluruhan product
					continue;
				}
				$promo_rule = $rule;
			}
		}

		if (!$promo_rule) {
			$minmax = ($min_qty != $max_qty ? "$min_qty sampai $max_qty" : $min_qty) . " item";
			$message = $this->getMessage('error_tier_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b> sebanyak %minmax%.'; 
			$message = MyHelper::simpleReplace($message, ['product' => $product_name, 'minmax' => $minmax]);

			return $this->failResponse($message);
		}

		// sort product price desc
		uasort($product, function($a, $b){
			return $b['product_price'] - $a['product_price'];
		});

		// get max qty of product that can get promo
		$total_promo_qty = $promo_rule->max_qty < $total_product ? $promo_rule->max_qty : $total_product;
		foreach ($product as $key => $value) {

			if (!empty($promo_qty_each)) {

				if ($value['product_type'] == 'variant') {

					if (!isset($qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']])) {
						$qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] = $promo_qty_each;
					}

					if ($qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] < 0) {
						$qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] = 0;
					}

					if ($qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] > $value['qty']) {
						$promo_qty = $value['qty'];
					}else{
						$promo_qty = $qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']];
					}

					$qty_each[$value['id_brand']][$value['id_product']][$value['id_product_variant_group']] -= $value['qty'];

				} else {

					if (!isset($qty_each[$value['id_brand']][$value['id_product']])) {
						$qty_each[$value['id_brand']][$value['id_product']] = $promo_qty_each;
					}

					if ($qty_each[$value['id_brand']][$value['id_product']] < 0) {
						$qty_each[$value['id_brand']][$value['id_product']] = 0;
					}

					if ($qty_each[$value['id_brand']][$value['id_product']] > $value['qty']) {
						$promo_qty = $value['qty'];
					}else{
						$promo_qty = $qty_each[$value['id_brand']][$value['id_product']];
					}

					$qty_each[$value['id_brand']][$value['id_product']] -= $value['qty'];
				}
				
			} else {
				if ($total_promo_qty < 0) {
					$total_promo_qty = 0;
				}

				if ($total_promo_qty > $value['qty']) {
					$promo_qty = $value['qty'];
				} else {
					$promo_qty = $total_promo_qty;
				}

				$total_promo_qty -= $promo_qty;
			}

			$product[$key]['promo_qty'] = $promo_qty;
		}
		// count discount
		$product_id = array_column($product, 'id_product');
		foreach ($promo_item as $key => &$item) {

			if (!isset($product[$key])) {
				continue;
			}

			if (!in_array($item['id_brand'], $promo_brand)) {
				continue;
			}

			if (in_array($item['id_product'], $product_id)) {
				// add discount
				$item['promo_qty'] = $product[$key]['promo_qty'];
				$discount += $this->discountPerItem($item, $promo_rule);
			}
		}

		$shared_promo['items'] = $promo_item;
		return MyHelper::checkGet([
			'discount'	=> $discount,
			'promo_type'=> $promo->promo_type
		]);
    }

    public function bxgyDiscount($promoSource, $promoQuery, $data)
    {
    	return $this->failResponse('Promo belum tersedia');
    }

    public function billDiscount($promoSource, $promoQuery, $data)
    {
    	// load required relationship
    	$promo 			= $promoQuery;
    	$pct 			= new PromoCampaignTools;
		$promo_rules 	= $promo->{$promoSource . '_discount_bill_rules'};
		$promo_product 	= $promo->{$promoSource . '_discount_bill_products'}->toArray();
		$promo_brand 	= $promo->{$promoSource . '_brands'}->pluck('id_brand')->toArray();
		$product_name 	= $pct->getProductName($promo_product, $promo->product_rule);
		$shared_promo 	= TemporaryDataManager::create('promo_trx');
		$promo_item 	= $shared_promo['items'];
		$discount 		= 0;

		if (!$promo_rules->is_all_product) {
			if ($promo[$promoSource.'_discount_bill_products']->isEmpty()) {
				return $this->failResponse('Produk tidak ditemukan');
			}

			$check_product = $pct->checkProductRule($promo, $promo_brand, $promo_product, $promo_item);

			if (!$check_product && empty($request['bundling_promo'])) {
				$message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.'; 
				$message = MyHelper::simpleReplace($message,['product'=>$product_name]);
				return $this->failResponse($message);
			}
		} else {
			$promo_product = "*";
		}

		$get_promo_product = $pct->getPromoProduct($promo_item, $promo_brand, $promo_product);
		$product = $get_promo_product['product'];

		if (!$product && empty($request['bundling_promo'])) {
			$message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.'; 
			$message = MyHelper::simpleReplace($message,['product'=>$product_name]);
			return $this->failResponse($message);
		}

		$total_price = $shared_promo['subtotal'];

		if ($promo_rules->discount_type == 'Percent') {
			$discount += ($total_price * $promo_rules->discount_value) / 100;
			if (!empty($promo_rules->max_percent_discount) && $discount > $promo_rules->max_percent_discount) {
				$discount = $promo_rules->max_percent_discount;
			}
		} else {
			if ($promo_rules->discount_value < $total_price) {
				$discount += $promo_rules->discount_value;
			} else {
				$discount += $total_price;
			}
		}

		if ($discount <= 0) {
			$message = $pct->getMessage('error_product_discount')['value_text'] = 'Promo hanya berlaku jika membeli <b>%product%</b>.'; 
			$message = MyHelper::simpleReplace($message,['product'=>'produk bertanda khusus']);
			return $this->failResponse($message);;
		}

		return MyHelper::checkGet([
			'discount'	=> $discount,
			'promo_type'=> $promo->promo_type
		]);
    }

    public function deliveryDiscount($promoSource, $promoQuery, $data)
    {
    	return $this->failResponse('Promo belum tersedia');
    }

    public function discountPerItem(&$item, $promo_rules){
		$discount 		= 0;
		$prev_discount 	= $item['discount'] ?? 0;
		$discount_qty 	= $item['promo_qty'];
		$product_price 	= $item['product_price'];

		$item['discount']		= 0;
		$item['new_price']		= $product_price;
		$item['base_discount']	= 0;
		$item['is_promo']		= 0;
		$item['qty_discount']	= 0;

		if (empty($discount_qty)) {
			return 0;
		}

		if (strtolower($promo_rules->discount_type) == 'nominal') {
			$discount = $promo_rules->discount_value * $discount_qty;
			$product_price_total = $product_price * $discount_qty;
			if ($discount > $product_price_total) {
				$discount = $product_price_total;
			}

			$item['discount']		= $prev_discount + $discount;
			$item['new_price']		= ($product_price * $item['qty']) - $item['discount'];
			$item['base_discount']	= $product_price < $promo_rules->discount_value ? $product_price : $promo_rules->discount_value;
		} else {
			// percent
			$discount_per_item = ($promo_rules->discount_value / 100) * $product_price;
			if (!empty($promo_rules->max_percent_discount) && $discount_per_item > $promo_rules->max_percent_discount) {
				$discount_per_item = $promo_rules->max_percent_discount;
			}
			$discount = (int) ($discount_per_item * $discount_qty);

			$item['discount']		= ($prev_discount + $discount);
			$item['new_price']		= ($product_price * $item['qty']) - $item['discount'];
			$item['base_discount']	= $discount_per_item;
		}

		// if new price is negative
		if ($item['new_price'] < 0) {
			$item['new_price']		= 0;
			$item['discount']		= $product_price * $discount_qty;
			$item['base_discount']	= $product_price;
			$discount 				= $item['discount'] - $prev_discount;
		}

		$item['is_promo']		= 1;
		$item['qty_discount']	= $discount_qty;
		unset($item['promo_qty']);

		return $discount;
	}

    public function createSharedPromoTrx($dataTrx)
    {
    	// get data to calculate promo
    	$sharedPromoTrx = TemporaryDataManager::create('promo_trx');
    	$items = [];
    	// product
    	$items = array_merge($items, ($dataTrx['item'] ?? [])); 
    	// product service
    	$items = array_merge($items, ($dataTrx['item_service'] ?? []));
    	// product academy
    	$items = array_merge($items, ($dataTrx['item_academy'] ?? []));
    	// transaction products
    	$items = array_merge($items, ($dataTrx['transaction_products'] ?? []));

    	$promoItems = [];
    	if (request()->transaction_from == 'academy' && isset($items['id_product'])) {
    		$promoItems[] = [
    			'id_product' => $items['id_product'],
    			'id_brand' => $items['id_brand'],
    			'product_price' => $items['product_price'],
    			'product_type' => 'Academy',
    			'qty' => $items['qty']
    		];
    	} else {
	    	foreach ($items as $val) {
	    		$productType = 'Product';
	    		if (isset($val['id_user_hair_stylist'])
	    			|| request()->transaction_from == 'home-service'
	    		) {
	    			$productType = 'Service';
	    		}
	    		$promoItems[] = [
	    			'id_transaction_product' => $val['id_transaction_product'] ?? null,
	    			'id_product' => $val['id_product'] ?? null,
    				'id_brand' => $val['id_brand'],
    				'product_price' => $val['product_price'] ?? $val['transaction_product_price'],
    				'product_type' => $val['type'] ?? $productType,
	    			'qty' => $val['qty'] ?? $val['transaction_product_qty'] ?? 1
	    		];
	    	}
    	}

    	$sharedPromoTrx['items'] = $promoItems;
    	$sharedPromoTrx['subtotal'] = $dataTrx['subtotal'] ?? $dataTrx['transaction_subtotal'];
    	$sharedPromoTrx['tax'] = $dataTrx['tax'] ?? $dataTrx['transaction_tax'];
    	$sharedPromoTrx['service'] = $dataTrx['service'] ?? $dataTrx['transaction_service'] ?? 0;
    	$sharedPromoTrx['cashback'] = $dataTrx['cashback'] ?? $dataTrx['transaction_cashback_earned'] ?? 0;
    	$sharedPromoTrx['grandtotal'] = $dataTrx['grandtotal'] ?? $dataTrx['transaction_grandtotal'];

    	return true;
    }

    public function getSharedPromoTrx()
    {
    	$sharedPromoTrx = TemporaryDataManager::create('promo_trx');
    	return $sharedPromoTrx;
    }

    public function applyPromoNewTrx(Transaction $trxQuery)
    {	
    	$data = clone $trxQuery;
    	$data->load('transaction_products');
    	$data = $data->toArray();
    	$user = request()->user();
    	$resPromoCode = null;
    	$resDeals = null;
    	$userPromo = UserPromo::where('id_user', $user->id)->get()->keyBy('promo_type');

    	if (isset($userPromo['deals'])) {
    		$this->createSharedPromoTrx($data);

    		$applyDeals = $this->applyDeals($userPromo['deals']->id_reference, $data);

    		$promoDeals = $applyDeals['result'] ?? null;

			if ($applyDeals['status'] == 'fail') {
				return $applyDeals;
			}

			$data = $this->reformatNewTrx($trxQuery, $promoDeals ?? null);
    	}

    	if (isset($userPromo['promo_campaign'])) {
    		$this->createSharedPromoTrx($data);
			$dataDiscount['promo_source'] = 'promo_code';
    		$resPromo['promo_campaign'] = PromoCampaign::find($userPromo['promo_campaign']['id_reference']);
    	}

		
		$trxQuery = Transaction::find($trxQuery->id_transaction);

		return MyHelper::checkGet($trxQuery);
    }

    public function reformatNewTrx(Transaction $trxQuery, $dataDiscount)
    {
    	if (empty($dataDiscount['discount']) && empty($dataDiscount['discount_delivery'])) {
    		return $trxQuery;
    	}
    	$user = request()->user();
    	$promoCashback = ($dataDiscount['promo_source'] == 'deals') ? 'voucher_online' : 'promo_code';
    	$discountValue = (int) abs($dataDiscount['discount'] ?? $dataDiscount['discount_delivery']);
    	$sharedPromo = $this->getSharedPromoTrx();
		$outlet = OUtlet::find($sharedPromo['id_outlet']);
		$subtotal = $sharedPromo['subtotal'];
		$cashback = $sharedPromo['cashback'];
		$discount = (int) abs($dataDiscount['discount'] ?? 0);
		$discount_delivery = (int) abs($dataDiscount['discount_delivery'] ?? 0);
		$shipping = $trxQuery->transaction_shipment;
		$tax = ($outlet['is_tax'] / 100) * ($subtotal - $discount);
		$grandtotal = (int) $subtotal + (int) $sharedPromo['service'] + (int) $tax + (int) $shipping - $discount - $discount_delivery;

		$promoGetPoint = app($this->online_trx)->checkPromoGetPoint($promoCashback);
		$cashback_earned = $promoGetPoint ? $cashback : 0;

		$totalDiscount = abs($trxQuery->transaction_discount) + $discount;
		$totalDiscountDelivery = abs($trxQuery->transaction_discount_delivery) + $discount_delivery;
		$totalDiscountItem = abs($trxQuery->transaction_discount_item);
		$totalDiscountBill = abs($trxQuery->transaction_discount_bill);

		switch ($dataDiscount['promo_type']) {
			case 'Discount bill':
				$totalDiscountBill = $totalDiscountBill + $discount;
				break;
			
			case 'Product discount':
			case 'Tier discount':
			case 'Buy X Get Y':
			default:
				$totalDiscountItem = $totalDiscountItem + $discount;
				foreach ($sharedPromo['items'] as $item) {
					if (empty($item['is_promo'])) {
						continue;
					}
					TransactionProduct::where('id_transaction_product', $item['id_transaction_product'])
					->update([
						'transaction_product_discount' => $item['discount'],
						'transaction_product_discount_all' => $item['discount'],
						'transaction_product_qty_discount' => $item['qty_discount'],
						'transaction_product_base_discount' => $item['base_discount'],
					]);
				}
				break;
		}

		$trxQuery->update([
			'transaction_discount' => - $totalDiscount,
			'transaction_discount_delivery' => - $totalDiscountDelivery,
			'transaction_discount_item' => $totalDiscountItem,
			'transaction_discount_bill' => $totalDiscountBill,
	    	'transaction_tax' => $tax,
	    	'transaction_cashback_earned' => $cashback_earned,
	    	'transaction_grandtotal' => $grandtotal
		]);

		TransactionPromo::create([
			'id_transaction' => $trxQuery->id_transaction,
			'promo_name' => $dataDiscount['title'],
			'promo_type' => ($dataDiscount['promo_source'] == 'deals') ? 'Deals' : 'Promo Campaign',
			'id_deals_user' => $dataDiscount['id_deals_user'] ?? null,
			'id_promo_campaign_promo_code' => $dataDiscount['id_promo_campaign_promo_code'] ?? null,
			'discount_value' => $discount ?: $discount_delivery
		]);
		if ($dataDiscount['promo_source'] == 'deals') {
			$insertPromo = $this->insertUsedVoucher($trxQuery, $dataDiscount);
			UserPromo::where('id_user', $user->id)->where('promo_type', 'deals')->delete();
		} else {
			$insertPromo = $this->insertUsedCOde($trxQuery, $dataDiscount);
			UserPromo::where('id_user', $user->id)->where('promo_type', 'promo_campaign')->delete();
		}

		if ($insertPromo['status'] == 'fail') {
			return $insertPromo;
		}

        return ['status' => 'success'];
    }

    public function insertUsedVoucher(Transaction $trx, $dataDiscount)
    {
    	$dealsUser = DealsUser::find($dataDiscount['id_deals_user']);
    	$dealsUser->update(['used_at' => date('Y-m-d H:i:s'), 'is_used' => 0]);

    	$deals = Deal::find($dataDiscount['id_deals']);
    	$deals->update(['deals_total_used' => $deals->deals_total_used + 1]);

        $createTrxVoucher = TransactionVoucher::create([
            'id_deals_voucher' => $dealsUser->id_deals_user,
            'id_user' => $trx->id_user,
            'id_transaction' => $trx->id_transaction
        ]);

        if (!$createTrxVoucher) {
        	return $this->failResponse('Insert Voucher Failed');
        }

        return ['status' => 'success'];
    }

    public function insertUsedCode(Transaction $trx, $dataDiscount)
    {
        return ['status' => 'success'];
    }

    public function paymentDetailPromo($result)
    {
    	$paymentDetail = [];
    	if ((!empty($result['promo_deals']) && !$result['promo_deals']['is_error'])
        	|| (!empty($result['promo_code']) && !$result['promo_code']['is_error'])
    	) {
    		$paymentDetail[] = [
                'name'          => 'Promo / Discount:',
                "is_discount"   => 0,
                'amount'        => null
            ];

	        if (!empty($result['promo_deals']) && !$result['promo_deals']['is_error']) {
	            $paymentDetail[] = [
	                'name'          => $result['promo_deals']['title'],
	                "is_discount"   => 1,
	                'amount'        => '-' . number_format(((int) $result['promo_deals']['discount'] ?: $result['promo_deals']['discount_delivery']),0,',','.')
	            ];
	        }

	        if (!empty($result['promo_code']) && !$result['promo_code']['is_error']) {
	            $paymentDetail[] = [
	                'name'          => $result['promo_code']['title'],
	                "is_discount"   => 1,
	                'amount'        => '-' . number_format(((int) $result['promo_code']['discount'] ?: $result['promo_code']['discount_delivery']),0,',','.')
	            ];
	        }
        }

        return $paymentDetail;
    }
}
