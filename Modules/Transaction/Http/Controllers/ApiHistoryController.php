<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Transaction;
use App\Http\Models\DealsUser;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\Configs;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentBalance;

use App\Lib\MyHelper;

class ApiHistoryController extends Controller
{
    public function historyAll(Request $request){

        $post = $request->json()->all();
        // return $post;
        $id = $request->user()->id;
        $order = 'new';
        $page = 1;

        if(!isset($post['pickup_order'])){
            $post['pickup_order'] = null;
        }

        if(!isset($post['delivery_order'])){
            $post['delivery_order'] = null;
        }

        if(!isset($post['online_order'])){
            $post['online_order'] = null;
        }

        if(!isset($post['offline_order'])){
            $post['offline_order'] = null;
        }

        if(!isset($post['pending'])){
            $post['pending'] = null;
        }

        if(!isset($post['paid'])){
            $post['paid'] = null;
        }

        if(!isset($post['completed'])){
            $post['completed'] = null;
        }

        if(!isset($post['cancel'])){
            $post['cancel'] = null;
        }

        if(!isset($post['use_point'])){
            $post['use_point'] = null;
        }
        if(!isset($post['earn_point'])){
            $post['earn_point'] = null;
        }
        if(!isset($post['offline_order'])){
            $post['offline_order'] = null;
        }
        if(!isset($post['voucher'])){
            $post['voucher'] = null;
        }

        $transaction = $this->transaction($post, $id);

        $balance = [];
        $cofigBalance = Configs::where('config_name', 'balance')->first();
        if($cofigBalance && $cofigBalance->is_active == '1'){
            $balance = $this->balance($post, $id);
        }

        $point = [];
        $cofigPoint = Configs::where('config_name', 'point')->first();
        if($cofigPoint && $cofigPoint->is_active == '1'){
            $point = $this->point($post, $id);
        }
        // $voucher = [];

        if (!is_null($post['oldest'])) {
            $order = 'old';
        }

        if (!is_null($post['newest'])) {
            $order = 'new';
        }

        if (!is_null($request->get('page'))) {
            $page = $request->get('page');
        }

        $next_page = $page + 1;
        
        $merge = array_merge($transaction, $balance);
        $merge = array_merge($merge, $point);
        // return $merge;
        $sortTrx = $this->sorting($merge, $order, $page);

        $check = MyHelper::checkGet($sortTrx);
        if (count($merge) > 0) {
            $ampas['status'] = 'success';
            $ampas['current_page']  = $page;
            $ampas['data']          = $sortTrx['data'];
            $ampas['total']         = count($merge);
            $ampas['next_page_url'] = null;

            if ($sortTrx['status'] == true) {
                $ampas['next_page_url'] = ENV('APP_API_URL').'/api/transaction/history?page='.$next_page;
            }
        } else {
            $ampas['status'] = 'fail';
            $ampas['messages'] = ['empty'];
            
        }

        return response()->json($ampas);
    }

