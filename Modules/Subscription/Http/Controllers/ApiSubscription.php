<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;

use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\FeaturedSubscription;
use Modules\Subscription\Entities\SubscriptionOutlet;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use App\Http\Models\Setting;

use Modules\Subscription\Http\Requests\ListSubscription;

class ApiSubscription extends Controller
{

    function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->user     = "Modules\Users\Http\Controllers\ApiUser";
    }

    public $saveImage = "img/subscription/";

    public function listSubscription(ListSubscription $request)
    {
        $post = $request->json()->all(); 
        $subs = (new Subscription)->newQuery();
        $user = $request->user();
        $curBalance = (int) $user->balance??0;

        // return $post;
        if ($request->json('forSelect2')) {
            return MyHelper::checkGet($subs->with(['outlets', 'users'])->whereDoesntHave('featured_subscriptions')->get());
        }

        if ($request->json('id_outlet') && is_integer($request->json('id_outlet'))) {
            $subs = $subs->join('subscription_outlets', 'subscriptions.id_subscription', '=', 'subscription_outlets.id_subscription')
                        ->where('id_outlet', $request->json('id_outlet'))
                        ->addSelect('subscriptions.*')->distinct();
        }

        if ($request->json('id_subscription')) {
            $subs = $subs->where('id_subscription', '=', $request->json('id_subscription'))
                        ->with('outlets', 'users');
        }

        if ($request->json('publish')) {
            $subs = $subs->where('subscription_publish_end', '>=', date('Y-m-d H:i:s'));
        }

        if ($request->json('key_free')) {
            $subs = $subs->where(function($query) use ($request){
                $query->where('subscription_title', 'LIKE', '%' . $request->json('key_free') . '%')
                    ->orWhere('subscription_sub_title', 'LIKE', '%' . $request->json('key_free') . '%');
            });
        }

        $subs->where(function ($query) use ($request) {

            // Cash
            if ($request->json('subscription_type_paid')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('subscription_price_cash');
                    if(is_numeric($val=$request->json('price_range_start'))){
                        $amp->where('subscription_price_cash','>=',$val);
                    }
                    if(is_numeric($val=$request->json('price_range_end'))){
                        $amp->where('subscription_price_cash','<=',$val);
                    }
                });
            }

            // Point
            if ($request->json('subscription_type_point')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNotNull('subscription_price_point');
                    if(is_numeric($val=$request->json('point_range_start'))){
                        $amp->where('subscription_price_point','>=',$val);
                    }
                    if(is_numeric($val=$request->json('point_range_end'))){
                        $amp->where('subscription_price_point','<=',$val);
                    }
                });
            }

            // Free
            if ($request->json('subscription_type_free')) {
                $query->orWhere(function ($amp) use ($request) {
                    $amp->whereNull('subscription_price_point')->whereNull('subscription_price_cash');
                });
            }
        });

        if ($request->json('lowest_point')) {
            $subs->orderBy('subscription_price_point', 'ASC');
        }
        if ($request->json('highest_point')) {
            $subs->orderBy('subscription_price_point', 'DESC');
        }

        if ($request->json('alphabetical')) {
            $subs->orderBy('subscription_title', 'ASC');

        } else if ($request->json('alphabetical-desc')) {
            $subs->orderBy('subscription_title', 'DESC');

        } else if ($request->json('newest')) {
            $subs->orderBy('subscription_publish_start', 'DESC');

        } else if ($request->json('oldest')) {
            $subs->orderBy('subscription_publish_start', 'ASC');

        } else {
            $subs->orderBy('subscription_end', 'ASC');
        }
        if ($request->json('id_city')) {
            $subs->with('outlets','outlets.city');
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
            $subs[0]['webview_url'] = env('APP_URL') . "api/webview/subscription/" . $subs[0]['id_subscription'];
            // text tombol beli
            $subs[0]['button_text'] = $subs[0]['subscription_price_type']=='free'?'Ambil':'Tukar';
            $subs[0]['button_status'] = 0;
            //text konfirmasi pembelian
            if($subs[0]['subscription_price_type']=='free'){
                //voucher free
                $payment_message = Setting::where('key', 'payment_messages')->pluck('value_text')->first()??'Kamu yakin ingin mengambil subscription ini?';
                $payment_message = MyHelper::simpleReplace($payment_message,['subscription_title'=>$subs[0]['subscription_title']]);
            }elseif($subs[0]['subscription_price_type']=='point'){
                $payment_message = Setting::where('key', 'payment_messages_point')->pluck('value_text')->first()??'Anda akan menukarkan %point% points anda dengan Subscription %subscription_title%?';
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
                $list[$i]['subscription_end'] = $subs[$i]['subscription_end'];
                $list[$i]['subscription_publish_end'] = $subs[$i]['subscription_publish_end'];
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

    function unlimited($subs)
    {
        $unlimited = array_filter(array_column($subs, "available_subscription"), function ($subs) {
            if ($subs == "*") {
                return $subs;
            }
        });

        return $unlimited;
    }

    function limited($subs)
    {
        $limited = array_filter(array_column($subs, "available_subscription"), function ($subs) {
            if ($subs != "*") {
                return $subs;
            }
        });

        return $limited;
    }

    /* SORT DEALS */
    function highestAvailableVoucher($subs)
    {
        usort($subs, function ($a, $b) {
            return $a['available_subscription'] < $b['available_subscription'];
        });

        return $subs;
    }

    function lowestAvailableVoucher($subs)
    {
        usort($subs, function ($a, $b) {
            return $a['available_subscription'] > $b['available_subscription'];
        });

        return $subs;
    }

    /* INI LIST KOTA */
    public function kota($deals, $city = "", $admin=false)
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

            $calc = $value['subscription_total'] - $value['subscription_bought'];

            if ( empty($value['subscription_total']) ) {
                $calc = '*';
            }

            if(is_numeric($calc)){
                if($calc||$admin){
                    $deals[$key]['percent_subscription'] = $calc*100/$value['subscription_total'];
                }else{
                    unset($deals[$key]);
                    continue;
                }
            }else{
                $deals[$key]['percent_voucher'] = 100;
            }
            $deals[$key]['available_subscription'] = (string) $calc;
            // deals masih ada?
            // print_r($deals[$key]['available_voucher']);
        }

        // print_r($deals); exit();
        $deals = array_values($deals);

        return $deals;
    }

    public function mySubscription(Request $request)
    {
        $post = $request->json()->all();
        $user = $request->user();

        $subs = SubscriptionUser::
                leftjoin('subscriptions', 'subscription_users.id_subscription', '=', 'subscriptions.id_subscription')
                ->where('id_user', $user['id'])
                ->where('subscription_users.subscription_expired_at', '>=',date('Y-m-d H:i:s'))
                ->whereIn('paid_status', ['Completed','Free'])
                ->withCount(['subscription_user_vouchers' => function($q){
                    $q->whereNotNull('used_at');
                }]);
                // ->where('subscription_user_vouchers_count', '<', 'subscriptions.subscription_voucher_total');
                // ->get();
                // return date('Y-m-d H:i:s');
        if ( isset($post['id_subscription_user']) ) {
            $subs = $subs->leftjoin('subscription_user_vouchers', 'subscription_users.id_subscription_user', '=', 'subscription_user_vouchers.id_subscription_user')
                         ->where('subscription_users.id_subscription_user', '=', $post['id_subscription_user'])
                         ->addselect('subscriptions.*', 'subscription_users.*')
                         ->first();
            if ($subs) {
                $subs['time_to_end'] = strtotime($subs['subscription_end'])-time();
                $subs['url_webview'] = env('APP_API_URL') ."api/webview/mysubscription/". $subs['id_subscription_user'];
            }
            $data = $subs;
        }
        else{
            $subs = $subs->addSelect(
                            'subscriptions.id_subscription',
                            'subscription_users.id_subscription_user',
                            'subscription_image',
                            'subscription_end',
                            'subscription_publish_end',
                            'subscription_voucher_total'
                        )
                        ->get();
            $data = [];
            if($subs){
                foreach ($subs as $key => $sub) {
                    //check voucher total
                    if ($sub['subscription_user_vouchers_count'] < $sub['subscription_voucher_total']) {

                        $data[$key]['id_subscription']              = $sub['id_subscription'];
                        $data[$key]['id_subscription_user']         = $sub['id_subscription_user'];
                        $data[$key]['subscription_end']             = $sub['subscription_end'];
                        $data[$key]['subscription_publish_end']     = $sub['subscription_publish_end'];
                        $data[$key]['subscription_voucher_total']   = $sub['subscription_voucher_total'];
                        $data[$key]['used_voucher']                 = $sub['subscription_user_vouchers_count'];
                        if (empty($sub['subscription_image'])) {
                            $data[$key]['url_subscription_image'] = env('S3_URL_API').'img/default.jpg';
                        }
                        else {
                            $data[$key]['url_subscription_image'] = env('S3_URL_API').$sub['subscription_image'];
                        }

                        $data[$key]['time_to_end']                  = strtotime($sub['subscription_end'])-time();
                        $data[$key]['url_webview']                  = env('APP_API_URL') ."api/webview/mysubscription/". $sub['id_subscription_user'];
                    }
                }
            }
        }
        return response()->json(MyHelper::checkGet($data));
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('subscription::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('subscription::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('subscription::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('subscription::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
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
}
