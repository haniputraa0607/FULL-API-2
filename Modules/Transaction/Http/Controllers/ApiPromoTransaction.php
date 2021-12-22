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
                'time_expired_indo' => 'pukul '.date('H:i', strtotime($var['voucher_expired_at']))
            ];
        }, $voucher);
        
        return $result;
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
    	$resPromoCode = null;
    	$resDeals = null;
    	$userPromo = UserPromo::where('id_user', $user->id)->get()->keyBy('promo_type');

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
		$dataTrx['discount'] = $dataDiscount['discount'] ?? 0;
		$dataTrx['discount_delivery'] = $dataDiscount['discount_delivery'] ?? 0;
		$dataTrx['tax'] = ($outlet['is_tax'] / 100) * ($sharedPromo['subtotal'] - $dataTrx['discount']);
		$dataTrx['grandtotal'] = (int) $sharedPromo['subtotal'] + (int) $sharedPromo['service'] + (int) $dataTrx['tax'] - $discount;

		$promoGetPoint = app($this->online_trx)->checkPromoGetPoint($promoCashback);
        if ($promoGetPoint) {
			$earnedPoint = app($this->online_trx)->countTranscationPoint($dataTrx, $user);
	        $cashback = $earnedPoint['cashback'] ?? 0;
			$dataTrx['cashback'] = $cashback;
        } else {
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

    	$getProduct = app($this->promo_campaign)->getProduct('deals',$deals);
    	$desc = app($this->promo_campaign)->getPromoDescription('deals', $deals, $getProduct['product']??'');

    	$res = [
    		'id_deals_user' => $id_deals_user,
    		'title' => $deals->deals_title,
    		'discount' => $getDiscount['result']['discount'] ?? 0,
    		'discount_delivery' => $getDiscount['result']['discount_delivery'] ?? 0,
    		'promo_type' => $getDiscount['result']['promo_type'],
    		'text' => $desc,
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
    	// code...
    }

    public function tierDiscount($promoSource, $promoQuery, $data)
    {
    	// code...
    }

    public function bxgyDiscount($promoSource, $promoQuery, $data)
    {
    	// code...
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
			$promo_product_count = count($promo_product);

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
    	// code...
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
    	if (request()->transaction_from == 'academy') {
    		$promoItems[] = [
    			'id_product' => $items['id_product'],
    			'id_brand' => $items['id_brand'],
    			'product_price' => $items['product_price'],
    			'product_type' => 'academy',
    			'qty' => $items['qty']
    		];
    	} else {
	    	foreach ($items as $val) {
	    		$productType = 'product';
	    		if (isset($val['id_user_hair_stylist'])
	    			|| request()->transaction_from == 'home-service'
	    		) {
	    			$productType = 'service';
	    		}
	    		$promoItems[] = [
	    			'id_product' => $val['id_product'] ?? null,
    				'id_brand' => $val['id_brand'],
    				'product_price' => $val['product_price'],
    				'product_type' => $productType,
	    			'qty' => $val['qty'] ?? $val['transaction_product_qty'] ?? 1
	    		];
	    	}
    	}

    	$sharedPromoTrx['items'] = $promoItems;
    	$sharedPromoTrx['subtotal'] = $dataTrx['subtotal'] ?? $dataTrx['transaction_subtotal'];
    	$sharedPromoTrx['tax'] = $dataTrx['tax'] ?? $dataTrx['transaction_tax'];
    	$sharedPromoTrx['service'] = $dataTrx['service'] ?? $dataTrx['transaction_service'];
    	$sharedPromoTrx['cashback'] = $dataTrx['cashback'] ?? $dataTrx['transaction_cashback_earned'];
    	$sharedPromoTrx['grandtotal'] = $dataTrx['grandtotal'] ?? $dataTrx['transaction_grandtotal'];

    	return true;
    }

    public function getSharedPromoTrx()
    {
    	$sharedPromoTrx = TemporaryDataManager::create('promo_trx');
    	return $sharedPromoTrx;
    }
}