    public function historyTrx(Request $request) {

        $post = $request->json()->all();
        // return $post;
        $id = $request->user()->id;
        $order = 'new';
        $page = 1;

        if(!isset($post['pickup_order'])){
            $post['pickup_order'] = null;
        }

        if(!isset($post['delivery_order'])){
            $post['delivery_order'] = null;
        }

        if(!isset($post['online_order'])){
            $post['online_order'] = null;
        }

        if(!isset($post['offline_order'])){
            $post['offline_order'] = null;
        }

        if(!isset($post['pending'])){
            $post['pending'] = null;
        }

        if(!isset($post['paid'])){
            $post['paid'] = null;
        }

        if(!isset($post['completed'])){
            $post['completed'] = null;
        }

        if(!isset($post['cancel'])){
            $post['cancel'] = null;
        }

        if(!isset($post['buy_voucher'])){
            $post['buy_voucher'] = null;
        }


        $transaction = $this->transaction($post, $id);
        $voucher = [];

        if (is_null($post['pickup_order']) && is_null($post['delivery_order']) && is_null($post['offline_order'])) {
            if (!is_null($post['buy_voucher'])) {
                $transaction = [];
                $voucher = $this->voucher($post, $id);
            }
            
        } elseif (!is_null($post['pickup_order']) || !is_null($post['delivery_order']) || !is_null($post['offline_order'])) {
            if (!is_null($post['buy_voucher'])) {
                $voucher = $this->voucher($post, $id);
            }
        }

        if (!is_null($post['oldest'])) {
            $order = 'old';
        }

        if (!is_null($post['newest'])) {
            $order = 'new';
        }

        if (!is_null($request->get('page'))) {
            $page = $request->get('page');
        }

        $next_page = $page + 1;
        
        $merge = array_merge($transaction, $voucher);
        $sortTrx = $this->sorting($merge, $order, $page);

        $check = MyHelper::checkGet($sortTrx);
        if (count($merge) > 0) {
            $ampas['status'] = 'success';
            $ampas['current_page']  = $page;
            $ampas['data']          = $sortTrx['data'];
            $ampas['total']         = count($merge);
            $ampas['next_page_url'] = null;

            if ($sortTrx['status'] == true) {
                $ampas['next_page_url'] = ENV('APP_API_URL').'/api/transaction/history-trx?page='.$next_page;
            }
        } else {
            $ampas['status'] = 'fail';
            $ampas['messages'] = ['empty'];
            
        }

        return response()->json($ampas);
    }

    public function historyTrxOnGoing(Request $request) {

        $post = $request->json()->all();
        // return $post;
        $id = $request->user()->id;
        $order = 'new';
        $page = 1;

        $transaction = $this->transactionOnGoingPickup($post, $id);

        if (!is_null($post['oldest'])) {
            $order = 'old';
        }

        if (!is_null($post['newest'])) {
            $order = 'new';
        }

        if (!is_null($request->get('page'))) {
            $page = $request->get('page');
        }

        $next_page = $page + 1;
        
        $sortTrx = $this->sorting($transaction, $order, $page);

        $check = MyHelper::checkGet($sortTrx);
        if (count($transaction) > 0) {
            $ampas['status'] = 'success';
            $ampas['current_page']  = $page;
            $ampas['data']          = $sortTrx['data'];
            $ampas['total']         = count($transaction);
            $ampas['next_page_url'] = null;

            if ($sortTrx['status'] == true) {
                $ampas['next_page_url'] = ENV('APP_API_URL').'/api/transaction/history-ongoing?page='.$next_page;
            }
        } else {
            $ampas['status'] = 'fail';
            $ampas['messages'] = ['empty'];
            
        }

        return response()->json($ampas);
    }

    public function historyPoint(Request $request) {
        $post = $request->json()->all();
        $id = $request->user()->id;
        $order = 'new';
        $page = 1;

        if(!isset($post['use_point'])){
            $post['use_point'] = null;
        }
        if(!isset($post['earn_point'])){
            $post['earn_point'] = null;
        }
        if(!isset($post['offline_order'])){
            $post['offline_order'] = null;
        }
        if(!isset($post['voucher'])){
            $post['voucher'] = null;
        }

        if(!isset($post['buy_voucher'])){
            $post['buy_voucher'] = null;
        }

        if (!is_null($post['oldest'])) {
            $order = 'old';
        }

        if (!is_null($post['newest'])) {
            $order = 'new';
        }

        if (!is_null($request->get('page'))) {
            $page = $request->get('page');
        }

        $next_page = $page + 1;

        $point = $this->point($post, $id);

        $sortPoint = $this->sorting($point, $order, $page);
        
        $check = MyHelper::checkGet($sortPoint);
        if (count($point) > 0) {
            $ampas['status'] = 'success';
            $ampas['current_page']  = $page;
            $ampas['data']          = $sortPoint['data'];
            $ampas['total']         = count($point);
            $ampas['next_page_url'] = null;

            if ($sortPoint['status'] == true) {
                $ampas['next_page_url'] = ENV('APP_API_URL').'/api/transaction/history-point?page='.$next_page;
            }
        } else {
            $ampas['status'] = 'fail';
            $ampas['messages'] = ['empty'];
            
        }

        return response()->json($ampas);
     
               
    }

