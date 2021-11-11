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
use DataTables;
class ApiSubscriptionController extends Controller
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
    
     public function listSubscriptionBefore(ListSubscription $request)
    {
        $post = $request->json()->all(); 
        $subs = Subscription::join('subscription_outlets', 'subscription_outlets.id_subscription', 'subscriptions.id_subscription')
                 ->join('subscription_brands','subscription_brands.id_subscription','subscriptions.id_subscription')
                 ->join('brands','brands.id_brand','subscription_brands.id_brand')
                 ->join('brand_outlet','brand_outlet.id_brand','brands.id_brand')
                 ->join('outlets','outlets.id_outlet','subscription_outlets.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_location','outlets.id_location')
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
            $result = $this->dealsPaginate($subs);
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
            $subs = $this->dealsPaginate($subs);
            return response()->json(MyHelper::checkGet($subs));
        }
    }
     public function listSubscriptionActive(ListSubscription $request)
    {
        $post = $request->json()->all(); 
        $subs = Subscription::join('subscription_outlets', 'subscription_outlets.id_subscription', 'subscriptions.id_subscription')
                 ->join('outlets','outlets.id_outlet','subscription_outlets.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_location','outlets.id_location')
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
            $result = $this->dealsPaginate($subs);
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
            $subs = $this->dealsPaginate($subs);
            return response()->json(MyHelper::checkGet($subs));
        }
    }
    
     function dealsPaginate($query)
	{
		
		$query = DataTables::of($query)
                     
                ->editColumn('subscription_price', function($row) {
                    if($row['subscription_price_point"']??0){
                        return $row['subscription_price_point'].' Points';
                    } elseif (!empty($row['subscription_price_cash']??0)){
                        return 'IDR '.'IDR '.$value['subscription_price_cash']['subscription_price_cash'];
                    }else{
                        return 'Free';
                    }
                })
                ->editColumn('subscription_publish_start', function($row) {
                  $publish_start = date('d M Y', strtotime($row['subscription_publish_start'])).' - '.date('d M Y', strtotime($row['subscription_publish_start']));
                  return $publish_start;
                })
                ->editColumn('subscription_start', function($row) {
                  $publish_start = date('d M Y', strtotime($row['subscription_start'])).' - '.date('d M Y', strtotime($row['subscription_end']));
                  return $publish_start;
                })
                ->make(true);
                
		return $query;
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
 