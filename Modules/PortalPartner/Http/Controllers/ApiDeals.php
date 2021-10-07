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
        $deals = Deal::where('deals.deals_start','>', date('Y-m-d H:i:s')) 
                 ->join('deals_brands','deals_brands.id_deals','deals.id_deals')
                 ->join('brands','brands.id_brand','deals_brands.id_brand')
                 ->join('deals_outlets','deals_outlets.id_deals','deals.id_deals')
                 ->join('outlets','outlets.id_outlet','deals_outlets.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner))
                ->Select('deals.*','brands.*');
        
          if ($request->json('id_outlet') && is_integer($request->json('id_outlet'))) {
                $deals->where('deals_outlets.id_outlet', $request->json('id_outlet'));
        }
        // brand
        if ($request->json('id_brand')) {
            
            $deals->join('deals_brand','deals_brand.id_deals','deals.id_deals')
            ->where('deals_brand.id_brand',$request->json('id_brand'));
        }
        
        // deals subscription
        if ($request->json('deals_type') == "Subscription") {
            $deals->with('deals_subscriptions');
        }
        
        if ($request->json('id_deals')) {
            $deals->with([
                'deals_vouchers'
            ])->where('deals.id_deals', $request->json('id_deals'))->with(['deals_content', 'deals_content.deals_content_details', 'outlets', 'outlets.city', 'product','brand']);
        }
        if ($request->json('rule')){
             $this->filterList($deals,$request->json('rule'),$request->json('operator')??'and');
        }
        if ($request->json('publish')) {
            $deals->where( function($q) {
            	$q->where('deals.deals_publish_start', '<=', date('Y-m-d H:i:s'))
            		->where('deals.deals_publish_end', '>=', date('Y-m-d H:i:s'));
            });

            $deals->where( function($q) {
	        	$q->where('deals.deals_voucher_type','Unlimited')
	        		->orWhereRaw('(deals.deals_total_voucher - deals.deals_total_claimed) > 0 ');
	        });
            $deals->where('deals.step_complete', '=', 1);

            $deals->whereDoesntHave('deals_user_limits', function($q) use ($user){
            	$q->where('deals.id_user',$user->id);
            });
        }

        if ($request->json('deals_type')) {
            // get > 1 deals types
            if (is_array($request->json('deals_type'))) {
                $deals->whereIn('deals.deals_type', $request->json('deals_type'));
            } else {
                $deals->where('deals.deals_type', $request->json('deals_type'));
            }
        }

		if ($request->json('deals_type_array')) {
            // get > 1 deals types
            $deals->whereIn('deals.deals_type', $request->json('deals_type_array'));
        }        

        if ($request->json('deals_promo_id')) {
            $deals->where('deals.deals_promo_id', $request->json('deals_promo_id'));
        }

        if ($request->json('key_free')) {
            $deals->where(function($query) use ($request){
                $query->where('deals.deals_title', 'LIKE', '%' . $request->json('key_free') . '%')
                    ->orWhere('deals.deals_second_title', 'LIKE', '%' . $request->json('key_free') . '%');
            });
        }


        /* ========================= TYPE ========================= */
        $deals->where(function ($query) use ($request) {
            // cash
            if ($request->json('voucher_type_paid')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('deals.deals_voucher_price_cash');
                    if(is_numeric($val=$request->json('price_range_start'))){
                        $amp->where('deals.deals_voucher_price_cash','>=',$val);
                    }
                    if(is_numeric($val=$request->json('price_range_end'))){
                        $amp->where('deals.deals_voucher_price_cash','<=',$val);
                    }
                });
            }

            if ($request->json('voucher_type_point')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('deals.deals_voucher_price_point');
                    if(is_numeric($val=$request->json('point_range_start'))){
                        $amp->where('deals.deals_voucher_price_point','>=',$val);
                    }
                    if(is_numeric($val=$request->json('point_range_end'))){
                        $amp->where('deals.deals_voucher_price_point','<=',$val);
                    }
                });
            }

            if ($request->json('voucher_type_free')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNull('deals.deals_voucher_price_point')->whereNull('deals.deals_voucher_price_cash');
                });
                // print_r('voucher_type_free');
                // print_r($query->get()->toArray());die();
            }
        });

        // print_r($deals->get()->toArray());
        // $deals = $deals->orderBy('deals_start', 'ASC');

        if ($request->json('lowest_point')) {
            $deals->orderBy('deals.deals_voucher_price_point', 'ASC');
        }

        if ($request->json('highest_point')) {
            $deals->orderBy('deals.deals_voucher_price_point', 'DESC');
        }

        if ($request->json('alphabetical')) {
            $deals->orderBy('deals.deals_title', 'ASC');
        } else if ($request->json('newest')) {
            $deals->orderBy('deals.deals_publish_start', 'DESC');
        } else if ($request->json('oldest')) {
            $deals->orderBy('deals.deals_publish_start', 'ASC');
        } else if ($request->json('updated_at')) {
            $deals->orderBy('deals.updated_at', 'DESC');
        } else {
            $deals->orderBy('deals.deals_end', 'ASC');
        }
        if ($request->json('id_city')) {
            $deals->with('outlets','outlets.city');
        }

        if ($request->json('paginate')) {
        	return $this->dealsPaginate($deals);
        }

        $deals = $deals->get()->toArray();

        if (!empty($deals)) {
            $city = "";

            // jika ada id city yg faq
            if ($request->json('id_city')) {
                $city = $request->json('id_city');
            }

            $deals = $this->kotacuks($deals, $city,$request->json('admin'));
        }

        if ($request->json('highest_available_voucher')) {
            $tempDeals = [];
            $dealsUnlimited = $this->unlimited($deals);

            if (!empty($dealsUnlimited)) {
                foreach ($dealsUnlimited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }
            }

            $limited = $this->limited($deals);

            if (!empty($limited)) {
                $tempTempDeals = [];
                foreach ($limited as $key => $value) {
                    array_push($tempTempDeals, $deals[$key]);
                }

                $tempTempDeals = $this->highestAvailableVoucher($tempTempDeals);

                $tempDeals =  array_merge($tempDeals, $tempTempDeals);
            }

            $deals = $tempDeals;
        }

        if ($request->json('lowest_available_voucher')) {
            $tempDeals = [];

            $limited = $this->limited($deals);

            if (!empty($limited)) {
                foreach ($limited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }

                $tempDeals = $this->lowestAvailableVoucher($tempDeals);
            }

            $dealsUnlimited = $this->unlimited($deals);

            if (!empty($dealsUnlimited)) {
                foreach ($dealsUnlimited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }
            }

            $deals = $tempDeals;
        }



        // if deals detail, add webview url & btn text
        if ($request->json('id_deals') && !empty($deals)) {
            //url webview
            $deals[0]['webview_url'] = config('url.app_url') . "webview/deals/" . $deals[0]['id_deals'] . "/" . $deals[0]['deals_type'];
            // text tombol beli
            $deals[0]['button_status'] = 0;
            //text konfirmasi pembelian
            if($deals[0]['deals_voucher_price_type']=='free'){
                //voucher free
                $deals[0]['button_text'] = 'Ambil';
                $payment_message = Setting::where('key', 'payment_messages')->pluck('value_text')->first()??'Kamu yakin ingin mengambil voucher ini?';
                $payment_message = MyHelper::simpleReplace($payment_message,['deals_title'=>$deals[0]['deals_title']]);
            }
            elseif($deals[0]['deals_voucher_price_type']=='point')
            {
                $deals[0]['button_text'] = 'Tukar';
                $payment_message = Setting::where('key', 'payment_messages_point')->pluck('value_text')->first()??'Anda akan menukarkan %point% points anda dengan Voucher %deals_title%?';
                $payment_message = MyHelper::simpleReplace($payment_message,['point'=>$deals[0]['deals_voucher_price_point'],'deals_title'=>$deals[0]['deals_title']]);
            }
            else
            {
                $deals[0]['button_text'] = 'Beli';
                $payment_message = Setting::where('key', 'payment_messages_cash')->pluck('value_text')->first()??'Anda akan membeli Voucher %deals_title% dengan harga %cash% ?';
                $payment_message = MyHelper::simpleReplace($payment_message,['cash'=>$deals[0]['deals_voucher_price_cash'],'deals_title'=>$deals[0]['deals_title']]);
            }
            $payment_success_message = Setting::where('key', 'payment_success_messages')->pluck('value_text')->first()??'Apakah kamu ingin menggunakan Voucher sekarang?';
            $deals[0]['payment_message'] = $payment_message;
            $deals[0]['payment_success_message'] = $payment_success_message;
            if($deals[0]['deals_voucher_price_type']=='free'&&$deals[0]['deals_status']=='available'){
                $deals[0]['button_status']=1;
            }else {
                if($deals[0]['deals_voucher_price_type']=='point'){
                    $deals[0]['button_status']=$deals[0]['deals_voucher_price_point']<=$curBalance?1:0;
                    if($deals[0]['deals_voucher_price_point']>$curBalance){
                        $deals[0]['payment_fail_message'] = Setting::where('key', 'payment_fail_messages')->pluck('value_text')->first()??'Mohon maaf, point anda tidak cukup';
                    }
                }else{
                    if($deals[0]['deals_status']=='available'){
                        $deals[0]['button_status'] = 1;
                    }
                }
            }
        }

        //jika mobile di pagination
        if (!$request->json('web')) {
            $result = $this->dealsPaginate($deals);
            if($request->json('voucher_type_point') ||
                $request->json('voucher_type_paid') ||
                $request->json('voucher_type_free') ||
                $request->json('id_city') ||
                $request->json('key_free')
            ){
                $resultMessage = 'Maaf, voucher yang kamu cari belum tersedia';
                 return response()->json(MyHelper::checkGet($result, $resultMessage));
            }else{
                $resultMessage = 'Nantikan penawaran menarik dari kami';
                 return response()->json(MyHelper::checkGet($result, $resultMessage));
            }
        }
        else{
            $deals = $this->dealsPaginate($deals);
            return response()->json(MyHelper::checkGet($deals));
        }
    }
    function listDealActive(ListDeal $request) 
    {
    	$post = $request->json()->all();
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        $deals = Deal::where('deals.deals_start','<', date('Y-m-d H:i:s')) 
                 ->where('deals.deals_end','>',date('Y-m-d H:i:s'))
                 ->join('deals_brands','deals_brands.id_deals','deals.id_deals')
                 ->join('brands','brands.id_brand','deals_brands.id_brand')
                 ->join('deals_outlets','deals_outlets.id_deals','deals.id_deals')
                 ->join('outlets','outlets.id_outlet','deals_outlets.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner))
                ->Select('deals.*','brands.*');
        
          if ($request->json('id_outlet') && is_integer($request->json('id_outlet'))) {
                $deals->where('deals_outlets.id_outlet', $request->json('id_outlet'));
        }
        // brand
        if ($request->json('id_brand')) {
            
            $deals->join('deals_brand','deals_brand.id_deals','deals.id_deals')
            ->where('deals_brand.id_brand',$request->json('id_brand'));
        }
        
        // deals subscription
        if ($request->json('deals_type') == "Subscription") {
            $deals->with('deals_subscriptions');
        }
        
        if ($request->json('id_deals')) {
            $deals->with([
                'deals_vouchers'
            ])->where('deals.id_deals', $request->json('id_deals'))->with(['deals_content', 'deals_content.deals_content_details', 'outlets', 'outlets.city', 'product','brand']);
        }
        if ($request->json('rule')){
             $this->filterList($deals,$request->json('rule'),$request->json('operator')??'and');
        }
        if ($request->json('publish')) {
            $deals->where( function($q) {
            	$q->where('deals.deals_publish_start', '<=', date('Y-m-d H:i:s'))
            		->where('deals.deals_publish_end', '>=', date('Y-m-d H:i:s'));
            });

            $deals->where( function($q) {
	        	$q->where('deals.deals_voucher_type','Unlimited')
	        		->orWhereRaw('(deals.deals_total_voucher - deals.deals_total_claimed) > 0 ');
	        });
            $deals->where('deals.step_complete', '=', 1);

            $deals->whereDoesntHave('deals_user_limits', function($q) use ($user){
            	$q->where('deals.id_user',$user->id);
            });
        }

        if ($request->json('deals_type')) {
            // get > 1 deals types
            if (is_array($request->json('deals_type'))) {
                $deals->whereIn('deals.deals_type', $request->json('deals_type'));
            } else {
                $deals->where('deals.deals_type', $request->json('deals_type'));
            }
        }

		if ($request->json('deals_type_array')) {
            // get > 1 deals types
            $deals->whereIn('deals.deals_type', $request->json('deals_type_array'));
        }        

        if ($request->json('deals_promo_id')) {
            $deals->where('deals.deals_promo_id', $request->json('deals_promo_id'));
        }

        if ($request->json('key_free')) {
            $deals->where(function($query) use ($request){
                $query->where('deals.deals_title', 'LIKE', '%' . $request->json('key_free') . '%')
                    ->orWhere('deals.deals_second_title', 'LIKE', '%' . $request->json('key_free') . '%');
            });
        }


        /* ========================= TYPE ========================= */
        $deals->where(function ($query) use ($request) {
            // cash
            if ($request->json('voucher_type_paid')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('deals.deals_voucher_price_cash');
                    if(is_numeric($val=$request->json('price_range_start'))){
                        $amp->where('deals.deals_voucher_price_cash','>=',$val);
                    }
                    if(is_numeric($val=$request->json('price_range_end'))){
                        $amp->where('deals.deals_voucher_price_cash','<=',$val);
                    }
                });
            }

            if ($request->json('voucher_type_point')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('deals.deals_voucher_price_point');
                    if(is_numeric($val=$request->json('point_range_start'))){
                        $amp->where('deals.deals_voucher_price_point','>=',$val);
                    }
                    if(is_numeric($val=$request->json('point_range_end'))){
                        $amp->where('deals.deals_voucher_price_point','<=',$val);
                    }
                });
            }

            if ($request->json('voucher_type_free')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNull('deals.deals_voucher_price_point')->whereNull('deals.deals_voucher_price_cash');
                });
                // print_r('voucher_type_free');
                // print_r($query->get()->toArray());die();
            }
        });

        // print_r($deals->get()->toArray());
        // $deals = $deals->orderBy('deals_start', 'ASC');

        if ($request->json('lowest_point')) {
            $deals->orderBy('deals.deals_voucher_price_point', 'ASC');
        }

        if ($request->json('highest_point')) {
            $deals->orderBy('deals.deals_voucher_price_point', 'DESC');
        }

        if ($request->json('alphabetical')) {
            $deals->orderBy('deals.deals_title', 'ASC');
        } else if ($request->json('newest')) {
            $deals->orderBy('deals.deals_publish_start', 'DESC');
        } else if ($request->json('oldest')) {
            $deals->orderBy('deals.deals_publish_start', 'ASC');
        } else if ($request->json('updated_at')) {
            $deals->orderBy('deals.updated_at', 'DESC');
        } else {
            $deals->orderBy('deals.deals_end', 'ASC');
        }
        if ($request->json('id_city')) {
            $deals->with('outlets','outlets.city');
        }

        if ($request->json('paginate')) {
        	return $this->dealsPaginate($deals);
        }

        $deals = $deals->get()->toArray();

        if (!empty($deals)) {
            $city = "";

            // jika ada id city yg faq
            if ($request->json('id_city')) {
                $city = $request->json('id_city');
            }

            $deals = $this->kotacuks($deals, $city,$request->json('admin'));
        }

        if ($request->json('highest_available_voucher')) {
            $tempDeals = [];
            $dealsUnlimited = $this->unlimited($deals);

            if (!empty($dealsUnlimited)) {
                foreach ($dealsUnlimited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }
            }

            $limited = $this->limited($deals);

            if (!empty($limited)) {
                $tempTempDeals = [];
                foreach ($limited as $key => $value) {
                    array_push($tempTempDeals, $deals[$key]);
                }

                $tempTempDeals = $this->highestAvailableVoucher($tempTempDeals);

                $tempDeals =  array_merge($tempDeals, $tempTempDeals);
            }

            $deals = $tempDeals;
        }

        if ($request->json('lowest_available_voucher')) {
            $tempDeals = [];

            $limited = $this->limited($deals);

            if (!empty($limited)) {
                foreach ($limited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }

                $tempDeals = $this->lowestAvailableVoucher($tempDeals);
            }

            $dealsUnlimited = $this->unlimited($deals);

            if (!empty($dealsUnlimited)) {
                foreach ($dealsUnlimited as $key => $value) {
                    array_push($tempDeals, $deals[$key]);
                }
            }

            $deals = $tempDeals;
        }



        // if deals detail, add webview url & btn text
        if ($request->json('id_deals') && !empty($deals)) {
            //url webview
            $deals[0]['webview_url'] = config('url.app_url') . "webview/deals/" . $deals[0]['id_deals'] . "/" . $deals[0]['deals_type'];
            // text tombol beli
            $deals[0]['button_status'] = 0;
            //text konfirmasi pembelian
            if($deals[0]['deals_voucher_price_type']=='free'){
                //voucher free
                $deals[0]['button_text'] = 'Ambil';
                $payment_message = Setting::where('key', 'payment_messages')->pluck('value_text')->first()??'Kamu yakin ingin mengambil voucher ini?';
                $payment_message = MyHelper::simpleReplace($payment_message,['deals_title'=>$deals[0]['deals_title']]);
            }
            elseif($deals[0]['deals_voucher_price_type']=='point')
            {
                $deals[0]['button_text'] = 'Tukar';
                $payment_message = Setting::where('key', 'payment_messages_point')->pluck('value_text')->first()??'Anda akan menukarkan %point% points anda dengan Voucher %deals_title%?';
                $payment_message = MyHelper::simpleReplace($payment_message,['point'=>$deals[0]['deals_voucher_price_point'],'deals_title'=>$deals[0]['deals_title']]);
            }
            else
            {
                $deals[0]['button_text'] = 'Beli';
                $payment_message = Setting::where('key', 'payment_messages_cash')->pluck('value_text')->first()??'Anda akan membeli Voucher %deals_title% dengan harga %cash% ?';
                $payment_message = MyHelper::simpleReplace($payment_message,['cash'=>$deals[0]['deals_voucher_price_cash'],'deals_title'=>$deals[0]['deals_title']]);
            }
            $payment_success_message = Setting::where('key', 'payment_success_messages')->pluck('value_text')->first()??'Apakah kamu ingin menggunakan Voucher sekarang?';
            $deals[0]['payment_message'] = $payment_message;
            $deals[0]['payment_success_message'] = $payment_success_message;
            if($deals[0]['deals_voucher_price_type']=='free'&&$deals[0]['deals_status']=='available'){
                $deals[0]['button_status']=1;
            }else {
                if($deals[0]['deals_voucher_price_type']=='point'){
                    $deals[0]['button_status']=$deals[0]['deals_voucher_price_point']<=$curBalance?1:0;
                    if($deals[0]['deals_voucher_price_point']>$curBalance){
                        $deals[0]['payment_fail_message'] = Setting::where('key', 'payment_fail_messages')->pluck('value_text')->first()??'Mohon maaf, point anda tidak cukup';
                    }
                }else{
                    if($deals[0]['deals_status']=='available'){
                        $deals[0]['button_status'] = 1;
                    }
                }
            }
        }

        //jika mobile di pagination
        if (!$request->json('web')) {
            $result = $this->dealsPaginate($deals);
            if($request->json('voucher_type_point') ||
                $request->json('voucher_type_paid') ||
                $request->json('voucher_type_free') ||
                $request->json('id_city') ||
                $request->json('key_free')
            ){
                $resultMessage = 'Maaf, voucher yang kamu cari belum tersedia';
                 return response()->json(MyHelper::checkGet($result, $resultMessage));
            }else{
                $resultMessage = 'Nantikan penawaran menarik dari kami';
                 return response()->json(MyHelper::checkGet($result, $resultMessage));
            }
        }
        else{
            $deals = $this->dealsPaginate($deals);
            return response()->json(MyHelper::checkGet($deals));
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
        $subjects=['deals_title','deals_title','deals_second_title','deals_promo_id_type','deals_promo_id','id_brand','deals_total_voucher','deals_start', 'deals_end', 'deals_publish_start', 'deals_publish_end', 'deals_voucher_start', 'deals_voucher_expired', 'deals_voucher_duration', 'user_limit', 'total_voucher_subscription', 'deals_total_claimed', 'deals_total_redeemed', 'deals_total_used', 'created_at', 'updated_at'];
        foreach ($subjects as $subject) {
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query->$where($subject,$rule[0],$rule[1]);
                }
            }
        }
        if($rules2=$newRule['voucher_code']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('deals_vouchers',function($query) use ($rule){
                    $query->where('deals_vouchers.voucher_code',$rule[0],$rule[1]);
                });
            }
        }
        if($rules2=$newRule['used_by']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('deals_vouchers.deals_voucher_user',function($query) use ($rule){
                    $query->where('phone',$rule[0],$rule[1]);
                });
            }
        }
        if($rules2=$newRule['deals_total_available']??false){
            foreach ($rules2 as $rule) {
                $query->$where(DB::raw('(deals.deals_total_voucher - deals.deals_total_claimed)'),$rule[0],$rule[1]);
            }
        }
        if($rules2=$newRule['id_outlet']??false){
            foreach ($rules2 as $rule) {
                $query->{$where.'Has'}('outlets',function($query) use ($rule){
                    $query->where('outlets.id_outlet',$rule[0],$rule[1]);
                });
            }
        }
        if($rules2=$newRule['voucher_claim_time']??false){
            foreach ($rules2 as $rule) {
                $rule[1]=strtotime($rule[1]);
                $query->{$where.'Has'}('deals_vouchers',function($query) use ($rule){
                    $query->whereHas('deals_user',function($query) use ($rule){
                        $query->where(DB::raw('UNIX_TIMESTAMP(deals_users.claimed_at)'),$rule[0],$rule[1]);
                    });
                });
            }
        }
        if($rules2=$newRule['voucher_redeem_time']??false){
            foreach ($rules2 as $rule) {
                $rule[1]=strtotime($rule[1]);
                $query->{$where.'Has'}('deals_vouchers',function($query) use ($rule){
                    $query->whereHas('deals_user',function($query) use ($rule){
                        $query->where('deals_users.redeemed_at',$rule[0],$rule[1]);
                    });
                });
            }
        }
        if($rules2=$newRule['voucher_used_time']??false){
            foreach ($rules2 as $rule) {
                $rule[1]=strtotime($rule[1]);
                $query->{$where.'Has'}('deals_vouchers',function($query) use ($rule){
                    $query->whereHas('deals_user',function($query) use ($rule){
                        $query->where('deals_users.used_at',$rule[0],$rule[1]);
                    });
                });
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
                    $btnDelete = '<a class="btn btn-sm btn-primary text-nowrap" target="_blank" href="'. url('report/promo/deals/detail/'.$data['id_deals']).'/'.$data['deals_type'].'"><i class="fa fa-search" style="font-size : 14px; padding-right : 0px"></i></a>';  
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
                ->editColumn('deals_publish_start', function($row) {
                  $publish_start = date('d M Y', strtotime($row['deals_publish_start'])).' - '.date('d M Y', strtotime($row['deals_publish_end']));
                  return $publish_start;
                })
                ->editColumn('deals_start', function($row) {
                  $publish_start = date('d M Y', strtotime($row['deals_start'])).' - '.date('d M Y', strtotime($row['deals_end']));
                  return $publish_start;
                })
                ->make(true);
                
		return $query;
	}



}