    public function historyBalance(Request $request) {
        $post = $request->json()->all();
        $id = $request->user()->id;
        $order = 'new';
        $page = 0;

        if(!isset($post['use_point'])){
            $post['use_point'] = null;
        }
        if(!isset($post['earn_point'])){
            $post['earn_point'] = null;
        }
        if(!isset($post['offline_order'])){
            $post['offline_order'] = null;
        }
        if(!isset($post['voucher'])){
            $post['voucher'] = null;
        }

        if (!is_null($post['oldest'])) {
            $order = 'old';
        }

        if (!is_null($post['newest'])) {
            $order = 'new';
        }

        if (!is_null($request->get('page'))) {
            $page = $request->get('page');
        }

        $next_page = $page + 1;

        $balance = $this->balance($post, $id);
        $sortBalance = $this->sorting($balance, $order, $page);
        
        $check = MyHelper::checkGet($sortBalance);
        if (count($balance) > 0) {
            $ampas['status'] = 'success';
            $ampas['current_page']  = $page;
            $ampas['data']          = $sortBalance['data'];
            $ampas['total']         = count($balance);
            $ampas['next_page_url'] = null;

            if ($sortBalance['status'] == true) {
                $ampas['next_page_url'] = ENV('APP_API_URL').'/api/transaction/history-balance?page='.$next_page;
            }
        } else {
            $ampas['status'] = 'fail';
            $ampas['messages'] = ['empty'];
            
        }

        return response()->json($ampas);
     
               
    }

    public function sorting($data, $order, $page) {
        $date = [];
        foreach ($data as $key => $row)
        {
            $date[$key] = $row['date'];
        }

        if ($order == 'new') {
            array_multisort($date, SORT_DESC, $data);
        }

        if ($order == 'old') {
            array_multisort($date, SORT_ASC, $data);
        }

        $next = false;

        if ($page > 0) {
            $resultData = [];
            $paginate   = 10;
            $start      = $paginate * ($page - 1);
            $all        = $paginate * $page;
            $end        = $all;
            $next       = true;

            if ($all > count($data)) {
                $end = count($data);
                $next = false;
            }

            for ($i=$start; $i < $end; $i++) {
                $useragent = $_SERVER['HTTP_USER_AGENT'];
                if(stristr($useragent,'okhttp')){
                    $data[$i]['date'] = MyHelper::dateFormatInd($data[$i]['date']);
                }
                array_push($resultData, $data[$i]);
            }

            return ['data' => $resultData, 'status' => $next];
        }
        

        return ['data' => $data, 'status' => $next];
    }

    public function transaction($post, $id) {
        $transaction = Transaction::with('outlet', 'logTopup')->orderBy('transaction_date', 'DESC');

        if (!is_null($post['date_start']) && !is_null($post['date_end'])) {
            $date_start = date('Y-m-d', strtotime($post['date_start']))." 00.00.00";
            $date_end = date('Y-m-d', strtotime($post['date_end']))." 23.59.59";

            $transaction->whereBetween('transaction_date', [$date_start, $date_end]);
        }

        $transaction->where(function ($query) use ($post) {
            if (!is_null($post['pickup_order'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('trasaction_type', 'Pickup Order');
                });
            }

            if (!is_null($post['delivery_order'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('trasaction_type', 'Delivery');
                });
            }

            if (!is_null($post['offline_order'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('trasaction_type', 'Offline');
                });
            }
        });

        $transaction->where(function ($query) use ($post) {
            if (!is_null($post['pending'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('transaction_payment_status', 'Pending');
                });
            }

            if (!is_null($post['paid'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('transaction_payment_status', 'Paid');
                });
            }

            if (!is_null($post['completed'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('transaction_payment_status', 'Completed');
                });
            }

            if (!is_null($post['cancel'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('transaction_payment_status', 'Cancelled');
                });
            }
        });

        $transaction->where('id_user', $id);

        $transaction = $transaction->get()->toArray();

        $listTransaction = [];

        foreach ($transaction as $key => $value) {
            // $transaction[$key]['date'] = $value['transaction_date'];
            // $transaction[$key]['type'] = 'trx';
            // $transaction[$key]['outlet'] = $value['outlet']['outlet_name'];
            
            //cek payment
            if($value['trasaction_payment_type']){
                $found = false;
                if($value['trasaction_payment_type'] == 'Midtrans'){
                    $pay = TransactionMultiplePayment::where('id_transaction', $value['id_transaction'])->first();
                    if($pay){
                        $payMidtrans = TransactionPaymentMidtran::where('id_transaction', $value['id_transaction'])->first();
                        if($payMidtrans && $payMidtrans['transaction_status']){
                            $found = true;
                        }
                    }else{
                        $payMidtrans = TransactionPaymentMidtran::where('id_transaction', $value['id_transaction'])->first();
                        if($payMidtrans && $payMidtrans['transaction_status']){
                            $found = true;
                        }
                    }
                }else{
                    $found = true;
                }

                if($found == true){
                    $dataList['type'] = 'trx';
                    $dataList['id'] = $value['transaction_receipt_number'];
                    $dataList['date']    = date('Y-m-d H:i:s', strtotime($value['transaction_date']));
                    $dataList['outlet'] = $value['outlet']['outlet_name'];
                    $dataList['amount'] = number_format($value['transaction_grandtotal'], 0, ',', '.');
        
                    $listTransaction[] = $dataList;
                }
            }

        }

        return $listTransaction;
    }

