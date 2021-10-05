<?php

namespace Modules\PortalPartner\Http\Controllers;


//use Modules\Franchise\Entities\Deal;
use App\Http\Models\Deal;
use App\Http\Models\Setting;
use Modules\Franchise\Entities\PromoCampaign;

use Modules\Franchise\Entities\Subscription;

use Modules\Franchise\Entities\Bundling;

use Modules\Franchise\Entities\SubscriptionUserVoucher;

use Modules\Franchise\Entities\TransactionBundlingProduct;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Http\Requests\ListSubscription;
use App\Lib\MyHelper;
use DB;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use Modules\BusinessDevelopment\Entities\Partner;

class ApiPromoController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

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
    public function listDealBeforeActive(Request $request)
    {
    	$post = $request->json()->all();
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        $deals = Deal::where('deals.deals_start','>', date('Y-m-d H:i:s')) 
                 ->join('deals_outlets','deals_outlets.id_deals','deals.id_deals')
                 ->join('outlets','outlets.id_outlet','deals_outlets.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner));

          if ($request->json('id_outlet') && is_integer($request->json('id_outlet'))) {
                $deals->where('deals_outlets.id_outlet', $request->json('id_outlet'))
                ->addSelect('deals.*')->distinct();
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
        }else{
            $deals->Select('deals.id_deals','deals.deals_title','deals.deals_second_title','deals.deals_voucher_price_point','deals.deals_voucher_price_cash','deals.deals_total_voucher','deals.deals_total_claimed','deals.deals_voucher_type','deals.deals_image','deals.deals_start','deals.deals_end','deals.deals_type','deals.is_offline','deals.is_online','deals.step_complete','deals.deals_total_used','deals.promo_type','deals.deals_promo_id_type','deals.deals_promo_id');
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

        if ($request->json('paginate') && $request->json('admin')) {
        	return $this->dealsPaginate($deals, $request);
        }

        $deals = $deals->get()->toArray();
        // print_r($deals); exit();

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
            //pagination
            if ($request->get('page')) {
                $page = $request->get('page');
            } else {
                $page = 1;
            }

            $resultData = [];
            $paginate   = 10;
            $start      = $paginate * ($page - 1);
            $all        = $paginate * $page;
            $end        = $all;
            $next       = true;

            if ($all > count($deals)) {
                $end = count($deals);
                $next = false;
            }


            for ($i=$start; $i < $end; $i++) {
                $deals[$i]['time_to_end']=strtotime($deals[$i]['deals_end'])-time();
                array_push($resultData, $deals[$i]);
            }

            $result['current_page']  = $page;
            $result['data']          = $resultData;
            $result['total']         = count($resultData);
            $result['next_page_url'] = null;
            if ($next == true) {
                $next_page = (int) $page + 1;
                $result['next_page_url'] = ENV('APP_API_URL') . 'api/deals/list?page=' . $next_page;
            }


            // print_r($deals); exit();
            if(!$result['total']){
                $result=[];
            }

            if(
                $request->json('voucher_type_point') ||
                $request->json('voucher_type_paid') ||
                $request->json('voucher_type_free') ||
                $request->json('id_city') ||
                $request->json('key_free')
            ){
                $resultMessage = 'Maaf, voucher yang kamu cari belum tersedia';
            }else{
                $resultMessage = 'Nantikan penawaran menarik dari kami';
            }
            return response()->json(MyHelper::checkGet($result, $resultMessage));

        }else{
            return response()->json(MyHelper::checkGet($deals));
        }
    }
    public function listDealActive( Request $request)
    {
    	$post = $request->json()->all();
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        $deals = Deal::where('deals.deals_start','<', date('Y-m-d H:i:s')) 
                 ->where('deals.deals_end','>',date('Y-m-d H:i:s'))
                 ->join('deals_outlets','deals_outlets.id_deals','deals.id_deals')
                 ->join('outlets','outlets.id_outlet','deals_outlets.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner));

          if ($request->json('id_outlet') && is_integer($request->json('id_outlet'))) {
                $deals->where('deals_outlets.id_outlet', $request->json('id_outlet'))
                ->addSelect('deals.*')->distinct();
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
        }else{
            $deals->Select('deals.id_deals','deals.deals_title','deals.deals_second_title','deals.deals_voucher_price_point','deals.deals_voucher_price_cash','deals.deals_total_voucher','deals.deals_total_claimed','deals.deals_voucher_type','deals.deals_image','deals.deals_start','deals.deals_end','deals.deals_type','deals.is_offline','deals.is_online','deals.step_complete','deals.deals_total_used','deals.promo_type','deals.deals_promo_id_type','deals.deals_promo_id');
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

        if ($request->json('paginate') && $request->json('admin')) {
        	return $this->dealsPaginate($deals, $request);
        }

        $deals = $deals->get()->toArray();
        // print_r($deals); exit();

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
            //pagination
            if ($request->get('page')) {
                $page = $request->get('page');
            } else {
                $page = 1;
            }

            $resultData = [];
            $paginate   = 10;
            $start      = $paginate * ($page - 1);
            $all        = $paginate * $page;
            $end        = $all;
            $next       = true;

            if ($all > count($deals)) {
                $end = count($deals);
                $next = false;
            }


            for ($i=$start; $i < $end; $i++) {
                $deals[$i]['time_to_end']=strtotime($deals[$i]['deals_end'])-time();
                array_push($resultData, $deals[$i]);
            }

            $result['current_page']  = $page;
            $result['data']          = $resultData;
            $result['total']         = count($resultData);
            $result['next_page_url'] = null;
            if ($next == true) {
                $next_page = (int) $page + 1;
                $result['next_page_url'] = ENV('APP_API_URL') . 'api/deals/list?page=' . $next_page;
            }


            // print_r($deals); exit();
            if(!$result['total']){
                $result=[];
            }

            if(
                $request->json('voucher_type_point') ||
                $request->json('voucher_type_paid') ||
                $request->json('voucher_type_free') ||
                $request->json('id_city') ||
                $request->json('key_free')
            ){
                $resultMessage = 'Maaf, voucher yang kamu cari belum tersedia';
            }else{
                $resultMessage = 'Nantikan penawaran menarik dari kami';
            }
            return response()->json(MyHelper::checkGet($result, $resultMessage));

        }else{
            return response()->json(MyHelper::checkGet($deals));
        }
    }
    
    public function listPromoCampaignBeforeActive(Request $request)
    {
        
        $post = $request->json()->all();
        $promo_type = $request->promo_type;
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        try {
            $query = PromoCampaign::with([
                        'user'
                    ])
                    ->where(function($query){
                        $query
                              ->where('promo_campaigns.promo_type', '!=', 'Referral')
                              ->orWhereNull('promo_campaigns.promo_type');
                    })
                 ->where('promo_campaigns.date_start','>', date('Y-m-d H:i:s')) 
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

            if(!empty($query)){
                $query = $query->paginate(10)->toArray();
                $result = [
                    'status'     => 'success',
                    'result'     => $query,
                    'count'      => count($query)
                ];
            }else{

                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Promo Campaign is empty']
                ]);
            }

            if ($filter??false) {
                $result = array_merge($result, $filter);
            }

            return response()->json($result);
            
        } catch (\Exception $e) {
            
            return response()->json(['status' => 'error', 'messages' => [$e->getMessage()]]);
        }
    }
    public function listPromoCampaignActive(Request $request)
    {
        
        $post = $request->json()->all();
        $promo_type = $request->promo_type;
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        try {
            $query = PromoCampaign::with([
                        'user'
                    ])
                    ->where(function($query){
                        $query
                              ->where('promo_campaigns.promo_type', '!=', 'Referral')
                              ->orWhereNull('promo_campaigns.promo_type');
                    })
                 ->where('promo_campaigns.date_start','<', date('Y-m-d H:i:s')) 
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

            if(!empty($query)){
                $query = $query->paginate(10)->toArray();
                $result = [
                    'status'     => 'success',
                    'result'     => $query,
                    'count'      => count($query)
                ];
            }else{

                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Promo Campaign is empty']
                ]);
            }

            if ($filter??false) {
                $result = array_merge($result, $filter);
            }

            return response()->json($result);
            
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
   
     public function listSubscriptionBeforeActive(ListSubscription $request)
    {
        $post = $request->json()->all(); 
        $subs = Subscription::join('subscription_outlets', 'subscription_outlets.id_subscription', 'subscriptions.id_subscription')
                 ->join('outlets','outlets.id_outlet','subscription_outlets.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where('subscriptions.subscription_start','>', date('Y-m-d H:i:s'))
                 ->where(array('partners.id_partner'=>$request->id_partner));
        $user = $request->user();
        $curBalance = (int) $user->balance??0;

        if ($request->json('forSelect2')) {
            return MyHelper::checkGet($subs->with(['outlets', 'users'])->whereDoesntHave('featured_subscriptions')->get());
        }

        if ($request->json('id_outlet') && is_integer($request->json('id_outlet'))) {
            $subs = $subs->where('outlets.id_outlet', $request->json('id_outlet'))
                        ->addSelect('subscriptions.*')->distinct();
        }
        
        if ( empty($request->json('admin')) ) {
            $subs = $subs->whereNotNull('subscriptions.subscription_step_complete');
        }

        if ( $request->json('with_brand') ) {
            $subs = $subs->with(['brand', 'brands']);
        }

        if ($request->json('id_subscription')) {
            // add content for detail subscription
            $subs = $subs->where('subscriptions.id_subscription', '=', $request->json('id_subscription'))
                        ->with([
                            'outlets.city',
                            'subscription_content' => function($q) {
                                $q->orderBy('order')
                                    ->where('is_active', '=', 1)
                                    ->addSelect(
                                        'id_subscription', 
                                        'id_subscription_content', 
                                        'title',
                                        'order'
                                    );
                            },
                            'subscription_content.subscription_content_details' => function($q) {
                                $q->orderBy('order')
                                    ->addSelect(
                                        'id_subscription_content_detail',
                                        'id_subscription_content',
                                        'content',
                                        'order'
                                    );
                            }
                        ]);
        }

        if ($request->json('publish')) {
            $subs = $subs->where('subscriptions.subscription_publish_end', '>=', date('Y-m-d H:i:s'));
        }

        if ($request->json('subscription_type')) {
            $subs = $subs->where('subscriptions.subscription_type', '=', $request->json('subscription_type'));
        }

        if ($request->json('key_free')) {
            $subs = $subs->where(function($query) use ($request){
                $query->where('subscriptions.subscription_title', 'LIKE', '%' . $request->json('key_free') . '%')
                    ->orWhere('subscriptions.subscription_sub_title', 'LIKE', '%' . $request->json('key_free') . '%');
            });
        }

        $subs->where(function ($query) use ($request) {

            // Cash
            if ($request->json('subscription_type_paid')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('subscriptions.subscription_price_cash');
                    if(is_numeric($val=$request->json('price_range_start'))){
                        $amp->where('subscriptions.subscription_price_cash','>=',$val);
                    }
                    if(is_numeric($val=$request->json('price_range_end'))){
                        $amp->where('subscriptions.subscription_price_cash','<=',$val);
                    }
                });
            }

            // Point
            if ($request->json('subscription_type_point')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('subscriptions.subscription_price_point');
                    if(is_numeric($val=$request->json('point_range_start'))){
                        $amp->where('subscriptions.subscription_price_point','>=',$val);
                    }
                    if(is_numeric($val=$request->json('point_range_end'))){
                        $amp->where('subscriptions.subscription_price_point','<=',$val);
                    }
                });
            }

            // Free
            if ($request->json('subscription_type_free')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNull('subscriptions.subscription_price_point')->whereNull('subscriptions.subscription_price_cash');
                });
            }
        });

        if ($request->json('lowest_point')) {
            $subs->orderBy('subscriptions.subscription_price_point', 'ASC');
        }
        if ($request->json('highest_point')) {
            $subs->orderBy('subscriptions.subscription_price_point', 'DESC');
        }

        if ($request->json('alphabetical')) {
            $subs->orderBy('subscriptions.subscription_title', 'ASC');

        } else if ($request->json('alphabetical-desc')) {
            $subs->orderBy('subscriptions.subscription_title', 'DESC');

        } else if ($request->json('newest')) {
            $subs->orderBy('subscriptions.subscription_publish_start', 'DESC');

        } else if ($request->json('oldest')) {
            $subs->orderBy('subscriptions.subscription_publish_start', 'ASC');

        } else {
            $subs->orderBy('subscriptions.subscription_end', 'ASC');
        }
        if ($request->json('id_city')) {
            $subs->with('outlets','outlets.city');
        }
        if ($request->json('created_at')) {
            $subs->orderBy('subscriptions.created_at', 'DESC');
        }

        $subs = $subs->get()->toArray();

        if (!empty($subs)) {
            $city = "";

            if ($request->json('id_city')) {
                $city = $request->json('id_city');
            }

            $subs = $this->kota($subs, $city, $request->json('admin'));

        }

        if ($request->json('highest_available_subscription')) {
            $tempSubs = [];
            $subsUnlimited = $this->unlimited($subs);

            if (!empty($subsUnlimited)) {
                foreach ($subsUnlimited as $key => $value) {
                    array_push($tempSubs, $subs[$key]);
                }
            }

            $limited = $this->limited($subs);

            if (!empty($limited)) {
                $tempTempSubs = [];
                foreach ($limited as $key => $value) {
                    array_push($tempTempSubs, $subs[$key]);
                }

                $tempTempSubs = $this->highestAvailableVoucher($tempTempSubs);

                // return $tempTempDeals;
                $tempSubs =  array_merge($tempSubs, $tempTempSubs);
            }

            $subs = $tempSubs;
        }

        if ($request->json('lowest_available_subscription')) {
            $tempSubs = [];

            $limited = $this->limited($subs);

            if (!empty($limited)) {
                foreach ($limited as $key => $value) {
                    array_push($tempSubs, $subs[$key]);
                }

                $tempSubs = $this->lowestAvailableVoucher($tempSubs);
            }

            $subsUnlimited = $this->unlimited($subs);

            if (!empty($subsUnlimited)) {
                foreach ($subsUnlimited as $key => $value) {
                    array_push($tempSubs, $subs[$key]);
                }
            }

            $subs = $tempSubs;
        }

        // if subs detail, add webview url & btn text
        if ($request->json('id_subscription') && !empty($subs)) {
            //url webview
            $subs[0]['webview_url'] = config('url.app_url') . "api/webview/subscription/" . $subs[0]['id_subscription'];
            // text tombol beli
            $subs[0]['button_text'] = $subs[0]['subscription_price_type']=='free'?'Ambil':'Tukar';
            $subs[0]['button_status'] = 0;
            //text konfirmasi pembelian
            if($subs[0]['subscription_price_type']=='free'){
                //voucher free
                $payment_message = Setting::where('key', 'payment_messages')->pluck('value_text')->first()??'Kamu yakin ingin membeli subscription ini?';
                $payment_message = MyHelper::simpleReplace($payment_message,['subscription_title'=>$subs[0]['subscription_title']]);
            }elseif($subs[0]['subscription_price_type']=='point'){
                $payment_message = Setting::where('key', 'payment_messages_point')->pluck('value_text')->first()??'Anda akan menukarkan %point% points anda dengan subscription %subscription_title%?';
                $payment_message = MyHelper::simpleReplace($payment_message,['point'=>$subs[0]['subscription_price_point'],'subscription_title'=>$subs[0]['subscription_title']]);
            }else{
                $payment_message = Setting::where('key', 'payment_messages')->pluck('value_text')->first()??'Kamu yakin ingin membeli subscription %subscription_title%?';
                $payment_message = MyHelper::simpleReplace($payment_message,['subscription_title'=>$subs[0]['subscription_title']]);
            }

            $payment_success_message = Setting::where('key', 'payment_success_messages')->pluck('value_text')->first()??'Anda telah membeli subscription %subscription_title%';
            $payment_success_message = MyHelper::simpleReplace($payment_success_message,['subscription_title'=>$subs[0]['subscription_title']]);


            $subs[0]['payment_message'] = $payment_message??'';
            $subs[0]['payment_success_message'] = $payment_success_message;

            if($subs[0]['subscription_price_type']=='free'&&$subs[0]['subscription_status']=='available'){
                $subs[0]['button_status']=1;
            }else {
                if($subs[0]['subscription_price_type']=='point'){
                    $subs[0]['button_status']=$subs[0]['subscription_price_point']<=$curBalance?1:0;
                    if($subs[0]['subscription_price_point']>$curBalance){
                        $subs[0]['payment_fail_message'] = Setting::where('key', 'payment_fail_messages')->pluck('value_text')->first()??'Mohon maaf, point anda tidak cukup';
                    }
                }else{
                    $subs[0]['button_text'] = 'Beli';
                    if($subs[0]['subscription_status']=='available'){
                        $subs[0]['button_status'] = 1;
                    }
                }
            }
        }

        //jika mobile di pagination
        if (!$request->json('web')) {
            //pagination
            if ($request->get('page')) {
                $page = $request->get('page');
            } else {
                $page = 1;
            }

            $resultData = [];
            $listData   = [];
            $paginate   = 10;
            $start      = $paginate * ($page - 1);
            $all        = $paginate * $page;
            $end        = $all;
            $next       = true;

            if ($all > count($subs)) {
                $end = count($subs);
                $next = false;
            }

            for ($i=$start; $i < $end; $i++) {
                $subs[$i]['time_to_end']=strtotime($subs[$i]['subscription_end'])-time();

                $list[$i]['id_subscription'] = $subs[$i]['id_subscription'];
                $list[$i]['url_subscription_image'] = $subs[$i]['url_subscription_image'];
                $list[$i]['time_to_end'] = $subs[$i]['time_to_end'];
                $list[$i]['subscription_start'] = $subs[$i]['subscription_start'];
                $list[$i]['subscription_publish_start'] = $subs[$i]['subscription_publish_start'];
                $list[$i]['subscription_end'] = $subs[$i]['subscription_end'];
                $list[$i]['subscription_publish_end'] = $subs[$i]['subscription_publish_end'];
                $list[$i]['subscription_price_cash'] = $subs[$i]['subscription_price_cash'];
                $list[$i]['subscription_price_point'] = $subs[$i]['subscription_price_point'];
                $list[$i]['subscription_price_type'] = $subs[$i]['subscription_price_type'];
                $list[$i]['time_server'] = date('Y-m-d H:i:s');
                array_push($resultData, $subs[$i]);
                array_push($listData, $list[$i]);
            }

            $result['current_page']  = $page;
            if (!$request->json('id_subscription')) {
                
                $result['data']          = $listData;
            }else{

                $result['data']          = $resultData;
            }
            $result['total']         = count($resultData);
            $result['next_page_url'] = null;
            if ($next == true) {
                $next_page = (int) $page + 1;
                $result['next_page_url'] = ENV('APP_API_URL') . 'api/subscription/list?page=' . $next_page;
            }

            // print_r($deals); exit();
            if(!$result['total']){
                $result=[];
            }

            if(
                $request->json('voucher_type_point') ||
                $request->json('voucher_type_paid') ||
                $request->json('voucher_type_free') ||
                $request->json('id_city') ||
                $request->json('key_free')
            ){
                $resultMessage = 'Maaf, voucher yang kamu cari belum tersedia';
            }else{
                $resultMessage = 'Nantikan penawaran menarik dari kami';
            }
            return response()->json(MyHelper::checkGet($result, $resultMessage));

        }else{
            return response()->json(MyHelper::checkGet($subs));
        }
    }
     public function listSubscriptionActive(ListSubscription $request)
    {
        $post = $request->json()->all(); 
        $subs = Subscription::join('subscription_outlets', 'subscription_outlets.id_subscription', 'subscriptions.id_subscription')
                 ->join('outlets','outlets.id_outlet','subscription_outlets.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where('subscriptions.subscription_start','<', date('Y-m-d H:i:s'))
                 ->where('subscriptions.subscription_end','>', date('Y-m-d H:i:s'))
                 ->where(array('partners.id_partner'=>$request->id_partner));
        $user = $request->user();
        $curBalance = (int) $user->balance??0;

        if ($request->json('forSelect2')) {
            return MyHelper::checkGet($subs->with(['outlets', 'users'])->whereDoesntHave('featured_subscriptions')->get());
        }

        if ($request->json('id_outlet') && is_integer($request->json('id_outlet'))) {
            $subs = $subs->where('outlets.id_outlet', $request->json('id_outlet'))
                        ->addSelect('subscriptions.*')->distinct();
        }
        
        if ( empty($request->json('admin')) ) {
            $subs = $subs->whereNotNull('subscriptions.subscription_step_complete');
        }

        if ( $request->json('with_brand') ) {
            $subs = $subs->with(['brand', 'brands']);
        }

        if ($request->json('id_subscription')) {
            // add content for detail subscription
            $subs = $subs->where('subscriptions.id_subscription', '=', $request->json('id_subscription'))
                        ->with([
                            'outlets.city',
                            'subscription_content' => function($q) {
                                $q->orderBy('order')
                                    ->where('is_active', '=', 1)
                                    ->addSelect(
                                        'id_subscription', 
                                        'id_subscription_content', 
                                        'title',
                                        'order'
                                    );
                            },
                            'subscription_content.subscription_content_details' => function($q) {
                                $q->orderBy('order')
                                    ->addSelect(
                                        'id_subscription_content_detail',
                                        'id_subscription_content',
                                        'content',
                                        'order'
                                    );
                            }
                        ]);
        }

        if ($request->json('publish')) {
            $subs = $subs->where('subscriptions.subscription_publish_end', '>=', date('Y-m-d H:i:s'));
        }

        if ($request->json('subscription_type')) {
            $subs = $subs->where('subscriptions.subscription_type', '=', $request->json('subscription_type'));
        }

        if ($request->json('key_free')) {
            $subs = $subs->where(function($query) use ($request){
                $query->where('subscriptions.subscription_title', 'LIKE', '%' . $request->json('key_free') . '%')
                    ->orWhere('subscriptions.subscription_sub_title', 'LIKE', '%' . $request->json('key_free') . '%');
            });
        }

        $subs->where(function ($query) use ($request) {

            // Cash
            if ($request->json('subscription_type_paid')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('subscriptions.subscription_price_cash');
                    if(is_numeric($val=$request->json('price_range_start'))){
                        $amp->where('subscriptions.subscription_price_cash','>=',$val);
                    }
                    if(is_numeric($val=$request->json('price_range_end'))){
                        $amp->where('subscriptions.subscription_price_cash','<=',$val);
                    }
                });
            }

            // Point
            if ($request->json('subscription_type_point')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('subscriptions.subscription_price_point');
                    if(is_numeric($val=$request->json('point_range_start'))){
                        $amp->where('subscriptions.subscription_price_point','>=',$val);
                    }
                    if(is_numeric($val=$request->json('point_range_end'))){
                        $amp->where('subscriptions.subscription_price_point','<=',$val);
                    }
                });
            }

            // Free
            if ($request->json('subscription_type_free')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNull('subscriptions.subscription_price_point')->whereNull('subscriptions.subscription_price_cash');
                });
            }
        });

        if ($request->json('lowest_point')) {
            $subs->orderBy('subscriptions.subscription_price_point', 'ASC');
        }
        if ($request->json('highest_point')) {
            $subs->orderBy('subscriptions.subscription_price_point', 'DESC');
        }

        if ($request->json('alphabetical')) {
            $subs->orderBy('subscriptions.subscription_title', 'ASC');

        } else if ($request->json('alphabetical-desc')) {
            $subs->orderBy('subscriptions.subscription_title', 'DESC');

        } else if ($request->json('newest')) {
            $subs->orderBy('subscriptions.subscription_publish_start', 'DESC');

        } else if ($request->json('oldest')) {
            $subs->orderBy('subscriptions.subscription_publish_start', 'ASC');

        } else {
            $subs->orderBy('subscriptions.subscription_end', 'ASC');
        }
        if ($request->json('id_city')) {
            $subs->with('outlets','outlets.city');
        }
        if ($request->json('created_at')) {
            $subs->orderBy('subscriptions.created_at', 'DESC');
        }

        $subs = $subs->get()->toArray();

        if (!empty($subs)) {
            $city = "";

            if ($request->json('id_city')) {
                $city = $request->json('id_city');
            }

            $subs = $this->kota($subs, $city, $request->json('admin'));

        }

        if ($request->json('highest_available_subscription')) {
            $tempSubs = [];
            $subsUnlimited = $this->unlimited($subs);

            if (!empty($subsUnlimited)) {
                foreach ($subsUnlimited as $key => $value) {
                    array_push($tempSubs, $subs[$key]);
                }
            }

            $limited = $this->limited($subs);

            if (!empty($limited)) {
                $tempTempSubs = [];
                foreach ($limited as $key => $value) {
                    array_push($tempTempSubs, $subs[$key]);
                }

                $tempTempSubs = $this->highestAvailableVoucher($tempTempSubs);

                // return $tempTempDeals;
                $tempSubs =  array_merge($tempSubs, $tempTempSubs);
            }

            $subs = $tempSubs;
        }

        if ($request->json('lowest_available_subscription')) {
            $tempSubs = [];

            $limited = $this->limited($subs);

            if (!empty($limited)) {
                foreach ($limited as $key => $value) {
                    array_push($tempSubs, $subs[$key]);
                }

                $tempSubs = $this->lowestAvailableVoucher($tempSubs);
            }

            $subsUnlimited = $this->unlimited($subs);

            if (!empty($subsUnlimited)) {
                foreach ($subsUnlimited as $key => $value) {
                    array_push($tempSubs, $subs[$key]);
                }
            }

            $subs = $tempSubs;
        }

        // if subs detail, add webview url & btn text
        if ($request->json('id_subscription') && !empty($subs)) {
            //url webview
            $subs[0]['webview_url'] = config('url.app_url') . "api/webview/subscription/" . $subs[0]['id_subscription'];
            // text tombol beli
            $subs[0]['button_text'] = $subs[0]['subscription_price_type']=='free'?'Ambil':'Tukar';
            $subs[0]['button_status'] = 0;
            //text konfirmasi pembelian
            if($subs[0]['subscription_price_type']=='free'){
                //voucher free
                $payment_message = Setting::where('key', 'payment_messages')->pluck('value_text')->first()??'Kamu yakin ingin membeli subscription ini?';
                $payment_message = MyHelper::simpleReplace($payment_message,['subscription_title'=>$subs[0]['subscription_title']]);
            }elseif($subs[0]['subscription_price_type']=='point'){
                $payment_message = Setting::where('key', 'payment_messages_point')->pluck('value_text')->first()??'Anda akan menukarkan %point% points anda dengan subscription %subscription_title%?';
                $payment_message = MyHelper::simpleReplace($payment_message,['point'=>$subs[0]['subscription_price_point'],'subscription_title'=>$subs[0]['subscription_title']]);
            }else{
                $payment_message = Setting::where('key', 'payment_messages')->pluck('value_text')->first()??'Kamu yakin ingin membeli subscription %subscription_title%?';
                $payment_message = MyHelper::simpleReplace($payment_message,['subscription_title'=>$subs[0]['subscription_title']]);
            }

            $payment_success_message = Setting::where('key', 'payment_success_messages')->pluck('value_text')->first()??'Anda telah membeli subscription %subscription_title%';
            $payment_success_message = MyHelper::simpleReplace($payment_success_message,['subscription_title'=>$subs[0]['subscription_title']]);


            $subs[0]['payment_message'] = $payment_message??'';
            $subs[0]['payment_success_message'] = $payment_success_message;

            if($subs[0]['subscription_price_type']=='free'&&$subs[0]['subscription_status']=='available'){
                $subs[0]['button_status']=1;
            }else {
                if($subs[0]['subscription_price_type']=='point'){
                    $subs[0]['button_status']=$subs[0]['subscription_price_point']<=$curBalance?1:0;
                    if($subs[0]['subscription_price_point']>$curBalance){
                        $subs[0]['payment_fail_message'] = Setting::where('key', 'payment_fail_messages')->pluck('value_text')->first()??'Mohon maaf, point anda tidak cukup';
                    }
                }else{
                    $subs[0]['button_text'] = 'Beli';
                    if($subs[0]['subscription_status']=='available'){
                        $subs[0]['button_status'] = 1;
                    }
                }
            }
        }

        //jika mobile di pagination
        if (!$request->json('web')) {
            //pagination
            if ($request->get('page')) {
                $page = $request->get('page');
            } else {
                $page = 1;
            }

            $resultData = [];
            $listData   = [];
            $paginate   = 10;
            $start      = $paginate * ($page - 1);
            $all        = $paginate * $page;
            $end        = $all;
            $next       = true;

            if ($all > count($subs)) {
                $end = count($subs);
                $next = false;
            }

            for ($i=$start; $i < $end; $i++) {
                $subs[$i]['time_to_end']=strtotime($subs[$i]['subscription_end'])-time();

                $list[$i]['id_subscription'] = $subs[$i]['id_subscription'];
                $list[$i]['url_subscription_image'] = $subs[$i]['url_subscription_image'];
                $list[$i]['time_to_end'] = $subs[$i]['time_to_end'];
                $list[$i]['subscription_start'] = $subs[$i]['subscription_start'];
                $list[$i]['subscription_publish_start'] = $subs[$i]['subscription_publish_start'];
                $list[$i]['subscription_end'] = $subs[$i]['subscription_end'];
                $list[$i]['subscription_publish_end'] = $subs[$i]['subscription_publish_end'];
                $list[$i]['subscription_price_cash'] = $subs[$i]['subscription_price_cash'];
                $list[$i]['subscription_price_point'] = $subs[$i]['subscription_price_point'];
                $list[$i]['subscription_price_type'] = $subs[$i]['subscription_price_type'];
                $list[$i]['time_server'] = date('Y-m-d H:i:s');
                array_push($resultData, $subs[$i]);
                array_push($listData, $list[$i]);
            }

            $result['current_page']  = $page;
            if (!$request->json('id_subscription')) {
                
                $result['data']          = $listData;
            }else{

                $result['data']          = $resultData;
            }
            $result['total']         = count($resultData);
            $result['next_page_url'] = null;
            if ($next == true) {
                $next_page = (int) $page + 1;
                $result['next_page_url'] = ENV('APP_API_URL') . 'api/subscription/list?page=' . $next_page;
            }

            // print_r($deals); exit();
            if(!$result['total']){
                $result=[];
            }

            if(
                $request->json('voucher_type_point') ||
                $request->json('voucher_type_paid') ||
                $request->json('voucher_type_free') ||
                $request->json('id_city') ||
                $request->json('key_free')
            ){
                $resultMessage = 'Maaf, voucher yang kamu cari belum tersedia';
            }else{
                $resultMessage = 'Nantikan penawaran menarik dari kami';
            }
            return response()->json(MyHelper::checkGet($result, $resultMessage));

        }else{
            return response()->json(MyHelper::checkGet($subs));
        }
    }
    public function kota($subs, $city = "", $admin=false)
    {
        $timeNow = date('Y-m-d H:i:s');

        foreach ($subs as $key => $value) {
            $markerCity = 0;

            $subs[$key]['outlet_by_city'] = [];

            // set time
            $subs[$key]['time_server'] = $timeNow;

            if (!empty($value['outlets'])) {
                // ambil kotanya dulu
        // return $value['outlets'];
                $kota = array_column($value['outlets'], 'city');
                $kota = array_values(array_map("unserialize", array_unique(array_map("serialize", $kota))));
        // return [$kota];

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

                $subs[$key]['outlet_by_city'] = $kota;
            }

            // unset($subs[$key]['outlets']);
            // jika ada pencarian kota
            if (!empty($city)) {
                if ($markerCity == 0) {
                    unset($subs[$key]);
                    continue;
                }
            }

            $calc = $value['subscription_total'] - $value['subscription_bought'];

            if ( empty($value['subscription_total']) ) {
                $calc = '*';
            }

            if(is_numeric($calc)){
                if($calc||$admin){
                    $subs[$key]['percent_subscription'] = $calc*100/$value['subscription_total'];
                }else{
                    unset($subs[$key]);
                    continue;
                }
            }else{
                $subs[$key]['percent_voucher'] = 100;
            }
            $subs[$key]['available_subscription'] = (string) $calc;
            // subs masih ada?
            // print_r($subs[$key]['available_voucher']);
        }

        // print_r($subs); exit();
        $subs = array_values($subs);

        return $subs;
    }
}
