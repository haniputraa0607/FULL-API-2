<?php

namespace Modules\Deals\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Lib\MyHelper;

use App\Http\Models\Deal;
use App\Http\Models\DealsOutlet;
use App\Http\Models\DealsPaymentManual;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsVoucher;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\Setting;
use App\Http\Models\TransactionVoucher;
use App\Http\Models\User;
use App\Http\Models\UserAddress;
use Modules\Deals\Http\Requests\Deals\Voucher;
use Modules\Deals\Http\Requests\Deals\UseVoucher;
use Modules\Deals\Http\Requests\Deals\MyVoucherStatus;
use Modules\ProductService\Entities\ProductHairstylistCategory;
use Modules\Product\Entities\ProductDetail;

use DB;
use Modules\Brand\Entities\Brand;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use App\Lib\TemporaryDataManager;
use Modules\Deals\Entities\DealsPaymentMethod;

class ApiDealsVoucher extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->deals        = "Modules\Deals\Http\Controllers\ApiDeals";
        $this->subscription        	= "Modules\Subscription\Http\Controllers\ApiSubscription";
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->online_transaction       = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->home_service = "Modules\Transaction\Http\Controllers\ApiTransactionHomeService";
        $this->shop = "Modules\Transaction\Http\Controllers\ApiTransactionShop";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->promo_trx = "Modules\Transaction\Http\Controllers\ApiPromoTransaction";
    }

    /* CREATE VOUCHER */
    function create($post) {
        DB::beginTransaction();
        if (is_array($post['voucher_code'])) {
            $data = [];
            $post['voucher_code'] = array_flip($post['voucher_code']);
            $post['voucher_code'] = array_flip($post['voucher_code']);
            foreach ($post['voucher_code'] as $value) {
                array_push($data, [
                    'id_deals'             => $post['id_deals'],
                    'voucher_code'         => strtoupper($value),
                    'deals_voucher_status' => 'Available',
                    'created_at'           => date('Y-m-d H:i:s'),
                    'updated_at'           => date('Y-m-d H:i:s')
                ]);
            }

            if (!empty($data)) {

            	if (($post['add_type']??false) != 'add')
            	{
                	$save = DealsVoucher::where('id_deals',$post['id_deals'])->delete();
            	    $save = DealsVoucher::insert($data);
            	}else{
            		foreach ($data as $key => $value) {
            	    	$save = DealsVoucher::updateOrCreate(['id_deals' =>$post['id_deals'], 'voucher_code' => $value['voucher_code']],$value);
            		}
            	}
                if ($save) {
                    // UPDATE VOUCHER TOTAL DEALS TABLE
                    $updateDealsTable = $this->updateTotalVoucher($post);

                    if ($updateDealsTable) {
                        DB::commit();
                        $save = true;
                    }
                    else {
                        DB::rollback();
                        $save = false;
                    }
                }
            }
            else {
                DB::rollback();
                $save = false;
            }

            return MyHelper::checkUpdate($save);
        }
        else {
            $save = DealsVoucher::create([
                'id_deals'             => $post['id_deals'],
                'voucher_code'         => strtoupper($post['voucher_code']),
                'deals_voucher_status' => 'Available'
            ]);

            return MyHelper::checkCreate($save);
        }
    }

    /* UPDATE TOTAL VOUCHER DEALS TABLE */
    function updateTotalVoucher($post) {
        $jumlahVoucher = DealsVoucher::where('id_deals', $post['id_deals'])->count();

        if ($jumlahVoucher) {
            // UPDATE DATA DEALS

            $save = Deal::where('id_deals', $post['id_deals'])->update([
                'deals_total_voucher' => $jumlahVoucher
            ]);

            if ($save) {
                return true;
            }
        }

        return false;
    }

    /* DELETE VOUCHER */
    function deleteReq(Request $request) {
        if (is_array($request->json('id_deals_voucher'))) {
            $delete = DealsVoucher::whereIn('id_deals_voucher', $request->json('id_deals_voucher'))->where('deals_voucher_status', '=', 'Available')->delete();
        }
        else {
            $delete = DealsVoucher::where('id_deals_voucher', $request->json('id_deals_voucher'))->where('deals_voucher_status', '=', 'Available')->delete();
        }

        if ($request->json('id_deals')) {
            $delete = DealsVoucher::where('id_deals')->where('deals_voucher_status', '=', 'Available')->delete();
        }

        return response()->json(MyHelper::checkDelete($delete));
    }

    /* CREATE VOUCHER REQUEST */
    function createReq(Voucher $request) {

        if ($request->json('type') == "generate") {
            $save = $this->generateVoucher($request->json('id_deals'), $request->json('total'));
            return response()->json(MyHelper::checkUpdate($save));
        }
        else {
            $save = $this->create($request->json()->all());

            return response()->json($save);
        }
    }

    /* GENERATE VOUCHER */
    function generateVoucher($id_deals, $total, $status=0) {
        $data = [];
        // pengecekan database
        $voucherDB = $this->voucherDB($id_deals);

        if ($total > 1) {
            for ($i=0; $i < $total; $i++) {
                // generate code
                $code = $this->generateCode($id_deals);

                // unique code in 1 deals
                while (in_array($code, $voucherDB)) {
                    $code = $this->generateCode($id_deals);
                }

                // push for voucher DB, to get unique code
                array_push($voucherDB, $code);

                // push for save db
                array_push($data, [
                    'id_deals'             => $id_deals,
                    'voucher_code'         => strtoupper($code),
                    'deals_voucher_status' => 'Available',
                    'created_at'           => date('Y-m-d H:i:s'),
                    'updated_at'           => date('Y-m-d H:i:s')
                ]);
            }

            $save = DealsVoucher::insert($data);
        }
        else {
            // generate code
            $code = $this->generateCode($id_deals);

            // unique code in 1 deals
            while (in_array($code, $voucherDB)) {
                $code = $this->generateCode($id_deals);
            }

            $data = [
                'id_deals'             => $id_deals,
                'voucher_code'         => strtoupper($code),
            ];

            if ($status != 0) {
                $data['deals_voucher_status'] = "Sent";
            }
            else {
                $data['deals_voucher_status'] = "Available";
            }

            $save = DealsVoucher::create($data);
        }

        return $save;
    }

    /* CHECK VOUCHER DATABASE */
    function voucherDB($id_deals) {
        $dbVoucher = DealsVoucher::where('id_deals', $id_deals)->get()->toArray();

        if (!empty($dbVoucher)) {
            $dbVoucher = array_pluck($dbVoucher, 'voucher_code');
        }

        return $dbVoucher;
    }

    /* GENERATE CODE */
    function generateCode($id_deals) {
        $code = sprintf('%03d', $id_deals).strtoupper(MyHelper::createrandom(5));

        return $code;
    }

    /* UPDATE VOUCHER */
    function update($id_deals_voucher, $post) {
        $update = DealsVoucher::where('id_deals_voucher', $id_deals_voucher)->update($post);

        return $update;
    }

    /* CREATE VOUCHER USER */
    function createVoucherUser($post) {
        $create = DealsUser::create($post);

        if ($create) {
            $create = DealsUser::with(['userMid', 'dealVoucher'])->where('id_deals_user', $create->id_deals_user)->first();

            // add notif mobile
            $addNotif = MyHelper::addUserNotification($create->id_user,'voucher');
        }

        return $create;
    }

    /* UPDATE VOUCHER USER */
    function updateVoucherUser($id_deals_user, $post) {
        $update = DealsVoucher::where('id_deals_user', $id_deals_user)->update($post);

        return $update;
    }

    /* MY VOUCHER */
    function myVoucher(Request $request) {
        $post = $request->json()->all();
        $outlet_total = Outlet::get()->count();

        $voucher = DealsUser::where('id_user', $request->user()->id)
                            ->whereIn('paid_status', ['Free', 'Completed'])
                            ->with(['dealVoucher', 'dealVoucher.deal', 'dealVoucher.deal.outlets.city', 'dealVoucher.deal.outlets.city']);
        $voucher->select('deals_users.id_deals','voucher_expired_at','deals_users.id_deals_voucher','id_deals_user','id_outlet','voucher_hash','redeemed_at','used_at','is_used');
        if (isset($post['id_deals_user'])) {
            $voucher->addselect('deals_users.redeemed_at', 'deals_users.used_at');
            $voucher->where('id_deals_user', $post['id_deals_user']);
        }

        $voucher->where(function ($query) use ($post) {

            if (isset($post['used']) && ($post['used'] == 1 || $post['used'] == '1'))  {
                $query->orWhere(function ($amp) use ($post) {
                        $amp->orWhereNotNull('used_at');
                        $amp->orWhere('voucher_expired_at', '<=', date('Y-m-d H:i:s'));
                    });
            }
            if (isset($post['available']) && ($post['available'] == 1 || $post['available'] == '1')) {
                 $query->orWhere(function ($amp) use ($post) {
                        $amp->whereNull('used_at')->where('voucher_expired_at', '>', date('Y-m-d H:i:s'));
                    });
            }
        });

        if (isset($post['expired_start'])) {
            $voucher->whereDate('voucher_expired_at', '>=',date('Y-m-d', strtotime($post['expired_start'])));
        }

        if (isset($post['expired_end'])) {
            $voucher->whereDate('voucher_expired_at', '<=',date('Y-m-d', strtotime($post['expired_end'])));
        }


         //search by outlet
        if(isset($post['id_outlet']) && is_numeric($post['id_outlet'])){
            $voucher->join('deals_vouchers', 'deals_users.id_deals_voucher', 'deals_vouchers.id_deals_voucher')
                                ->join('deals', 'deals.id_deals', 'deals_vouchers.id_deals')
                                ->join('deals_outlets', 'deals.id_deals', 'deals_outlets.id_deals')
                                ->where(function ($query) use ($post) {
                                    $query->where('deals_users.id_outlet', $post['id_outlet'])
                                            ->orWhere('deals_outlets.id_outlet', $post['id_outlet']);
                                })
                                ->select('deals_users.*')->distinct();


        }

        if(isset($post['key_free']) && $post['key_free'] != null){
            if(!MyHelper::isJoined($voucher,'deals_vouchers')){
                $voucher->leftJoin('deals_vouchers', 'deals_users.id_deals_voucher', 'deals_vouchers.id_deals_voucher');
            }
            if(!MyHelper::isJoined($voucher,'deals')){
                $voucher->leftJoin('deals', 'deals.id_deals', 'deals_vouchers.id_deals');
            }
            $voucher->where(function ($query) use ($post) {
                                    $query->where('deals.deals_title', 'LIKE', '%'.$post['key_free'].'%')
                                            ->orWhere('deals.deals_second_title', 'LIKE', '%'.$post['key_free'].'%');
                                });
            }
         //search by brand
        if(isset($post['id_brand']) && is_numeric($post['id_brand'])){
            if(!MyHelper::isJoined($voucher,'deals_vouchers')){
                $voucher->leftJoin('deals_vouchers', 'deals_users.id_deals_voucher', 'deals_vouchers.id_deals_voucher');
            }
            if(!MyHelper::isJoined($voucher,'deals')){
                $voucher->leftJoin('deals', 'deals.id_deals', 'deals_vouchers.id_deals');
            }
            $voucher->where('deals.id_brand',$post['id_brand']);
        }

        // $voucher->orderBy('voucher_expired_at', 'asc');
        if (isset($post['oldest']) && ($post['oldest'] == 1 || $post['oldest'] == '1')) {
                $voucher = $voucher->orderBy('deals_users.claimed_at', 'asc');
        }
        elseif (isset($post['newest_expired']) && ($post['newest_expired'] == 1 || $post['newest_expired'] == '1')) {
            $voucher = $voucher->orderBy('voucher_expired_at', 'asc');
        }
        else{
            $voucher = $voucher->orderBy('deals_users.claimed_at', 'desc');
        }

        // if voucher detail, no need pagination
        if (isset($post['id_deals_user']) && $post['id_deals_user'] != "") {
            $vcr=$voucher->first();
            if(($post['no_qr']??false)&&!$vcr->used_at){
                $vcr->redeemed_at=null;
                $vcr->save();
            }
            $voucher = $voucher->get()->toArray();

            if (!$voucher) {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Voucher not found']
                ]);
            }
        }
        else {
            if (isset($post['used']) && ($post['used'] == 1 || $post['used'] == '1'))  {
                // if voucher used, return max 100 vouchers with pagination
                $collection = $voucher->take(100)->get();
                $perPage = 10;
                $currentPage = LengthAwarePaginator::resolveCurrentPage();
                if ($currentPage == 1) {
                    $start = 0;
                }
                else {
                    $start = ($currentPage - 1) * $perPage;
                }
                $currentPageCollection = $collection->slice($start, $perPage)->all();

                $paginatedLast100 = new LengthAwarePaginator($currentPageCollection, count($collection), $perPage);

                $paginatedLast100->setPath(LengthAwarePaginator::resolveCurrentPath());
                $voucher = $paginatedLast100;
            }
            else{
                $voucher = $voucher->paginate(10);
            }

            // get pagination attributes
            $current_page = $voucher->currentPage();
            $next_page_url = $voucher->nextPageUrl();
            $per_page = $voucher->perPage();
            $prev_page_url = $voucher->previousPageUrl();
            $total = $voucher->count();

            $voucher_temp = [];
            // convert paginate collection to array data of vouchers
            foreach ($voucher as $key => $value) {
                $voucher_temp[] = $value->toArray();
            }
            $voucher = $voucher_temp;
        }

        //add outlet name
        foreach($voucher as $index => $datavoucher){
            $check = count($datavoucher['deal_voucher']['deal']['outlets']);
            if ($check == $outlet_total) {
                $voucher[$index]['deal_voucher']['deal']['label_outlet'] = 'All';
            } else {
                $voucher[$index]['deal_voucher']['deal']['label_outlet'] = 'Some';
            }
            if($datavoucher['used_at']){
                $voucher[$index]['label']='Used';
                $voucher[$index]['status_text']="Sudah digunakan pada \n".MyHelper::dateFormatInd($voucher[$index]['used_at'],false);
            }elseif($datavoucher['voucher_expired_at']<date('Y-m-d H:i:s')){
                $voucher[$index]['label']='Expired';
                $voucher[$index]['status_text']="Telah berakhir pada \n".MyHelper::dateFormatInd($voucher[$index]['voucher_expired_at'],false);
            }else{
                $voucher[$index]['label']='Gunakan';
                $voucher[$index]['status_text']="Berlaku hingga \n".MyHelper::dateFormatInd($voucher[$index]['voucher_expired_at'],false);
            }
            $outlet = null;
            if($datavoucher['deal_voucher'] == null){
                unset($voucher[$index]);
            }else{
                // if(count($datavoucher['deal_voucher']['deal']['outlets_active']) <= 1){
                //     unset($voucher[$index]);
                // }else{
                    // $voucher[$index]['deal_voucher']['deal']['outlets'] = $datavoucher['deal_voucher']['deal']['outlets_active'];
                    // unset($voucher[$index]['deal_voucher']['deal']['outlets_active']);
                    $outlet = null;
                    if($datavoucher['id_outlet']){
                        $getOutlet = Outlet::find($datavoucher['id_outlet']);
                        if($getOutlet){
                            $outlet = $getOutlet['outlet_name'];
                        }
                    }

                    $voucher[$index] = array_slice($voucher[$index], 0, 4, true) +
                    array("outlet_name" => $outlet) +
                    array_slice($voucher[$index], 4, count($voucher[$index]) - 1, true) ;

                    // get new voucher code
                    // beetwen "https://chart.googleapis.com/chart?chl="
                    // and "&chs=250x250&cht=qr&chld=H%7C0"
                    // preg_match("/api.qrserver.com\/v1\/create-qr-code\/?size=250x250&data=(.*)&chs=250x250/", $datavoucher['voucher_hash'], $matches);
                    preg_match("/chart.googleapis.com\/chart\?chl=(.*)&chs=250x250/", $datavoucher['voucher_hash'], $matches);

                    // replace voucher_code with code from voucher_hash
                    if (isset($matches[1])) {
                        $voucher[$index]['deal_voucher']['voucher_code'] = $matches[1];
                    }
                    else {
                        $voucherHash = $datavoucher['voucher_hash'];
                        $voucher[$index]['deal_voucher']['voucher_code'] = str_replace("https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=",'',  $voucherHash);
                    }

                // }
                // else{
                //     unset($voucher[$index]);
                //     continue;
                // }

            }

            $voucher = $this->kotacuks($voucher);
        }
        // add webview url & btn text
        /*if (isset($post['used'])) {
            if ($post['used'] == 0) {
                foreach($voucher as $index => $dataVou){
                    $voucher[$index]['webview_url'] = config('url.app_url') ."webview/voucher/". $dataVou['id_deals_user'];
                    $voucher[$index]['button_text'] = 'INVALIDATE';
                }
            }
            elseif ($post['used'] == 1) {
                foreach($voucher as $index => $dataVou){
                    $voucher[$index]['webview_url'] = config('url.app_url') ."webview/voucher/used/". $dataVou['id_deals_user'];
                }
            }
        }*/
        if (!($post['used']??false)) {

                foreach($voucher as $index => $dataVou){
                    $voucher[$index]['webview_url'] = config('url.api_url') ."api/webview/voucher/". $dataVou['id_deals_user'];
                    $voucher[$index]['webview_url_v2'] = config('url.api_url') ."api/webview/voucher/v2/". $dataVou['id_deals_user'];
                    $voucher[$index]['button_text'] = 'Gunakan';
                }

        }

        // if voucher detail, no need pagination
        if (isset($post['id_deals_user']) && $post['id_deals_user'] != "") {
            $voucher[0]['deals_title'] = $voucher[0]['deal_voucher']['deal']['deals_title'];
            $voucher[0]['is_offline'] = $voucher[0]['deal_voucher']['deal']['is_offline'];
            $voucher[0]['is_online'] = $voucher[0]['deal_voucher']['deal']['is_online'];

            if (!empty($voucher[0]['is_online'])) {
            	// get pop up confirmation message
            	$deals_rule = Deal::where('id_deals','=',$voucher[0]['deal_voucher']['id_deals'])
            					->with([
		                            'deals_product_discount.product' => function($q) {
										$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
									},
		                            'deals_tier_discount_product.product' => function($q) {
										$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
									},
		                            'deals_buyxgety_product_requirement.product' => function($q) {
										$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
									},
		                            'deals_product_discount_rules',
		                            'deals_tier_discount_rules',
		                            'deals_buyxgety_rules'
		                        ])
		                        ->first();
            	$product_name = app($this->promo_campaign)->getProduct('deals', $deals_rule)['product']??'';
            	// $desc = app($this->promo_campaign)->getPromoDescription('deals', $deals_rule, $product_name)??'';
            	$deals_title = $deals_rule['deals_title']??'';

	            $popup_message = Setting::where('key','=','coupon_confirmation_pop_up')->first()['value_text']??'';
	            $popup_message = MyHelper::simpleReplace($popup_message,['title'=>$deals_title, 'product' => $product_name]);
	            $voucher[0]['confirm_message'] = $popup_message;
            }

            $result['data'] = $voucher;
        }
        else {
            // add pagination attributes
            // $result['data'] = $voucher;
            $result['data'] = array_map(function($var){

            	if ($var['voucher_expired_at'] < date('Y-m-d H:i:s') || !empty($var['used_at'])) {
            		$var['is_used'] = 0;
            	}
            	
                return [
                    'id_deals'=> $var['deal_voucher']['id_deals']??null,
                    'voucher_expired_at'=> $var['voucher_expired_at'],
                    'id_deals_voucher'=> $var['id_deals_voucher'],
                    'id_deals_user'=> $var['id_deals_user'],
                    'deals_title'=>$var['deal_voucher']['deal']['deals_title']??'',
                    'deals_second_title'=>$var['deal_voucher']['deal']['deals_second_title']??'',
                    'webview_url_v2'=>$var['webview_url_v2']??'',
                    'webview_url'=>$var['webview_url']??'',
                    'url_deals_image'=>$var['deal_voucher']['deal']['url_deals_image'],
                    'status_redeem'=>($var['redeemed_at']??false)?1:0,
                    'label'=>$var['label'],
                    'status_text'=>$var['status_text'],
                    'is_used'=>$var['is_used']
                ];
            },$voucher);
            $result['current_page'] = $current_page;
            $result['next_page_url'] = $next_page_url;
            $result['prev_page_url'] = $prev_page_url;
            $result['per_page'] = $per_page;
            $result['total'] = $total;
            if(!$result['total']){
                $result=[];
            }
        }

    	if (empty($voucher)) {
            $empty_text = Setting::where('key','=','message_myvoucher_empty_header')
                            ->orWhere('key','=','message_myvoucher_empty_content')
                            ->orderBy('id_setting')
                            ->get();
            $resultMessage['header'] =  $empty_text[0]['value']??'Anda belum memiliki Kupon.';
            $resultMessage['content'] =  $empty_text[1]['value']??'Potongan menarik untuk setiap pembelian.';
            return  response()->json([
                    'status'   => 'fail',
                    'messages' => ['My voucher is empty'],
                    'empty'    => $resultMessage
                ]);
    	}

        // if(
        //     $request->json('id_outlet') ||
        //     $request->json('id_brand') ||
        //     $request->json('expired_start') ||
        //     $request->json('expired_end') ||
        //     $request->json('key_free')
        // ){
        //     $resultMessage = 'Voucher yang kamu cari tidak tersedia';
        // }else{
        // }

        return response()->json(app($this->subscription)->checkGet($result, $resultMessage??''));

    }

    function kotacuks($deals)
    {
        $timeNow = date('Y-m-d H:i:s');

        // print_r($deals); exit();

        foreach ($deals as $key => $value) {
            $markerCity = 0;

            $deals[$key]['deal_voucher']['deal']['outlet_by_city'] = [];

            // set time
            $deals[$key]['deal_voucher']['deal']['time_server'] = $timeNow;

            if (!empty($deals[$key]['deal_voucher']['deal']['outlets'])) {
                // ambil kotanya dulu

                // print_r($value['deal_voucher']['deal']); exit();
                $kota = array_column($value['deal_voucher']['deal']['outlets'], 'city');
                $kota = array_values(array_map("unserialize", array_unique(array_map("serialize", $kota))));


                // jika ada pencarian kota
                if (!empty($city)) {
                    $cariKota = array_search($city, array_column($kota, 'id_city'));

                    if (is_integer($cariKota)) {
                        $markerCity = 1;
                    }
                }

                foreach ($kota as $k => $v) {
                    $kota[$k]['outlet'] = [];

                    foreach ($value['deal_voucher']['deal']['outlets'] as $outlet) {
                        if ($outlet['id_city'] != null) {
                            if ($v['id_city'] == $outlet['id_city']) {
                                unset($outlet['pivot']);
                                unset($outlet['city']);

                                array_push($kota[$k]['outlet'], $outlet);
                            }
                        }

                    }
                }

                $deals[$key]['deal_voucher']['deal']['outlet_by_city'] = $kota;
            }

            // unset($deals[$key]['outlets']);
            // jika ada pencarian kota
            if (!empty($city)) {
                if ($markerCity == 0) {
                    unset($deals[$key]);
                }
            }

            // kalkulasi point
            $calc = $value['deal_voucher']['deal']['deals_total_voucher'] - $value['deal_voucher']['deal']['deals_total_claimed'];

            if ($value['deal_voucher']['deal']['deals_voucher_type'] == "Unlimited") {
                $calc = '*';
            }

            $deals[$key]['deal_voucher']['deal']['available_voucher'] = $calc;

            // print_r($deals[$key]['available_voucher']);
        }

        // print_r($deals); exit();
        $deals = array_values($deals);

        return $deals;
    }

    function voucherUser(Request $request){
        $post = $request->json()->all();

        $voucher = DealsUser::join('users', 'deals_users.id_user', 'users.id')->where('phone', $post['phone'])
                                ->with(['deals_voucher.deals', 'outlet'])
                                ->get();

        return response()->json(MyHelper::checkGet($voucher));
    }

    public function useVoucher($id_deals_user, $use_later=null)
    {
    	$user = auth()->user();

		DB::beginTransaction();
		// change is used flag to 0
		$deals_user = DealsUser::where('id_user','=',$user->id)->where('is_used','=',1)->update(['is_used' => 0]);
		if (empty($use_later)) {
			// change specific deals user is used to 1
			$deals_user = DealsUser::where('id_deals_user','=',$id_deals_user)->update(['is_used' => 1]);
		}

		if ($deals_user) {
			DB::commit();
		}else{
			DB::rollback();
		}
		$deals_user = MyHelper::checkUpdate($deals_user);
		$deals_user['webview_url'] = config('url.api_url') ."api/webview/voucher/". $id_deals_user;
		$deals_user['webview_url_v2'] = config('url.api_url') ."api/webview/voucher/v2/". $id_deals_user;
		return $deals_user;

    }

    public function unuseVoucher(Request $request)
    {
    	$post = $request->json()->all();
    	$unuse = $this->useVoucher($post['id_deals_user'], 1);
    	if ($unuse) {
    		return response()->json($unuse);
    	}else{
    		return response()->json([
    			'status' => 'fail',
    			'messages' => 'Failed to update voucher'
    		]);
    	}
    }

    public function returnVoucher($id_transaction)
    {
    	$getVoucher = TransactionVoucher::where('id_transaction','=',$id_transaction)->with('deals_voucher.deals')->first();

    	if ($getVoucher)
    	{
	    	$update = DealsUser::where('id_deals_voucher', '=', $getVoucher['id_deals_voucher'])->update(['used_at' => null]);

	    	if ($update)
	    	{
	    		$update = TransactionVoucher::where('id_deals_voucher', '=', $getVoucher['id_deals_voucher'])->update(['status' => 'failed']);

	    		if ($update)
	    		{
	    			$update = Deal::where('id_deals','=',$getVoucher['deals_voucher']['deals']['id_deals'])->update(['deals_total_used' => $getVoucher['deals_voucher']['deals']['deals_total_used']-1]);

	    			if ($update)
		    		{
		    			return true;
		    		}
		    		else
		    		{
		    			return false;
		    		}
	    		}
	    		else
	    		{
	    			return false;
	    		}
	    	}
	    	else
	    	{
	    		return false;
	    	}
    	}

    	return true;

    }

    public function checkStatus(MyVoucherStatus $request)
    {
    	$post = $request->json()->all();
    	$getData = DealsUser::where('id_deals_user', '=', $post['id_deals_user'])->first();

		if (!$getData) {
			return response()->json(['status' => 'fail']);
		}
    	$result['payment_status'] = $getData['paid_status']??'';
    	if ($result['payment_status'] == 'Free') {
    		$result['payment_status'] = 'Completed';
    	}
    	$result['webview_url'] = config('url.api_url').'api/webview/mydeals/'.$post['id_deals_user'];

		return response()->json(MyHelper::checkGet($result));
    }

    /*============================= Start Filter & Sort V2 ================================*/
    function myVoucherV2(Request $request) {
        $post = $request->json()->all();
        $outlet_total = Outlet::get()->count();

        $voucher = DealsUser::where('id_user', $request->user()->id)
            ->whereIn('paid_status', ['Free', 'Completed'])
            ->whereNull('used_at')
            ->with(['dealVoucher', 'dealVoucher.deal', 'dealVoucher.deal.outlets.city', 'dealVoucher.deal.outlets.city']);
        $voucher->select('deals_users.id_deals','voucher_expired_at','deals_users.id_deals_voucher','id_deals_user','id_outlet','voucher_hash','redeemed_at','used_at','is_used');
        
        //search by outlet
        if(isset($post['id_outlet']) && is_numeric($post['id_outlet'])){
            $voucher->join('deals_vouchers', 'deals_users.id_deals_voucher', 'deals_vouchers.id_deals_voucher')
                ->join('deals', 'deals.id_deals', 'deals_vouchers.id_deals')
                ->leftJoin('deals_outlets', 'deals.id_deals', 'deals_outlets.id_deals')
                ->where(function ($query) use ($post) {
                    $query->where('deals_users.id_outlet', $post['id_outlet'])
                        ->orWhere('deals_outlets.id_outlet', $post['id_outlet'])
                        ->orWhere('deals.is_all_outlet','=',1);
                })
                ->select('deals_users.*')->distinct();
        }

         //search by outlet
        if(isset($post['transaction_from']) && is_string($post['transaction_from'])){
            $service = [
                'outlet-service' => 'Outlet Service',
                'home-service' => 'Home Service',
                'shop' => 'Online Shop',
                'academy' => 'Academy',
            ];
            if(!MyHelper::isJoined($voucher,'deals_vouchers')){
                $voucher->leftJoin('deals_vouchers', 'deals_users.id_deals_voucher', 'deals_vouchers.id_deals_voucher');
            }
            if(!MyHelper::isJoined($voucher,'deals')){
                $voucher->leftJoin('deals', 'deals.id_deals', 'deals_vouchers.id_deals');
            }
            $voucher->leftJoin('deals_services', 'deals.id_deals', 'deals_services.id_deals')
            ->where('deals_services.service', $service[$post['transaction_from']])
            ->select('deals_users.*')->distinct();
        }

        if(isset($post['voucher_expired']) && $post['voucher_expired'] != null){
            $voucher->whereDate('deals_users.voucher_expired_at', date('Y-m-d', strtotime($post['voucher_expired'])));
        }else{
            $voucher->where('deals_users.voucher_expired_at', '>', date('Y-m-d H:i:s'));
        }

        if(isset($post['key_free']) && $post['key_free'] != null){
            if(!MyHelper::isJoined($voucher,'deals_vouchers')){
                $voucher->leftJoin('deals_vouchers', 'deals_users.id_deals_voucher', 'deals_vouchers.id_deals_voucher');
            }
            if(!MyHelper::isJoined($voucher,'deals')){
                $voucher->leftJoin('deals', 'deals.id_deals', 'deals_vouchers.id_deals');
            }
            $voucher->where(function ($query) use ($post) {
                $query->where('deals.deals_title', 'LIKE', '%'.$post['key_free'].'%')
                    ->orWhere('deals.deals_second_title', 'LIKE', '%'.$post['key_free'].'%');
            });
        }

        //search by brand
        if(isset($post['id_brand']) && is_numeric($post['id_brand'])){
            if(!MyHelper::isJoined($voucher,'deals_vouchers')){
                $voucher->leftJoin('deals_vouchers', 'deals_users.id_deals_voucher', 'deals_vouchers.id_deals_voucher');
            }
            if(!MyHelper::isJoined($voucher,'deals')){
                $voucher->leftJoin('deals', 'deals.id_deals', 'deals_vouchers.id_deals');
            }
            $voucher->where('deals.id_brand',$post['id_brand']);
        }

        if($request->json('sort')){
            if($request->json('sort') == 'old'){
                $voucher->orderBy('deals_users.claimed_at', 'asc');
            }elseif($request->json('sort') == 'new'){
                $voucher->orderBy('deals_users.claimed_at', 'desc');
            }
        }else{
            $voucher = $voucher->orderBy('deals_users.claimed_at', 'desc');
        }

        $voucher = $voucher->get()->toArray();

        $id_vouchers = [];

        foreach($voucher ?? [] as $val_vou){
            if(!in_array($val_vou['deal_voucher']['id_deals'],$id_vouchers)){
                $id_vouchers[] = $val_vou['deal_voucher']['id_deals'];
            }
        }
        
        if ($request->json('from') && $request->json('from')=='checkout') {
            $deals_no_claim = (new Deal)->newQuery();
            $deals_no_claim->where('deals_type', '!=','WelcomeVoucher');
            $deals_no_claim->where( function($dc) {
                $dc->where('deals_publish_start', '<=', date('Y-m-d H:i:s'))
                ->where('deals_publish_end', '>=', date('Y-m-d H:i:s'))
                ->where('deals_end', '>=', date('Y-m-d H:i:s'));
            });
            $deals_no_claim->where( function($dc) {
                $dc->where('deals_voucher_type','Unlimited')
                    ->orWhereRaw('(deals.deals_total_voucher - deals.deals_total_claimed) > 0 ');
            });
            $deals_no_claim->where('step_complete', '=', 1);
            $deals_no_claim->whereNotIn('deals.id_deals', $id_vouchers);
            $deals_no_claim->with(['outlets', 'outlets.city']);
        
            if ($request->json('id_outlet') && is_numeric($request->json('id_outlet'))) {
                $deals_no_claim->leftJoin('deals_outlets', 'deals.id_deals', 'deals_outlets.id_deals')
                    ->where(function($query) use ($request){
                        $query->where('id_outlet', $request->json('id_outlet'))
                                ->orWhere('deals.is_all_outlet','=',1);
                    })
                    ->addSelect('deals.*')->distinct();
            }
            if(isset($post['transaction_from']) && is_string($post['transaction_from'])){
                $service = [
                    'outlet-service' => 'Outlet Service',
                    'home-service' => 'Home Service',
                    'shop' => 'Online Shop',
                    'academy' => 'Academy',
                ];
                $deals_no_claim->leftJoin('deals_services', 'deals.id_deals', 'deals_services.id_deals')
                ->where('deals_services.service', $service[$post['transaction_from']])
                ->select('deals.*')->distinct();
            }
            $deals_no_claim = $deals_no_claim->get()->toArray();
        }
        //add outlet name
        $datavoucher = [];
        foreach($voucher ?? [] as $index => $datavoucher){
            $check = count($datavoucher['deal_voucher']['deal']['outlets']);
            if ($check == $outlet_total) {
                $voucher[$index]['deal_voucher']['deal']['label_outlet'] = 'All';
            } else {
                $voucher[$index]['deal_voucher']['deal']['label_outlet'] = 'Some';
            }
            if($datavoucher['used_at']){
                $voucher[$index]['label']='Used';
                $voucher[$index]['status_text']="Sudah digunakan pada \n".MyHelper::dateFormatInd($voucher[$index]['used_at'],false);
            }elseif($datavoucher['voucher_expired_at']<date('Y-m-d H:i:s')){
                $voucher[$index]['label']='Expired';
                $voucher[$index]['status_text']="Telah berakhir pada \n".MyHelper::dateFormatInd($voucher[$index]['voucher_expired_at'],false);
            }else{
                $voucher[$index]['label']='Gunakan';
                $voucher[$index]['status_text']="Berlaku hingga \n".MyHelper::dateFormatInd($voucher[$index]['voucher_expired_at'],false);
            }

            $outlet = null;
            if($datavoucher['id_outlet']){
                $getOutlet = Outlet::find($datavoucher['id_outlet']);
                if($getOutlet){
                    $outlet = $getOutlet['outlet_name'];
                }
            }

            $voucher[$index] = array_slice($voucher[$index], 0, 4, true) +
                array("outlet_name" => $outlet) +
                array_slice($voucher[$index], 4, count($voucher[$index]) - 1, true) ;

            preg_match("/chart.googleapis.com\/chart\?chl=(.*)&chs=250x250/", $datavoucher['voucher_hash'], $matches);

            // replace voucher_code with code from voucher_hash
            if (isset($matches[1])) {
                $voucher[$index]['deal_voucher']['voucher_code'] = $matches[1];
            }
            else {
                $voucherHash = $datavoucher['voucher_hash'];
                $voucher[$index]['deal_voucher']['voucher_code'] = str_replace("https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=",'',  $voucherHash);
            }
           

            $voucher = $this->kotacuks($voucher);
        }

        if ($request->json('from') && $request->json('from')=='checkout') {
            foreach($deals_no_claim ?? [] as $index => $deal){
                $check = count($deal['outlets']);
                if ($check == $outlet_total) {
                    $deal[$index]['label_outlet'] = 'All';
                } else {
                    $deal[$index]['label_outlet'] = 'Some';
                }
    
                $outlet = null;
                if( $datavoucher && $datavoucher['deal_voucher'] == null){
                    unset($voucher[$index]);
                }else{
                    $outlet = null;
                    if( $datavoucher && $datavoucher['id_outlet']){
                        $getOutlet = Outlet::find($datavoucher['id_outlet']);
                        if($getOutlet){
                            $outlet = $getOutlet['outlet_name'];
                        }
                    }
                }
            }
        }
        
        if (!($post['used']??false)) {

            foreach($voucher as $index => $dataVou){
                $voucher[$index]['webview_url'] = config('url.api_url') ."api/webview/voucher/". $dataVou['id_deals_user'];
                $voucher[$index]['webview_url_v2'] = config('url.api_url') ."api/webview/voucher/v2/". $dataVou['id_deals_user'];
                $voucher[$index]['button_text'] = 'Gunakan';
            }
        }

        $voucher = array_map(function($var){
            return [
                'id_deals'=> $var['deal_voucher']['id_deals']??null,
                'voucher_expired_at'=> $var['voucher_expired_at'],
                'id_deals_voucher'=> $var['id_deals_voucher'],
                'id_deals_user'=> $var['id_deals_user'],
                'deals_title'=>$var['deal_voucher']['deal']['deals_title']??'',
                'deals_second_title'=>$var['deal_voucher']['deal']['deals_second_title']??'',
                'webview_url_v2'=>$var['webview_url_v2']??'',
                'webview_url'=>$var['webview_url']??'',
                'url_deals_image'=>$var['deal_voucher']['deal']['url_deals_image'],
                'status_redeem'=>($var['redeemed_at']??false)?1:0,
                'label'=>$var['label'],
                'status_text'=>$var['status_text'],
                'is_used'=>$var['is_used'],
                'voucher_expired_at_indo'=> MyHelper::dateFormatInd($var['voucher_expired_at'], false, false),
                'voucher_expired_at_time_indo'=> 'pukul '.date('H:i', strtotime($var['voucher_expired_at']))
            ];
        },$voucher);

        $result['data'] = $voucher ?? [];

        if ($request->json('from') && $request->json('from')=='checkout') {
        
            $deals_no_claim = array_map(function($var){
                return [
                    'id_deals'=> $var['id_deals']??null,
                    'deals_title'=>$var['deals_title']??'',
                    'url_deals_image'=>$var['url_deals_image'],
                ];
            },$deals_no_claim);
    
            $result['data'] = array_merge($voucher,$deals_no_claim);
            
            if($service[$post['transaction_from']] == 'Outlet Service'){

                $outlet = Outlet::where('id_outlet', $post['id_outlet'])->with('today')->where('outlet_status', 'Active')->where('outlets.outlet_service_status', 1)
                ->join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'provinces.id_province', 'cities.id_province')
                ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')
                ->first();
                
                foreach($post['item_service'] ?? [] as $index => $item_service){
                    if($outlet['outlet_different_price']==0){
                        $post['item_service'][$index]['product_price'] = (int)ProductGlobalPrice::where('id_product',$item_service['id_product'])->first()['product_global_price'] ?? $item_service['product_price'];
                    }elseif($outlet['outlet_different_price']==1){
                        $post['item_service'][$index]['product_price'] = (int)ProductSpecialPrice::where('id_product',$item_service['id_product'])->where('id_outlet',$outlet['id_outlet'])->first()['product_special_price'] ?? $item_service['product_price'];
                    }
                }
        
                foreach($post['item'] ?? [] as $index => $item){
                    if($outlet['outlet_different_price']==0){
                        $post['item'][$index]['product_price_raw'] = (int)ProductGlobalPrice::where('id_product',$item['id_product'])->first()['product_global_price'] ?? $item['product_price_raw'];
                        $post['item'][$index]['product_price_total'] = $item['qty'] * $post['item'][$index]['product_price_raw'];
                    }elseif($outlet['outlet_different_price']==1){
                        $post['item'][$index]['product_price_raw'] = (int)ProductSpecialPrice::where('id_product',$item['id_product'])->where('id_outlet',$outlet['id_outlet'])->first()['product_special_price'] ?? $item_service['product_price_raw'];
                        $post['item'][$index]['product_price_total'] = $item['qty'] * $post['item'][$index]['product_price_raw'];
                    }
                }
        
                if (!isset($post['subtotal'])) {
                    $post['subtotal'] = 0;
                }
        
                if (!isset($post['discount'])) {
                    $post['discount'] = 0;
                }
        
                if (!isset($post['service'])) {
                    $post['service'] = 0;
                }
        
                if (!isset($post['tax'])) {
                    $post['tax'] = 0;
                }
        
                if (!isset($post['shipping'])) {
                    $post['shipping'] = 0;
                }
        
                $grandTotal = app($this->setting_trx)->grandTotal();
        
                $totalItem = 0;
                $totalDisProduct = 0;
                $error_item = [];
                if(!empty($post['item_service'])){
                    $itemServices = app($this->online_transaction)->checkServiceProduct($post, $outlet);
                    $post['item_service'] = $itemServices['item_service']??[];
                    $totalItem = $totalItem + $itemServices['total_item_service']??0;
                    $error_item = $itemServices['error_message']??[];
                }
                $subtotal = 0;
                $items = [];
                $post['item'] = isset($post['item']) ? app($this->online_transaction)->mergeProducts($post['item']) : null;
        
                foreach ($grandTotal as $keyTotal => $valueTotal) {
                    if ($valueTotal == 'subtotal') {
                        $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post, $discount_promo);
                        // $post['sub'] = $this->countTransaction($valueTotal, $post);
                        if (gettype($post['sub']) != 'array') {
                            $mes = ['Data Not Valid'];
        
                            if (isset($post['sub']->original['messages'])) {
                                $mes = $post['sub']->original['messages'];
        
                                if ($post['sub']->original['messages'] == ['Price Product Not Found']) {
                                    if (isset($post['sub']->original['product'])) {
                                        $mes = ['Price Product Not Found with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                                    }
                                }
        
                                if ($post['sub']->original['messages'] == ['Price Product Not Valid']) {
                                    if (isset($post['sub']->original['product'])) {
                                        $mes = ['Price Product Not Valid with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                                    }
                                }
                            }
        
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => $mes
                            ]);
                        }
        
                        // $post['subtotal'] = array_sum($post['sub']);
                        $post['subtotal'] = array_sum($post['sub']['subtotal']);
                        $post['subtotal'] = $post['subtotal'] - $totalDisProduct??0;
                    }elseif ($valueTotal == 'discount') {
                        // $post['dis'] = $this->countTransaction($valueTotal, $post);
                        $post['dis'] = app($this->setting_trx)->countTransaction($valueTotal, $post, $discount_promo);
                        $mes = ['Data Not Valid'];
        
                        if (isset($post['dis']->original['messages'])) {
                            $mes = $post['dis']->original['messages'];
        
                            if ($post['dis']->original['messages'] == ['Price Product Not Found']) {
                                if (isset($post['dis']->original['product'])) {
                                    $mes = ['Price Product Not Found with product '.$post['dis']->original['product'].' at outlet '.$outlet['outlet_name']];
                                }
                            }
        
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => $mes
                            ]);
                        }
        
                        // $post['discount'] = $post['dis'] + $totalDisProduct;
                        $post['discount'] = $totalDisProduct??0;
                    }else {
                        $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                    }
                }
        
                $subtotalProduct = 0;
                foreach ($post['item'] ?? [] as &$item) {
                    // get detail product
                    $product = Product::select([
                            'products.id_product','products.product_name','products.product_code','products.product_description',
                            DB::raw('(CASE
                                    WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = '.$post['id_outlet'].' ) = 1 
                                    THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$post['id_outlet'].' )
                                    ELSE product_global_price.product_global_price
                                END) as product_price'),
                            DB::raw('(select product_detail.product_detail_stock_item from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1) as product_stock_status'),
                            'brand_product.id_brand', 'products.product_variant_status'
                        ])
                        ->join('brand_product','brand_product.id_product','=','products.id_product')
                        ->leftJoin('product_global_price','product_global_price.id_product','=','products.id_product')
                        ->where('brand_outlet.id_outlet','=',$post['id_outlet'])
                        ->join('brand_outlet','brand_outlet.id_brand','=','brand_product.id_brand')
                        ->whereRaw('products.id_product in (CASE
                                WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                                is NULL AND products.product_visibility = "Visible" THEN products.id_product
                                WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility IS NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                                is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                                ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                            END)')
                        ->whereRaw('products.id_product in (CASE
                                WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                                is NULL THEN products.id_product
                                ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                            END)')
                        ->where(function ($query) use ($post){
                            $query->orWhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$post['id_outlet'].' ) is NOT NULL');
                            $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product) is NOT NULL');
                        })
                        ->with([
                            'photos' => function($query){
                                $query->select('id_product','product_photo');
                            },
                            'product_promo_categories' => function($query){
                                $query->select('product_promo_categories.id_product_promo_category','product_promo_category_name as product_category_name','product_promo_category_order as product_category_order');
                            },
                        ])
                    ->having('product_price','>',0)
                    ->groupBy('products.id_product')
                    ->orderBy('products.position')
                    ->find($item['id_product']);
                    $product->append('photo');
                    $product = $product->toArray();
        
                    if($product['product_variant_status'] && !empty($item['id_product_variant_group'])){
                        $product['product_stock_status'] = ProductVariantGroupDetail::where('id_product_variant_group', $item['id_product_variant_group'])
                                ->where('id_outlet', $outlet['id_outlet'])
                                ->first()['product_variant_group_detail_stock_item']??0;
                    }
        
                    if($item['qty'] > $product['product_stock_status']){
                        $error_msg[] = MyHelper::simpleReplace(
                            'Produk %product_name% tidak tersedia',
                            [
                                'product_name' => $product['product_name']
                            ]
                        );
                        continue;
                    }
                    unset($product['photos']);
                    $product['id_custom'] = $item['id_custom']??null;
                    $product['qty'] = $item['qty'];
        
                    $product['product_price_total'] = $item['transaction_product_subtotal'];
                    $product['product_price_raw'] = (int) $product['product_price'];
                    $product['product_price_raw_total'] = (int) $product['product_price'];
                    $product['qty_stock'] = (int)$product['product_stock_status'];
                    $product['product_price'] = (int) $product['product_price'];
                    $subtotalProduct = $subtotalProduct + $item['transaction_product_subtotal'];
        
                    //calculate total item
                    $totalItem += $product['qty'];
                    if(!empty($product['product_stock_status'])){
                        $product['product_stock_status'] = 'Available';
                    }else{
                        $product['product_stock_status'] = 'Sold Out';
                    }
                    $items[] = $product;
                }
                if(empty($post['customer']) || empty($post['customer']['name'])){
                    $id = $request->user()->id;
        
                    $user = User::leftJoin('cities', 'cities.id_city', 'users.id_city')->where('id', $id)
                            ->select('users.*', 'cities.city_name')->first();
                    if (empty($user)) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['User Not Found']
                        ]);
                    }
        
                    $post['customer'] = [
                        "name" => $user['name'],
                        "email" => $user['email'],
                        "domicile" => $user['city_name'],
                        "birthdate" => date('Y-m-d', strtotime($user['birthday'])),
                        "gender" => $user['gender'],
                    ];
                }else{
                    $post['customer'] = [
                        "name" => $post['customer']['name']??"",
                        "email" => $post['customer']['email']??"",
                        "domicile" => $post['customer']['domicile']??"",
                        "birthdate" => $post['customer']['birthdate']??"",
                        "gender" => $post['customer']['gender']??"",
                    ];
                }
                $post['outlet'] = [
                    'id_outlet' => $outlet['id_outlet'],
                    'outlet_code' => $outlet['outlet_code'],
                    'outlet_name' => $outlet['outlet_name'],
                    'outlet_address' => $outlet['outlet_address'],
                    'delivery_order' => $outlet['delivery_order'],
                    'today' => $outlet['today']
                ];
        
                $post['subtotal_product_service'] = $itemServices['subtotal_service']??0;
                $post['subtotal_product'] = $subtotalProduct??0;
                $post['subtotal'] = $post['subtotal_product_service'] + $post['subtotal_product'];
                $post['grandtotal'] = (int)$post['subtotal'] + (int)(-$post['discount']) + (int)$post['service'];
                $earnedPoint = app($this->online_transaction)->countTranscationPoint($post, $user);
                $post['cashback'] = $earnedPoint['cashback'] ?? 0;
                $post['grandtotal'] = (int)$post['subtotal'] + (int)(-$post['discount']) + (int)$post['service'];
                $balance = app($this->balance)->balanceNow($user->id);
                $post['points'] = (int) $balance;
                $post['total_payment'] = $post['grandtotal'] - 0;
                $fake_request = new Request(['show_all' => 1]);
                $post['available_payment'] = app($this->online_transaction)->availablePayment($fake_request)['result'] ?? [];

            }elseif($service[$post['transaction_from']] == 'Home Service'){

                if(!empty($request->user()->id)){
                    $user = User::where('id', $request->user()->id)
                        ->leftJoin('cities', 'cities.id_city', 'users.id_city')
                        ->select('users.*', 'cities.city_name')
                        ->first();
                    if (empty($user)) {
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['User Not Found']
                        ]);
                    }
                }
        
                if($post['preference_hair_stylist'] == 'favorite' && empty($post['id_user_hair_stylist'])){
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['User hair stylist can not be empty']
                    ]);
                }
        
                $bookNow = false;
                if(strtolower($post['booking_time']) == 'sekarang'){
                    $bookNow = true;
                    $post['booking_time'] = date('H:i', strtotime("+2 minutes", strtotime($post['booking_time_user'])));
                }
        
                $address = UserAddress::where('id_user', $user->id)->where('id_user_address', $post['id_user_address'])->first();
                if(empty($address)){
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Address user not found']
                    ]);
                }
        
                $post['latitude'] = $address['latitude'];
                $post['longitude'] = $address['longitude'];
        
                $errAll = [];
                $itemService = [];
                $arrProccessingTime = [];
                $continueCheckOut = true;
        
                //process get time start and end
                foreach ($post['item_service']??[] as $key=>$item){
                    $processingTime = Product::where('products.id_product', $item['id_product'])->first()['processing_time_service']??0;
                    $arrProccessingTime[] = $processingTime * $item['qty'];
                }
                $error_item = [];
                $post['item_service'] = app($this->home_service)->mergeService($post['item_service']);
                $outletHomeService = Setting::where('key', 'default_outlet_home_service')->first()['value']??null;
                $outlet = Outlet::where('id_outlet', $outletHomeService)->first();
                if(empty($outlet)){
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Outlet default not found']
                    ]);
                }
        
                $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
                    ->where('id_outlet', $outlet['id_outlet'])->first();
        
                if(empty($brand)){
                    return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
                }
                $totalItem = 0;
                $tmpHsCatGroup = [];
                $tmpHsCat = [];
                foreach ($post['item_service']??[] as $key=>$item){
                    $err = [];
                    $service = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                        ->where('products.id_product', $item['id_product'])
                        ->select('products.*', 'product_global_price as product_price')
                        ->first();
        
                    if(empty($service)){
                        $err[] = 'Service tidak tersedia';
                        $errAll[] = 'Service tidak tersedia';
                        unset($item[$key]);
                        continue;
                    }
        
                    $hsCat = ProductHairstylistCategory::where('id_product', $service['id_product'])->pluck('id_hairstylist_category')->toArray();
                    foreach ($hsCat as $cat){
                        $tmpHsCatGroup[$cat] = ($tmpHsCatGroup[$cat]??0) + 1;
                        $tmpHsCat[$service['id_product']][] = $cat;
                    }
        
                    if(!empty($idHs) && $post['preference_hair_stylist'] == 'favorite'){
                        $hs = UserHairStylist::where('id_user_hair_stylist', $idHs)->where('user_hair_stylist_status', 'Active')->first();
                        if(empty($hs)){
                            $err[] = "Outlet hair stylist not found";
                            $errAll[] = 'Outlet hair stylist not found';
                            unset($item[$key]);
                            continue;
                        }
        
                        if(!empty($hs) && !empty($hsCat) && !in_array($hs['id_hairstylist_category'], $hsCat)){
                            $idHsCategory = $hs['id_hairstylist_category'];
                            $err[] = 'Service tidak available untuk hairstylist favorite Anda';
                            $errAll[] = 'Service tidak available untuk hairstylist favorite Anda';
                            unset($item[$key]);
                            continue;
                        }
                    }
        
                    $getProductDetail = ProductDetail::where('id_product', $service['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();
                    $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;
        
                    if($service['visibility_outlet'] == 'Hidden' || (empty($service['visibility_outlet']) && $service['product_visibility'] == 'Hidden')){
                        $err[] = 'Service tidak tersedia';
                        $errAll[] = 'Service tidak tersedia';
                        unset($item[$key]);
                        continue;
                    }
        
                    if(!is_null($getProductDetail['product_detail_stock_item']) && $item['qty'] > $getProductDetail['product_detail_stock_item']){
                        $err[] = 'Service tidak tersedia';
                        $errAll[] = 'Service tidak tersedia';
                        unset($item[$key]);
                        continue;
                    }
        
                    if(empty($service['product_price'])){
                        $err[] = 'Service tidak tersedia';
                        $errAll[] = 'Service tidak tersedia';
                        unset($item[$key]);
                        continue;
                    }
        
                    $itemService[$key] = [
                        "id_custom" => $item['id_custom'],
                        "id_brand" => $brand['id_brand'],
                        "id_product" => $service['id_product'],
                        "product_code" => $service['product_code'],
                        "product_name" => $service['product_name'],
                        "product_price" => (int)$service['product_price'],
                        "subtotal" => (int)$service['product_price'] * $item['qty'],
                        "qty" => $item['qty'],
                        "error_msg" => (empty($err)? null:implode(".", array_unique($err)))
                    ];
        
                    $totalItem = $totalItem + $item['qty'];
        
                    if(!empty($err)){
                        $continueCheckOut = false;
                    }
                }
        
                if(empty($idHsCategory) && !empty($tmpHsCatGroup)){
                    $idHsCategory = array_search(max($tmpHsCatGroup), $tmpHsCatGroup);
                    foreach ($tmpHsCat as $category){
                        if(!in_array($idHsCategory, $category)){
                            $errAll[] = 'Service tidak dapat dipesan bersamaan';
                            break;
                        }
                    }
                }
        
                $post['sum_time'] = array_sum($arrProccessingTime);
                $checkHS = app($this->home_service)->checkAvailableHS($post, [], $user, $idHsCategory);
                $idHs = $checkHS['id_user_hair_stylist']??null;
                $errAll = array_merge($errAll, $checkHS['error_all']??[]);
        
                $post['item_service'] = $itemService;
                $grandTotal = app($this->setting_trx)->grandTotal();
                foreach ($grandTotal as $keyTotal => $valueTotal) {
                    if ($valueTotal == 'subtotal') {
                        $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                        if (gettype($post['sub']) != 'array') {
                            $mes = ['Data Not Valid'];
        
                            if (isset($post['sub']->original['messages'])) {
                                $mes = $post['sub']->original['messages'];
        
                                if ($post['sub']->original['messages'] == ['Product Service not found']) {
                                    if (isset($post['sub']->original['product'])) {
                                        $mes = ['Price Service Not Found with product '.$post['sub']->original['product']];
                                    }
                                }
        
                                if ($post['sub']->original['messages'] == ['Price Service Product Not Valid']) {
                                    if (isset($post['sub']->original['product'])) {
                                        $mes = ['Price Service Not Valid with product '.$post['sub']->original['product']];
                                    }
                                }
                            }
        
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => $mes
                            ]);
                        }
        
                        $post['subtotal'] = array_sum($post['sub']['subtotal']);
                    } else {
                        $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                    }
                }
        
                unset($address['description']);
                $post['customer'] = [
                    "name" => $user['name'],
                    "email" => $user['email'],
                    "phone" => $user['phone'],
                    "domicile" => $user['city_name'],
                    "birthdate" => date('Y-m-d', strtotime($user['birthday'])),
                    "gender" => $user['gender'],
                    "address" => $address
                ];
        
                if(!empty($errAll)){
                    $continueCheckOut = false;
                }
                $post['tax'] = ($outlet['is_tax']/100) * $post['subtotal'];
                $post['id_user_address'] = $address['id_user_address'];
                $post['notes'] = (empty($post['notes']) ? $address['description']:$post['notes']);
                $post['preference_hair_stylist'] = $post['preference_hair_stylist'];
                $post['id_user_hair_stylist'] = $idHs;
                $post['booking_date'] = $post['booking_date'];
                $post['booking_time'] = $post['booking_time'];
                if($bookNow){
                    $post['booking_time_user'] = $post['booking_time'];
                    $post['booking_time'] = 'Sekarang';
                }
                $post['booking_date_display'] = MyHelper::dateFormatInd($post['booking_date'].' '.$post['booking_time'], true, true);
                $post['item_service'] = array_values($itemService);
                $post['subtotal'] = $post['subtotal'];
                $post['grandtotal'] = (int)$post['subtotal'] + (int)$post['tax'] ;
                $balance = app($this->balance)->balanceNow($user->id);
                $post['points'] = (int) $balance;
                $post['total_payment'] = $post['grandtotal'];
                $post['tax'] = $post['tax'];
                $post['service'] = $post['service'] ?? 0;
        
                $earnedPoint = app($this->online_transaction)->countTranscationPoint($post, $user);
                $post['cashback'] = $earnedPoint['cashback'] ?? 0;
        
                $post['currency'] = 'Rp';
                $post['payment_detail'] = [];
                $post['point_earned'] = null;
                $post['currency'] = 'Rp';
                $post['complete_profile'] = (empty($user->complete_profile) ?false:true);
                $post['continue_checkout'] = $continueCheckOut;
                $post['messages_all'] = (empty($errAll)? null:implode(".", array_unique($errAll)));
                $fake_request = new Request(['show_all' => 1]);
                $post['available_payment'] = app($this->online_transaction)->availablePayment($fake_request)['result'] ?? [];
                $post['id_outlet'] = $outlet['id_outlet'];   
            }elseif($service[$post['transaction_from']] == 'Online Shop'){

                if (empty($post['outlet_code']) && empty($post['id_outlet'])) {
                    $post['id_outlet'] = Setting::where('key', 'default_outlet')->first()['value'] ?? null;
                }

                if (!empty($post['outlet_code'])) {
                    $outlet = Outlet::where('outlet_code', $post['outlet_code'])->with('today')->where('outlet_status', 'Active')
                        ->join('cities', 'cities.id_city', 'outlets.id_city')
                        ->join('provinces', 'provinces.id_province', 'cities.id_province')
                        ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')
                        ->first();
                    $post['id_outlet'] = $outlet['id_outlet'] ?? null;
                } else {
                    $outlet = Outlet::where('id_outlet', $post['id_outlet'])->with('today')->where('outlet_status', 'Active')
                        ->join('cities', 'cities.id_city', 'outlets.id_city')
                        ->join('provinces', 'provinces.id_province', 'cities.id_province')
                        ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')
                        ->first();
                }
        
                $id_outlet = $post['id_outlet'];
                if (empty($outlet)) {
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Outlet Not Found']
                    ]);
                }
                return $post;
                $issetDate = false;
                if (isset($post['transaction_date'])) {
                    $issetDate = true;
                    $post['transaction_date'] = date('Y-m-d H:i:s', strtotime($post['transaction_date']));
                } else {
                    $post['transaction_date'] = date('Y-m-d H:i:s');
                }
        
                if (!isset($post['payment_type'])) {
                    $post['payment_type'] = null;
                }
        
                if (!isset($post['shipping'])) {
                    $post['shipping'] = 0;
                }
        
                $error_msg = [];
        
                if (!isset($post['subtotal'])) {
                    $post['subtotal'] = 0;
                }
        
                if (!isset($post['discount'])) {
                    $post['discount'] = 0;
                }
        
                if (!isset($post['service'])) {
                    $post['service'] = 0;
                }
        
                if (!isset($post['tax'])) {
                    $post['tax'] = 0;
                }
        
                $totalItem = 0;
                $totalDisProduct = 0;
                $post['discount'] = -$post['discount'];
                $subtotal = 0;
                $items = [];
                $post['item'] = isset($post['item']) ? app($this->online_transaction)->mergeProducts($post['item']) : null;
        
                foreach ($grandTotal as $keyTotal => $valueTotal) {
                    if ($valueTotal == 'subtotal') {
                        $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post, $discount_promo);
                        // $post['sub'] = $this->countTransaction($valueTotal, $post);
                        if (gettype($post['sub']) != 'array') {
                            $mes = ['Data Not Valid'];
        
                            if (isset($post['sub']->original['messages'])) {
                                $mes = $post['sub']->original['messages'];
        
                                if ($post['sub']->original['messages'] == ['Price Product Not Found']) {
                                    if (isset($post['sub']->original['product'])) {
                                        $mes = ['Price Product Not Found with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                                    }
                                }
        
                                if ($post['sub']->original['messages'] == ['Price Product Not Valid']) {
                                    if (isset($post['sub']->original['product'])) {
                                        $mes = ['Price Product Not Valid with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                                    }
                                }
                            }
        
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => $mes
                            ]);
                        }
        
                        // $post['subtotal'] = array_sum($post['sub']);
                        $post['subtotal'] = array_sum($post['sub']['subtotal']);
                        $post['subtotal'] = $post['subtotal'] - $totalDisProduct??0;
                    }elseif ($valueTotal == 'discount') {
                        // $post['dis'] = $this->countTransaction($valueTotal, $post);
                        $post['dis'] = app($this->setting_trx)->countTransaction($valueTotal, $post, $discount_promo);
                        $mes = ['Data Not Valid'];
        
                        if (isset($post['dis']->original['messages'])) {
                            $mes = $post['dis']->original['messages'];
        
                            if ($post['dis']->original['messages'] == ['Price Product Not Found']) {
                                if (isset($post['dis']->original['product'])) {
                                    $mes = ['Price Product Not Found with product '.$post['dis']->original['product'].' at outlet '.$outlet['outlet_name']];
                                }
                            }
        
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => $mes
                            ]);
                        }
        
                        // $post['discount'] = $post['dis'] + $totalDisProduct;
                        $post['discount'] = $totalDisProduct??0;
                    }else {
                        $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                    }
                }
        
                $subtotalProduct = 0;
                foreach ($post['item'] ?? [] as &$item) {

                    $product = $this->getDetailProduct($item['id_product'], $post['id_outlet']);
                    $product->load([
                                'product_promo_categories' => function($query) {
                                    $query->select('product_promo_categories.id_product_promo_category','product_promo_category_name as product_category_name','product_promo_category_order as product_category_order');
                                }
                            ])->append('photo');
        
                    $product = $product->toArray();
        
                    if($product['product_variant_status'] && !empty($item['id_product_variant_group'])){
                        $product['product_stock_status'] = ProductVariantGroupDetail::where('id_product_variant_group', $item['id_product_variant_group'])
                                ->where('id_outlet', $outlet['id_outlet'])
                                ->first()['product_variant_group_detail_stock_item']??0;
                    }
        
                    if($item['qty'] > $product['product_stock_status']){
                        $error_msg[] = MyHelper::simpleReplace(
                            'Produk %product_name% tidak tersedia',
                            [
                                'product_name' => $product['product_name']
                            ]
                        );
                        continue;
                    }
                    unset($product['photos']);
                    $product['id_custom'] = $item['id_custom']??null;
                    $product['qty'] = $item['qty'];
        
                    $subtotalProduct = $subtotalProduct + $item['transaction_product_subtotal'];
        
                    //calculate total item
                    $totalItem += $product['qty'];
        
                    $tempItem =   [
                        'id_custom' => $product['id_custom'],
                        'id_product' => $product['id_product'],
                        'product_name' => $product['product_group']['product_group_name'] . ' ' . $product['variant_name'],
                        'product_code' => $product['product_code'],
                        'variant_name' => $product['variant_name'],
                        'product_description' => $product['product_description'],
                        'id_product_group' => $product['id_product_group'],
                        'id_product_category' => $product['id_product_category'],
                        'id_brand' => $product['id_brand'],
                        'photo' => $product['photo'],
                        'product_group_name' => $product['product_group']['product_group_name'],
                        'qty' => $product['qty'],
                        'product_stock_status' => $product['product_stock_status'],
                        'product_price' => (int) $product['product_price'],
                        'product_price_raw' => (int) $product['product_price'],
                        'product_price_raw_total' => (int) $item['transaction_product_subtotal'],
                        'product_price_total_pretty' => MyHelper::requestNumber((int) $item['transaction_product_subtotal'],'_CURRENCY'),
                        'qty_stock' => (int)$product['product_stock_status'],
                    ];
        
                    if(!empty($tempItem['product_stock_status'])){
                        $tempItem['product_stock_status'] = 'Available';
                    }else{
                        $tempItem['product_stock_status'] = 'Sold Out';
                    }
        
                    $items[] = $tempItem;
                }
        
                $post['tax'] = ($outlet['is_tax']/100) * $post['subtotal'];
        
                if ($post['id_user_address'] ?? null) {
                    $address = UserAddress::where('id_user', $user->id)->where('id_user_address', $post['id_user_address'])->first();
                } else {
                    $address = UserAddress::where('id_user', $user->id)->where('favorite', 1)->first();
                }
        
                if(empty($post['customer']) || empty($post['customer']['name'])){
                    $id = $request->user()->id;
        
                    $user = User::leftJoin('cities', 'cities.id_city', 'users.id_city')->where('id', $id)
                            ->select('users.*', 'cities.city_name')->first();
                    if (empty($user)) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['User Not Found']
                        ]);
                    }
        
                    $post['customer'] = [
                        "name" => $user['name'],
                        "email" => $user['email'],
                        "phone" => $user['phone'],
                        "domicile" => $user['city_name'],
                        "birthdate" => date('Y-m-d', strtotime($user['birthday'])),
                        "gender" => $user['gender'],
                        "address" => $address
                    ];
                }else{
                    $post['customer'] = [
                        "name" => $post['customer']['name'] ?? null,
                        "email" => $post['customer']['email'] ?? null,
                        "phone" => $post['customer']['phone'] ?? null,
                        "domicile" => $post['customer']['domicile'] ?? null,
                        "birthdate" => $post['customer']['birthdate'] ?? null,
                        "gender" => $post['customer']['gender'] ?? null,
                        "address" => $post['customer']['address'] ?? []
                    ];
                }
        
                $post['outlet'] = [
                    'id_outlet' => $outlet['id_outlet'],
                    'outlet_code' => $outlet['outlet_code'],
                    'outlet_name' => $outlet['outlet_name'],
                    'outlet_address' => $outlet['outlet_address'],
                    'delivery_order' => $outlet['delivery_order'],
                    'today' => $outlet['today']
                ];
                $post['item'] = $items;
                $post['subtotal_product'] = $subtotalProduct;
                $subtotal = $post['subtotal'];
        
                $earnedPoint = app($this->online_trx)->countTranscationPoint($post, $user);
                $cashback = $earnedPoint['cashback'] ?? 0;
                if ($cashback) {
                    $post['point_earned'] = [
                        'value' => MyHelper::requestNumber($cashback, '_CURRENCY'),
                        'text' => MyHelper::setting('cashback_earned_text', 'value', 'Point yang akan didapatkan')
                    ];
                }
        
                $listDelivery = $this->listDelivery();
                if (!$request->delivery_name && !$request->delivery_method) {
                    $deliv = $listDelivery[0] ?? null;
                } else {
                    $deliv = $this->findDelivery($listDelivery, $request->delivery_name, $request->delivery_method);
                }
                if (empty($deliv) && $request->delivery_name && $request->delivery_method) {
                    $error_msg[] = 'Pengiriman tidak ditemukan';
                }
                $post['shipping'] = $deliv['price'] ?? 0;
        
                $post['id_user_address'] = $address['id_user_address'] ?? null;
                $post['subtotal'] = $subtotal;
                $post['tax'] = $post['tax'];
                $post['shipping'] = $post['shipping'];
                $post['discount'] = $post['discount'];
                $post['grandtotal'] = (int)$post['subtotal'] + (int)(-$post['discount']) + (int)$post['service'] + (int)$post['tax'] + $post['shipping'];
                $post['subscription'] = 0;
                $post['used_point'] = 0;
                $balance = app($this->balance)->balanceNow($user->id);
                $post['points'] = (int) $balance;
                $post['total_payment'] = $post['grandtotal'] - $post['used_point'];
                $post['discount'] = (int) $post['discount'];
                $post['currency'] = 'Rp';
                $post['complete_profile'] = true;
                $post['payment_detail'] = [];
                $post['continue_checkout'] = (empty($error_msg) ? true : false);
                $fake_request = new Request(['show_all' => 1]);
                $post['available_payment'] = app($this->online_trx)->availablePayment($fake_request)['result'] ?? [];
                
            }
    
            $new_result_data = [];
            foreach($result['data'] ?? [] as $key => $data_voucher){
                if(!$error_item){
                    $check_avail = app($this->ship)->checkVoucherAvail($data_voucher['id_deals'],$post);
                    if($check_avail['status']=='success'){
                        if(isset($data_voucher['id_deals_voucher']) && isset($data_voucher['id_deals_user'])){
                            $data_voucher['type_deals'] = 'voucher';
                        }else{
                            $data_voucher['type_deals'] = 'deals';
                        }
                        $new_result_data[] = $data_voucher;
                    }
                }
            }
            if(count($new_result_data)>0){
                $result['data'] = $new_result_data;
            }else{
                $result['data'] = !$error_item ? $result['data'] : [];
            }


        }

        $result['current_page'] = 1;
        $result['from'] = 1;
        $result['last_page'] = 1;
        $result['path'] = $request->url();
        $result['per_page'] = count($result['data']);
        $result['to'] = count($result['data']);
        $result['total'] = count($result['data']);
        if(!$result['total']){
            $result=[];
        }
        
        if (empty($result['data'])) {
            $empty_text = Setting::where('key','=','message_myvoucher_empty_header')
                ->orWhere('key','=','message_myvoucher_empty_content')
                ->orderBy('id_setting')
                ->get();
            $resultMessage['header'] =  $empty_text[0]['value']??'Anda belum memiliki Kupon.';
            $resultMessage['content'] =  $empty_text[1]['value']??'Potongan menarik untuk setiap pembelian.';
            return  response()->json([
                'status'   => 'fail',
                'messages' => ['My voucher is empty'],
                'empty'    => $resultMessage
            ]);
        }
        return response()->json(app($this->subscription)->checkGet($result, $resultMessage??''));

    }

    public function checkVoucherAvail($id_deals,$data){
    	$sharedPromoTrx = TemporaryDataManager::create('promo_trx');
        $deals = Deal::find($id_deals);
        $sharedPromoTrx['deals'] = $deals;
        $dealsPayment = DealsPaymentMethod::where('id_deals', $deals['id_deals'])->pluck('payment_method')->toArray();
        $dealsType = $deals->promo_type;

        if (!empty($dealsPayment)) {

    		if (!empty($dealsPayment)) {
    			$validPayment = [];
    			foreach ($data['available_payment'] as $payment) {
	    			if (!in_array($payment['payment_method'], $dealsPayment)) {
	    				continue;
	    			}
	    			if (!empty($payment['status'])) {
		    			$validPayment[] = $payment['payment_method'];
	    			}
	    		}
	    		$dealsPayment = $validPayment;
	    		if (empty($validPayment)) {
	    			return [
                        'status' => 'fail'
                    ];
	    		}
    		}

    	}

	    app($this->promo_trx)->createSharedPromoTrx($data);
        return $applyDeals = app($this->promo_trx)->applyDeals($id_deals, $data);
    }
    /*============================= End Filter & Sort V2 ================================*/
}