    public function transactionOnGoingPickup($post, $id) {
        $transaction = Transaction::join('transaction_pickups', 'transactions.id_transaction', 'transaction_pickups.id_transaction')
                                    ->with('outlet')
                                    ->where('transaction_payment_status', 'Completed')
                                    ->whereDate('transaction_date', date('Y-m-d'))
                                    ->whereNull('taken_at')
                                    ->whereNull('reject_at')
                                    ->where('id_user', $id)
                                    ->orderBy('transaction_date', 'DESC')
                                    ->get()->toArray();

        $listTransaction = [];

        foreach ($transaction as $key => $value) {
            $dataList['type'] = 'trx';
            $dataList['id'] = $value['transaction_receipt_number'];
            $dataList['date']    = date('Y-m-d H:i:s', strtotime($value['transaction_date']));
            $dataList['outlet'] = $value['outlet']['outlet_name'];
            $dataList['amount'] = number_format($value['transaction_grandtotal'], 0, ',', '.');

            if($value['ready_at'] != null){
                $dataList['status'] = "Pesanan Sudah Siap";
            }
            elseif($value['receive_at'] != null){
                $dataList['status'] = "Pesanan Sudah Diterima";
            }else{
                $dataList['status'] = "Pesanan Menunggu Konfirmasi";
            }

            $listTransaction[] = $dataList;
        }

        return $listTransaction;
    }

    public function voucher($post, $id) {
        $voucher = DealsUser::with('outlet')->orderBy('claimed_at', 'DESC');

        if (!is_null($post['date_start']) && !is_null($post['date_end'])) {
            $date_start = date('Y-m-d', strtotime($post['date_start']))." 00.00.00";
            $date_end = date('Y-m-d', strtotime($post['date_end']))." 23.59.59";

            $voucher->whereBetween('claimed_at', [$date_start, $date_end]);
        }

        $voucher->where(function ($query) use ($post) {
            if (!is_null($post['pending'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('paid_status', 'Pending');
                });
            }

            if (!is_null($post['paid'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('paid_status', 'Paid');
                });
            }

            if (!is_null($post['completed'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('paid_status', 'Completed');
                });
            }

            if (!is_null($post['cancel'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('paid_status', 'Cancelled');
                });
            }
        });

        $voucher->where('id_user', $id);

        $voucher = $voucher->get()->toArray();

        foreach ($voucher as $key => $value) {
            $voucher[$key]['date'] = $value['claimed_at'];
            $voucher[$key]['type'] = 'voucher';
            $voucher[$key]['outlet'] = $value['outlet']['outlet_name'];
        }

        return $voucher;
    }

    public function point($post, $id) {
        $log = LogPoint::where('id_user', $id)->get();
       
        $listPoint = [];
        
        foreach ($log as $key => $value) {
            if ($value['source'] == 'Transaction') {
                $trx = Transaction::with('outlet')->where('id_transaction', $value['id_reference'])->first();

                $dataList['type']    = 'point';
                $dataList['detail_type']    = 'trx';
                $dataList['id']      = $value['id_log_point'];
                $dataList['date']    = date('Y-m-d H:i:s', strtotime($trx['transaction_date']));
                $dataList['outlet']  = $trx['outlet']['outlet_name'];
                $dataList['amount'] = $value['point'];

                $listPoint[$key] = $dataList;

                if ($trx['trasaction_type'] == 'Offline') {
                    $log[$key]['online'] = 0;
                } else {
                    $log[$key]['online'] = 1;
                }
            } else {
                $vou = DealsUser::with('dealVoucher.deal')->where('id_deals_user', $value['id_reference'])->first();

                $dataList['type']        = 'point';
                $dataList['detail_type'] = 'voucher';
                $dataList['id']          = $value['id_log_point'];
                $dataList['date']    = date('Y-m-d H:i:s', strtotime($vou['claimed_at']));
                $dataList['outlet']      = $trx['outlet']['outlet_name'];
                $dataList['amount']     = $value['point'];
                $log[$key]['online']     = 1;

                $listPoint[$key] = $dataList;
            }

            if (!is_null($post['date_start']) && !is_null($post['date_end'])) {
                $date_start = date('Y-m-d', strtotime($post['date_start']))." 00.00.00";
                $date_end = date('Y-m-d', strtotime($post['date_end']))." 23.59.59";

                if ($listPoint[$key]['date'] < $date_start || $listPoint[$key]['date'] > $date_end) {
                    unset($listPoint[$key]);
                    continue;
                }
            }

            if (!is_null($post['use_point']) && !is_null($post['earn_point']) && !is_null($post['online_order']) && !is_null($post['offline_order']) && !is_null($post['voucher'])) {
            }

            if (!is_null($post['use_point']) && !is_null($post['earn_point'])) {
               
            } elseif (is_null($post['use_point']) && is_null($post['earn_point'])) {
               
            } else {
                if (!is_null($post['use_point'])) {
                    if ($value['source'] == 'Transaction') {
                        unset($listPoint[$key]);
                        continue;
                    }
                }

                if (!is_null($post['earn_point'])) {
                    if ($value['source'] != 'Transaction') {
                        unset($listPoint[$key]);
                        continue;
                    }
                }
            }


            if (!is_null($post['online_order']) && !is_null($post['offline_order']) && !is_null($post['voucher'])) {
                
            } elseif (is_null($post['online_order']) && is_null($post['offline_order']) && is_null($post['voucher'])) {
                
            } else {
                if (!is_null($post['online_order'])) {
                    if (is_null($post['voucher'])) {
                        if ($listPoint[$key]['type'] == 'voucher') {
                            unset($listPoint[$key]);
                            continue;
                        }
                    }

                    if ($listPoint[$key]['online'] == 0) {
                        unset($listPoint[$key]);
                        continue;
                    }
                }

                if (!is_null($post['offline_order'])) {
                    if ($listPoint[$key]['online'] != 0) {
                        unset($listPoint[$key]);
                        continue;
                    }
                }

                if (!is_null($post['voucher'])) {
                    if ($listPoint[$key]['type'] != 'voucher') {
                        unset($listPoint[$key]);
                        continue;
                    }
                }
            }

        }

        return $listPoint;
    }

    function pointTest($post) {
        $log = DB::table('log_points')->paginate();


    }

    public function balance($post, $id) {
        $log = LogBalance::where('id_user', $id)->get();
        $listBalance = [];
        
        foreach ($log as $key => $value) {
            if ($value['source'] == 'Transaction' || $value['source'] == 'Rejected Order') {
                $trx = Transaction::with('outlet')->where('id_transaction', $value['id_reference'])->first();
                
                // return $trx;
                // $log[$key]['detail'] = $trx;
                // $log[$key]['type']   = 'trx';
                // $log[$key]['date']   = date('Y-m-d H:i:s', strtotime($trx['transaction_date']));
                // $log[$key]['outlet'] = $trx['outlet']['outlet_name'];
                // if ($trx['trasaction_type'] == 'Offline') {
                //     $log[$key]['online'] = 0;
                // } else {
                //     $log[$key]['online'] = 1;
                // }

                if (empty($trx)) {
                    continue;
                }

                $dataList['type']    = 'balance';
                $dataList['id']      = $value['id_log_balance'];
                $dataList['date']    = date('Y-m-d H:i:s', strtotime($value['created_at']));
                $dataList['outlet']  = $trx['outlet']['outlet_name'];
                if ($value['balance'] < 0) {
                    $dataList['amount'] = '- '.ltrim(number_format($value['balance'], 0, ',', '.'), '-');
                } else {
                    $dataList['amount'] = '+ '.number_format($value['balance'], 0, ',', '.');
                }

                $listBalance[$key] = $dataList;


            } elseif ($value['source'] == 'Voucher') {
                $vou = DealsUser::with('dealVoucher.deal')->where('id_deals_user', $value['id_reference'])->first();
                // $log[$key]['detail'] = $vou;
                $dataList['type']   = 'voucher';
                $dataList['id']      = $value['id_log_balance'];
                $dataList['date']   = date('Y-m-d H:i:s', strtotime($vou['claimed_at']));
                $dataList['outlet'] = $vou['outlet']['outlet_name'];
                $dataList['amount'] = '- '.ltrim(number_format($value['balance'], 0, ',', '.'), '-');
                // $dataList['amount'] = number_format($value['balance'], 0, ',', '.');
                // $dataList['online'] = 1;

                $listBalance[$key] = $dataList;
            } else {
                // return 'a';
                $dataList['type']   = 'profile';
                $dataList['id']      = $value['id_log_balance'];
                $dataList['date']    = date('Y-m-d H:i:s', strtotime($value['created_at']));
                $dataList['outlet'] = 'Completing User Profile';
                $dataList['amount'] = '+ '.number_format($value['balance'], 0, ',', '.');

                $listBalance[$key] = $dataList;
            }

            if (!is_null($post['date_start']) && !is_null($post['date_end'])) {
                $date_start = date('Y-m-d', strtotime($post['date_start']))." 00.00.00";
                $date_end = date('Y-m-d', strtotime($post['date_end']))." 23.59.59";

                if ($listBalance[$key]['date'] < $date_start || $listBalance[$key]['date'] > $date_end) {
                    unset($listBalance[$key]);
                    continue;
                }
            }
            
            if (!is_null($post['use_point']) && !is_null($post['earn_point']) && !is_null($post['online_order']) && !is_null($post['offline_order']) && !is_null($post['voucher'])) {
            }

            if (!is_null($post['use_point']) && !is_null($post['earn_point'])) {
               
            } elseif (is_null($post['use_point']) && is_null($post['earn_point'])) {
               
            } else {
                if (!is_null($post['use_point'])) {
                    if ($value['source'] == 'Transaction') {
                        unset($listBalance[$key]);
                        continue;
                    }
                }

                if (!is_null($post['earn_point'])) {
                    if ($value['source'] != 'Transaction') {
                        unset($listBalance[$key]);
                        continue;
                    }
                }
            }


            // if (!is_null($post['online_order']) && !is_null($post['offline_order']) && !is_null($post['voucher'])) {
                
            // } elseif (is_null($post['online_order']) && is_null($post['offline_order']) && is_null($post['voucher'])) {
                
            // } else {
            //     if (!is_null($post['online_order'])) {
            //         if (is_null($post['voucher'])) {
            //             if ($listBalance[$key]['type'] == 'voucher') {
            //                 unset($listBalance[$key]);
            //                 continue;
            //             }
            //         }

            //         if ($listBalance[$key]['online'] == 0) {
            //             unset($listBalance[$key]);
            //             continue;
            //         }
            //     }

            //     if (!is_null($post['offline_order'])) {
            //         if ($log[$listBalance]['online'] != 0) {
            //             unset($listBalance[$key]);
            //             continue;
            //         }
            //     }

            //     if (!is_null($post['voucher'])) {
            //         if ($listBalance[$key]['type'] != 'voucher') {
            //             unset($listBalance[$key]);
            //             continue;
            //         }
            //     }
            // }

        }

        return array_values($listBalance);
    }
}
