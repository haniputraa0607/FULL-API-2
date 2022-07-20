<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\DailyTransactions;
use App\Http\Models\LogBalance;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentManual;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionProduct;
use App\Lib\Midtrans;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Brand\Entities\Brand;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\Product\Entities\ProductDetail;
use App\Http\Models\TransactionPayment;
use App\Http\Models\User;
use App\Http\Models\Product;
use App\Http\Models\StockLog;
use DB;
use App\Http\Models\Configs;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use Modules\Transaction\Entities\LogInvalidTransaction;
use Modules\Transaction\Entities\TransactionAcademy;
use Modules\Transaction\Entities\TransactionAcademyInstallment;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\UserFeedback\Entities\UserFeedbackLog;
use Modules\Franchise\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\TransactionPromo;
use Modules\Xendit\Entities\TransactionAcademyInstallmentPaymentXendit;
use Modules\Xendit\Entities\TransactionPaymentXendit;
class ApiTransactionAcademy extends Controller
{
    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->product       = "Modules\Product\Http\Controllers\ApiProductController";
        $this->online_trx    = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->transaction   = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->outlet        = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->promo_trx 	 = "Modules\Transaction\Http\Controllers\ApiPromoTransaction";
        $this->xendit = "Modules\Xendit\Http\Controllers\XenditController";
    }

    public function check(Request $request) {
        $post = $request->json()->all();

        if(empty($post['item_academy'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item Academy can not be empty']
            ]);
        }

        if(!empty($request->user()->id)){
            $user = User::leftJoin('cities', 'cities.id_city', 'users.id_city')
                ->select('users.*', 'cities.city_name')
            ->where('id', $request->user()->id)->first();
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User Not Found']
                ]);
            }
        }

        if(empty($post['id_outlet'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['ID outlet can not be empty']
            ]);
        }

        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->where('outlet_academy_status', 1)->first();
        if (empty($outlet)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet Not Found']
            ]);
        }

        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
            ->where('id_outlet', $outlet['id_outlet'])->first();

        if(empty($brand)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
        }

        $errAll = [];
        $continueCheckOut = true;

        $itemAcademy = $post['item_academy'];
        $academy = Product::leftJoin('product_global_price','product_global_price.id_product','=','products.id_product')
            ->where('products.id_product', $itemAcademy['id_product'])
            ->select('products.*', DB::raw('(CASE
                            WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $outlet['id_outlet'] . ' ) = 1 
                            THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . ' )
                            ELSE product_global_price.product_global_price
                        END) as product_price'))
            ->first();

        if(empty($academy)){
            $errAll[] = 'Kursus tidak tersedia';
        }

        $getProductDetail = ProductDetail::where('id_product', $academy['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();
        $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

        if($academy['visibility_outlet'] == 'Hidden' || (empty($academy['visibility_outlet']) && $academy['product_visibility'] == 'Hidden')){
            $errAll[] = 'Kursus tidak tersedia';
        }

        $itemAcademy = [
            "id_brand" => $brand['id_brand'],
            "id_product" => $academy['id_product'],
            "product_code" => $academy['product_code'],
            "product_name" => $academy['product_name'],
            "product_price" => (int)$academy['product_price'],
            'duration' => 'Durasi '.$academy['product_academy_duration'].' bulan',
            'total_meeting' => (!empty($academy['product_academy_total_meeting'])? $academy['product_academy_total_meeting'].' x Pertemuan @'.$academy['product_academy_hours_meeting'].' jam':''),
            "qty" => 1
        ];

        if(!empty($errAll)){
            $continueCheckOut = false;
        }
        $post['item_academy'] = $itemAcademy;

        $grandTotal = app($this->setting_trx)->grandTotal();
        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                if (gettype($post['sub']) != 'array') {
                    $mes = ['Data Not Valid'];

                    if (isset($post['sub']->original['messages'])) {
                        $mes = $post['sub']->original['messages'];

                        if ($post['sub']->original['messages'] == ['Product academy not found']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Not Found with product '.$post['sub']->original['product']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price product academy not valid']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Not Valid with product '.$post['sub']->original['product']];
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

        if(!empty($errAll)){
            $continueCheckOut = false;
        }

        $result['customer'] = [
            "name" => $user['name'],
            "email" => $user['email'],
            "domicile" => $user['city_name']
        ];
        $result['outlet'] = [
            'id_outlet' => $outlet['id_outlet'],
            'outlet_code' => $outlet['outlet_code'],
            'outlet_name' => $outlet['outlet_name'],
            'outlet_address' => $outlet['outlet_address'],
            'color' => $brand['color_brand']??''
        ];

        $result['item_academy'] = $itemAcademy;
        $result['subtotal'] = $post['subtotal'];
        $post['tax'] = ($outlet['is_tax']/100) * $post['subtotal'];
        $result['tax'] = $post['tax'];
        $result['grandtotal'] = (int)$result['subtotal'] + (int)$post['tax'] ;
        $balance = app($this->balance)->balanceNow($user->id);
        $result['points'] = (int) $balance;
        $result['total_payment'] = $result['grandtotal'];
        $result['cashback'] = 0;

        $settingGetPoint = Configs::where('config_name', 'transaction academy get point')->first()['is_active']??0;
        $result['point_earned'] = null;
        if($settingGetPoint == 1){
            $earnedPoint = app($this->online_trx)->countTranscationPoint($post, $user);
            $result['cashback'] = $earnedPoint['cashback'] ?? 0;
        }

        $result['currency'] = 'Rp';
        $result['complete_profile'] = (empty($user->complete_profile) ?false:true);
        $result['continue_checkout'] = $continueCheckOut;
        $result['payment_detail'] = [];
        $result['point_earned'] = null;

        $result['payment_method'] = [
            ['type' => 'one_time_payment', 'text' => 'One-time Payment'],
            ['type' => 'installment', 'text' => 'Cicilan Bertahap']
        ];
        $fake_request = new Request(['show_all' => 1]);
        $result['available_payment'] = app($this->online_trx)->availablePayment($fake_request)['result'] ?? [];

        $result = app($this->promo_trx)->applyPromoCheckout($result,$post);

        if ($result['cashback']) {
            $result['point_earned'] = [
                'value' => MyHelper::requestNumber($result['cashback'], '_CURRENCY'),
                'text' => MyHelper::setting('cashback_earned_text', 'value', 'Point yang akan didapatkan')
            ];
        }

        $result['payment_detail'][] = [
            'name'          => 'Subtotal:',
            "is_discount"   => 0,
            'amount'        => MyHelper::requestNumber($result['subtotal'],'_CURRENCY')
        ];

        if(!empty($outlet['is_tax'])){
            $result['payment_detail'][] = [
                'name'          => 'Tax:',
                "is_discount"   => 0,
                'amount'        => MyHelper::requestNumber(roun($post['tax']),'_CURRENCY')
            ];
        }

        $paymentDetailPromo = app($this->promo_trx)->paymentDetailPromo($result);
        $result['payment_detail'] = array_merge($result['payment_detail'], $paymentDetailPromo);

        $result['messages_all_title'] = (empty($errAll)? null : 'TRANSAKSI TIDAK DAPAT DILANJUTKAN');
        $result['messages_all'] = (empty($errAll)? null:implode(".", array_unique($errAll)));
        
        if($result['promo_deals']){
            if($result['promo_deals']['is_error']){
                $result['continue_checkout'] = false;
                $result['messages_all_title'] = 'VOUCHER ANDA TIDAK DAPAT DIGUNAKAN';
                $result['messages_all'] = 'Silahkan gunakan voucher yang berlaku atau tidak menggunakan voucher sama sekali.';
            }
        }
        if($result['promo_code']){
            if($result['promo_code']['is_error']){
                $result['continue_checkout'] = false;
                $result['messages_all_title'] = 'PROMO ANDA TIDAK DAPAT DIGUNAKAN';
                $result['messages_all'] = 'Silahkan gunakan promo yang berlaku atau tidak menggunakan promo sama sekali.';
            }
        }


        return MyHelper::checkGet($result);
    }

    public function newTransactionAcademy(Request $request) {
        $post = $request->json()->all();

        if(empty($post['item_academy'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item Academy can not be empty']
            ]);
        }

        $post['payment_method'] = $post['payment_method']??'one_time_payment';
        if($post['payment_method'] == 'installment' && empty($post['installment'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Data installment can not be empty when payment method is installment']
            ]);
        }

        if(!empty($request->user()->id)){
            $user = User::where('id', $request->user()->id)->with('memberships')->first();
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User Not Found']
                ]);
            }
        }

        if($user['complete_profile'] == 0){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Please complete your profile']
            ]);
        }

        if(empty($post['id_outlet'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['ID outlet can not be empty']
            ]);
        }

        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->where('outlet_academy_status', 1)->first();
        if (empty($outlet)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet Not Found']
            ]);
        }

        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
            ->where('id_outlet', $outlet['id_outlet'])->first();

        if(empty($brand)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
        }

        $errAll = [];
        $itemAcademy = $post['item_academy'];
        $academy = Product::leftJoin('product_global_price','product_global_price.id_product','=','products.id_product')
            ->where('products.id_product', $itemAcademy['id_product'])
            ->select('products.*', DB::raw('(CASE
                            WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $outlet['id_outlet'] . ' ) = 1 
                            THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . ' )
                            ELSE product_global_price.product_global_price
                        END) as product_price'))
            ->first();

        if(empty($academy)){
            $errAll[] = 'Kursus tidak tersedia';
        }

        $getProductDetail = ProductDetail::where('id_product', $academy['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();
        $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

        if($academy['visibility_outlet'] == 'Hidden' || (empty($academy['visibility_outlet']) && $academy['product_visibility'] == 'Hidden')){
            $errAll[] = 'Kursus tidak tersedia';
        }

        $itemAcademy = [
            "id_brand" => $brand['id_brand'],
            "id_product" => $academy['id_product'],
            "product_code" => $academy['product_code'],
            "product_name" => $academy['product_name'],
            "product_price" => (int)$academy['product_price'],
            'duration' => $academy['product_academy_duration'],
            'total_meeting' => $academy['product_academy_total_meeting'],
            'hours_meeting' => $academy['product_academy_hours_meeting'],
            "qty" => 1
        ];

        if(!empty($errAll)){
            return response()->json(['status' => 'fail', 'messages' => [implode(".", array_unique($errAll))]]);
        }
        $post['item_academy'] = $itemAcademy;

        $grandTotal = app($this->setting_trx)->grandTotal();
        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                if (gettype($post['sub']) != 'array') {
                    $mes = ['Data Not Valid'];

                    if (isset($post['sub']->original['messages'])) {
                        $mes = $post['sub']->original['messages'];

                        if ($post['sub']->original['messages'] == ['Product academy not found']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Not Found with product '.$post['sub']->original['product']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price product academy not valid']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Not Valid with product '.$post['sub']->original['product']];
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

        if(!empty($errAll)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => [(empty($errAll)? null:implode(".", array_unique($errAll)))]
            ]);
        }

        if (isset($post['transaction_payment_status'])) {
            $post['transaction_payment_status'] = $post['transaction_payment_status'];
        } else {
            $post['transaction_payment_status'] = 'Pending';
        }

        if (count($user['memberships']) > 0) {
            $post['membership_level']    = $user['memberships'][0]['membership_name'];
            $post['membership_promo_id'] = $user['memberships'][0]['benefit_promo_id'];
        } else {
            $post['membership_level']    = null;
            $post['membership_promo_id'] = null;
        }


        $settingGetPoint = Configs::where('config_name', 'transaction academy get point')->first()['is_active']??0;
        if($settingGetPoint == 1) {
            $earnedPoint = app($this->online_trx)->countTranscationPoint($post, $user);
        }
        $cashback = $earnedPoint['cashback'] ?? 0;
        $post['tax'] = ($outlet['is_tax']/100) * $post['subtotal'];

        DB::beginTransaction();
        $id=$request->user()->id;
        $transaction = [
            'id_outlet'                   => $post['id_outlet'],
            'id_user'                     => $id,
            'transaction_date'            => date('Y-m-d H:i:s'),
            'transaction_subtotal'        => $post['subtotal'],
            'transaction_gross'  		  => $post['subtotal'],
            'transaction_tax'             => $post['tax'],
            'transaction_grandtotal'      => (int)$post['subtotal'] + (int)$post['tax'],
            'transaction_cashback_earned' => $cashback,
            'transaction_payment_status'  => $post['transaction_payment_status'],
            'membership_level'            => $post['membership_level'],
            'membership_promo_id'         => $post['membership_promo_id'],
            'trasaction_payment_type'     => ($post['payment_method'] == 'installment'? 'Installment': null),
            'void_date'                   => null,
            'transaction_from'            => $post['transaction_from'],
            'scope'                       => 'apps'
        ];

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if(stristr($useragent,'iOS')) $useragent = 'IOS';
        elseif(stristr($useragent,'okhttp')) $useragent = 'Android';
        else $useragent = null;

        if($useragent){
            $transaction['transaction_device_type'] = $useragent;
        }

        $insertTransaction = Transaction::create($transaction);

        if (!$insertTransaction) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Failed']
            ]);
        }

        $lastReceipt = Transaction::where('id_outlet', $insertTransaction['id_outlet'])->orderBy('transaction_receipt_number', 'desc')->first()['transaction_receipt_number']??'';
        $lastReceipt = substr($lastReceipt, -5);
        $lastReceipt = (int)$lastReceipt;
        $countReciptNumber = $lastReceipt+1;
        $receipt = 'TRX'.substr($outlet['outlet_code'], -4).'-'.sprintf("%05d", $countReciptNumber);
        $updateReceiptNumber = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->update([
            'transaction_receipt_number' => $receipt
        ]);

        if (!$updateReceiptNumber) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Failed']
            ]);
        }
        $insertTransaction['transaction_receipt_number'] = $receipt;

        $dataProduct = [
            'id_transaction'               => $insertTransaction['id_transaction'],
            'id_product'                   => $itemAcademy['id_product'],
            'type'                         => 'Academy',
            'id_outlet'                    => $insertTransaction['id_outlet'],
            'id_brand'                     => $itemAcademy['id_brand'],
            'id_user'                      => $insertTransaction['id_user'],
            'transaction_product_qty'      => $itemAcademy['qty'],
            'transaction_product_price'    => $itemAcademy['product_price'],
            'transaction_product_price_base' => $itemAcademy['product_price'],
            'transaction_product_discount'   => 0,
            'transaction_product_discount_all'   => 0,
            'transaction_product_base_discount' => 0,
            'transaction_product_qty_discount'  => 0,
            'transaction_product_subtotal' => $itemAcademy['product_price'],
            'transaction_product_net'       => $itemAcademy['product_price'],
            'transaction_product_note'     => null,
            'created_at'                   => date('Y-m-d', strtotime($insertTransaction['transaction_date'])).' '.date('H:i:s'),
            'updated_at'                   => date('Y-m-d H:i:s')
        ];

        $trx_product = TransactionProduct::create($dataProduct);
        if (!$trx_product) {
            DB::rollback();
            return [
                'status'    => 'fail',
                'messages'  => ['Insert Product Academy Transaction Failed']
            ];
        }

        $applyPromo = app($this->promo_trx)->applyPromoNewTrx($insertTransaction);
        if ($applyPromo['status'] == 'fail') {
        	DB::rollback();
            return $applyPromo;
        }

        $insertTransaction = $applyPromo['result'] ?? $insertTransaction;

        $createTransactionAcademy = TransactionAcademy::create([
            'id_transaction' => $insertTransaction['id_transaction'],
            'payment_method' => $post['payment_method'],
            'total_installment' => (($post['payment_method'] == 'installment')? count($post['installment']) : null),
            'amount_completed' => 0,
            'amount_not_completed' => $insertTransaction['transaction_grandtotal'],
            'transaction_academy_duration' => $itemAcademy['duration'],
            'transaction_academy_total_meeting' => $itemAcademy['total_meeting'],
            'transaction_academy_hours_meeting' => $itemAcademy['hours_meeting']
        ]);

        if (!$createTransactionAcademy) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Academy Failed']
            ]);
        }

        if($post['payment_method'] == 'installment'){
            $installment = [];
            $checkDP = ($post['installment'][0]['percent']??0) + ($post['installment'][1]['percent']??0);
            if($checkDP < 50){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Uang muka dan tahap 1 minimal 50%']
                ]);
            }

            $sumTotal = array_sum(array_column($post['installment'], 'amount'));
            $sumPercent = array_sum(array_column($post['installment'], 'percent'));
            if($sumTotal != $insertTransaction['transaction_grandtotal']){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Total cicilan tidak sesuai dengan total transaksi']
                ]);
            }

            if($sumPercent != 100){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Total percent tidak 100%']
                ]);
            }

            $settingDeadline = Setting::where('key', 'transaction_academy_installment_deadline_date')->first()['value']??null;
            if(empty($settingDeadline)){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Deadline date is empty']
                ]);
            }

            $startDeadline = date('Y-m-d', strtotime(date('Y-m').'-'.$settingDeadline));
            foreach ($post['installment'] as $key=>$value){
                $installment[] = [
                    'installment_step' => $key+1,
                    'id_transaction_academy' => $createTransactionAcademy['id_transaction_academy'],
                    'installment_receipt_number' => 'TRX'.substr($outlet['outlet_code'], -4).'-'.substr($insertTransaction['transaction_receipt_number'], -5).'-'.sprintf("%02d", ($key+1)),
                    'percent' => $value['percent'],
                    'amount' => $value['amount'],
                    'deadline' => ($key==0 ? date('Y-m-d') : date('Y-m-d', strtotime("+".$key." month", strtotime($startDeadline)))),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            $insertTransactionAcademyinstallment = TransactionAcademyInstallment::insert($installment);
            if(!$insertTransactionAcademyinstallment){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert installment Failed']
                ]);
            }
        }

        $dataDailyTrx = [
            'id_transaction'    => $insertTransaction['id_transaction'],
            'id_outlet'         => $outlet['id_outlet'],
            'transaction_date'  => date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
            'id_user'           => $user['id'],
            'referral_code'     => NULL
        ];
        DailyTransactions::create($dataDailyTrx);
        DB::commit();

        if(!empty($insertTransaction['id_transaction']) && $insertTransaction['transaction_grandtotal'] == 0){
            $trx = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->first();
            optional($trx)->recalculateTaxandMDR();
            $trx->triggerPaymentCompleted();
        }

        $insertTransaction['id_transaction_academy_installment'] = TransactionAcademyInstallment::where('id_transaction_academy', $createTransactionAcademy['id_transaction_academy'])
                                                                    ->where('installment_step', 1)->first()['id_transaction_academy_installment']??null;
        return response()->json([
            'status'   => 'success',
            'result'   => $insertTransaction
        ]);
    }

    public function installmentDetail(Request $request){
        $post = $request->json()->all();
        if(empty($post['grandtotal'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Grandtotal can not be empty']
            ]);
        }

        $data = (array)json_decode(Setting::where('key', 'setting_academy_installment')->first()['value_text']??'');
        if(empty($data)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Setting not found']
            ]);
        }

        $listinstallment = [];
        foreach ($data as $dt){
            $dt = (array)$dt;
            $step = [];
            $allMinimumStep = array_values((array)$dt['step']);
            $sumMinimumStep = array_sum($allMinimumStep);
            $minimumStep = array_count_values($allMinimumStep);
            $getEmptyMinimum = $minimumStep[0]??0;
            if($getEmptyMinimum > 0){
                $diff = (100 - $sumMinimumStep) / $getEmptyMinimum;
            }

            foreach ($dt['step'] as $key=>$value){
                $percent = (empty($value) ? $diff:$value);
                $step[] = [
                    'text' => ($key == 1 ? 'Uang Muka':'Tahap '.($key-1)),
                    'minimum' => (int)$percent,
                    'amount' => ($percent/100) * $post['grandtotal']
                ];
            }

            $listinstallment[] = [
                'text' => $dt['total_installment'].' x',
                'description' => $dt['description'],
                'step' => $step
            ];
        }

        $result = [
            'total_amount' => $post['grandtotal'],
            'list_installment' => $listinstallment
        ];

        $fake_request = new Request(['show_all' => 1]);
        $result['available_payment'] = app($this->online_trx)->availablePayment($fake_request)['result'] ?? [];

        return response()->json(MyHelper::checkGet($result));
    }

    public function listAcademy(Request $request)
    {
        $list = Transaction::where('transaction_from', 'academy')
            ->join('transaction_academy','transactions.id_transaction', 'transaction_academy.id_transaction')
            ->join('users','transactions.id_user','=','users.id')
            ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
            ->leftJoin('transaction_payment_xendits', 'transactions.id_transaction', '=', 'transaction_payment_xendits.id_transaction')
            ->with('user')
            ->select(
                'transaction_academy.*',
                'users.*',
                'transactions.*'
            )
            ->groupBy('transactions.id_transaction');

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $list->count();
            $this->filterList($list, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'id_transaction',
                'transaction_date',
                'transaction_receipt_number',
                'name',
                'phone',
                'transaction_grandtotal',
                'transaction_payment_status',
                'payment_method'
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $list->orderBy($colname, $column['dir']);
                }
            }
        }
        $list->orderBy('transactions.id_transaction', $column['dir'] ?? 'DESC');

        if ($request->page) {
            $list = $list->paginate($request->length ?: 15);
            $list->each(function($item) {
                $item->images = array_map(function($item) {
                    return config('url.storage_url_api').$item;
                }, json_decode($item->images) ?? []);
            });
            $list = $list->toArray();
            if (is_null($countTotal)) {
                $countTotal = $list['total'];
            }
            // needed by datatables
            $list['recordsTotal'] = $countTotal;
            $list['recordsFiltered'] = $list['total'];
        } else {
            $list = $list->get();
        }
        return MyHelper::checkGet($list);
    }

    public function filterList($model, $rule, $operator = 'and')
    {
        $new_rule = [];
        $where    = $operator == 'and' ? 'where' : 'orWhere';
        foreach ($rule as $var) {
            $var1 = ['operator' => $var['operator'] ?? '=', 'parameter' => $var['parameter'] ?? null, 'hide' => $var['hide'] ?? false];
            if ($var1['operator'] == 'like') {
                $var1['parameter'] = '%' . $var1['parameter'] . '%';
            }
            $new_rule[$var['subject']][] = $var1;
        }
        $model->where(function($model2) use ($model, $where, $new_rule){
            $inner = [
                'transaction_receipt_number',
                'transaction_grandtotal',
                'transaction_payment_status',
                'payment_method'
            ];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where($col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['name', 'phone', 'email'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('users.'.$col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['payment'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $explode = explode('-', $rul['parameter']);
                        $paymentGateway = $explode[0];
                        $paymentMethod = $explode[1];
                        if($paymentGateway == 'Cash'){
                            $model2->$where('transactions.trasaction_payment_type', 'Cash');
                        }elseif($paymentGateway == 'Midtrans'){
                            $model2->$where('transaction_payment_midtrans.payment_type',  $paymentMethod);
                        }elseif($paymentGateway == 'Xendit'){
                            $model2->$where('transaction_payment_xendits.type',  $paymentMethod);
                        }
                    }
                }
            }
        });

        if ($rules = $new_rule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->where(\DB::raw('DATE(transaction_date)'), $rul['operator'], $rul['parameter']);
            }
        }
    }

    public function detailTransaction(Request $request)
    {
        if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id = $request->json('id_transaction');
        }

        $trx = Transaction::where(['transactions.id_transaction' => $id])
            ->join('transaction_academy','transaction_academy.id_transaction','=','transactions.id_transaction')
            ->first();

        if(!$trx){
            return MyHelper::checkGet($trx);
        }

        $trxPromo = $this->transactionPromo($trx);

        $trxPayment = $this->transactionPayment($trx);
        $trx['payment'] = $trxPayment['payment'];

        $trx->load('user', 'outlet');
        $result = [
            'id_transaction'                => $trx['id_transaction'],
            'transaction_receipt_number'    => $trx['transaction_receipt_number'],
            'receipt_qrcode' 				=> 'https://chart.googleapis.com/chart?chl=' . $trx['transaction_receipt_number'] . '&chs=250x250&cht=qr&chld=H%7C0',
            'transaction_date'              => date('d M Y H:i', strtotime($trx['transaction_date'])),
            'transaction_grandtotal'        => MyHelper::requestNumber($trx['transaction_grandtotal'],'_CURRENCY'),
            'transaction_subtotal'          => MyHelper::requestNumber($trx['transaction_subtotal'],'_CURRENCY'),
            'transaction_discount'          => MyHelper::requestNumber($trx['transaction_discount'],'_CURRENCY'),
            'transaction_cashback_earned'   => MyHelper::requestNumber($trx['transaction_cashback_earned'],'_POINT'),
            'transaction_tax'               => $trx['transaction_tax'],
            'mdr'                           => $trx['mdr'],
            'trasaction_payment_type'       => $trx['trasaction_payment_type'],
            'trasaction_type'               => $trx['trasaction_type'],
            'transaction_payment_status'    => $trx['transaction_payment_status'],
            'continue_payment'              => $trxPayment['continue_payment'],
            'payment_gateway'               => $trxPayment['payment_gateway'],
            'payment_type'                  => $trxPayment['payment_type'],
            'payment_redirect_url'          => $trxPayment['payment_redirect_url'],
            'payment_redirect_url_app'      => $trxPayment['payment_redirect_url_app'],
            'payment_token'                 => $trxPayment['payment_token'],
            'total_payment'                 => (int) $trxPayment['total_payment'],
            'timer_shopeepay'               => $trxPayment['timer_shopeepay'],
            'message_timeout_shopeepay'     => $trxPayment['message_timeout_shopeepay'],
            'transaction_academy_total_meeting' => $trx['transaction_academy_total_meeting'],
            'outlet'                        => [
                'outlet_name'    => $trx['outlet']['outlet_name'],
                'outlet_address' => $trx['outlet']['outlet_address']
            ],
            'user'							=> [
                'phone' => $trx['user']['phone'],
                'name' 	=> $trx['user']['name'],
                'email' => $trx['user']['email']
            ],

        ];

        $result['product_academy'] =  TransactionProduct::where('id_transaction', $trx['id_transaction'])
            ->join('products', 'products.id_product', 'transaction_products.id_product')->get()->toArray();

        $lastLog = LogInvalidTransaction::where('id_transaction', $trx['id_transaction'])->orderBy('updated_at', 'desc')->first();

        $result['image_invalid_flag'] = NULL;
        if(!empty($trx['image_invalid_flag'])){
            $result['image_invalid_flag'] =  config('url.storage_url_api').$trx['image_invalid_flag'];
        }

        $result['transaction_flag_invalid'] =  $trx['transaction_flag_invalid'];
        $result['flag_reason'] =  $lastLog['reason'] ?? '';
        $result['payment_detail'] = $this->transactionPaymentDetail($trx);

        if($result['payment_detail'] && isset($trxPromo)){
            $lastKey = array_key_last($result['payment_detail']);
            for($i = 0; $i < count($trxPromo); $i++){
                $KeyPosition = 1 + $i;
                $result['payment_detail'][$lastKey+$KeyPosition] = $trxPromo[$i];
            }
        }

        if(!isset($trx['payment'])){
            $result['transaction_payment'] = null;
        }else{
            foreach ($trx['payment'] as $key => $value) {
                if ($value['name'] == 'Balance') {
                    $result['transaction_payment'][$key] = [
                        'name'      => (env('POINT_NAME')) ? env('POINT_NAME') : $value['name'],
                        'is_balance'=> 1,
                        'amount'    => MyHelper::requestNumber($value['amount'],'_POINT')
                    ];
                } else {
                    $result['transaction_payment'][$key] = [
                        'name'      => $value['name'],
                        'amount'    => MyHelper::requestNumber((int)$value['amount'],'_CURRENCY')
                    ];
                }
            }
        }

        return MyHelper::checkGet($result);
    }

    public function transactionPromo(Transaction $trx){
        $trx = clone $trx;
        $promo_discount = [];
        $promos = TransactionPromo::where('id_transaction', $trx['id_transaction'])->get()->toArray();
        if($promos){
            $promo_discount[0]=[
                "name"  => "Promo / Discount:",
                "desc"  => "",
                "is_discount" => 0,
                "amount" => null 
            ];
            foreach($promos as $p => $promo){
                if($promo['promo_type']=='Promo Campaign'){
                    $promo['promo_name'] = PromoCampaign::where('promo_title',$promo['promo_name'])->select('campaign_name')->first()['campaign_name'];
                }
                $promo_discount[$p+1] = [
                    "name"  => $promo['promo_name'],
                    "desc"  => "",
                    "is_discount" => 1,
                    "amount" => '- '.MyHelper::requestNumber($promo['discount_value'],'_CURRENCY')
                ];
            }
        }
        return $promo_discount;
    }

    public function transactionPayment(Transaction $trx)
    {
        $trx = clone $trx;
        $trx = $trx->toArray();
        $redirectUrlApp = "";
        $redirectUrl = "";
        $tokenPayment = "";
        $continuePayment = false;
        $totalPayment = 0;
        $shopeeTimer = 0;
        $shopeeMessage = "";
        $paymentType = "";
        $paymentGateway = "";
        switch ($trx['trasaction_payment_type']) {
            case 'Balance':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get()->toArray();
                if ($multiPayment) {
                    foreach ($multiPayment as $keyMP => $mp) {
                        switch ($mp['type']) {
                            case 'Balance':
                                $log = LogBalance::where('id_reference', $mp['id_transaction'])->where('source', 'Online Transaction')->first();
                                if ($log['balance'] < 0) {
                                    $trx['balance'] = $log['balance'];
                                    $trx['check'] = 'tidak topup';
                                } else {
                                    $trx['balance'] = $trx['transaction_grandtotal'] - $log['balance'];
                                    $trx['check'] = 'topup';
                                }
                                $trx['payment'][] = [
                                    'name'      => 'Balance',
                                    'amount'    => $trx['balance']
                                ];
                                break;
                            case 'Manual':
                                $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $trx['id_transaction'])->first();
                                $trx['payment'] = $payment;
                                $trx['payment'][] = [
                                    'name'      => 'Cash',
                                    'amount'    => $payment['payment_nominal']
                                ];
                                break;
                            case 'Midtrans':
                                $payMidtrans = TransactionPaymentMidtran::find($mp['id_payment']);
                                $payment['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                                $payment['amount']    = $payMidtrans->gross_amount;
                                $trx['payment'][] = $payment;
                                if($trx['transaction_payment_status'] == 'Pending' && !empty($payMidtrans->token)) {
                                    $redirectUrl = $payMidtrans->redirect_url;
                                    $tokenPayment = $payMidtrans->token;
                                    $continuePayment =  true;
                                    $totalPayment = $payMidtrans->gross_amount;
                                    $paymentType = strtoupper($payMidtrans->payment_type);
                                    $paymentGateway = 'Midtrans';
                                }
                                break;
                            case 'Ovo':
                                $payment = TransactionPaymentOvo::find($mp['id_payment']);
                                $payment['name']    = 'OVO';
                                $trx['payment'][] = $payment;
                                break;
                            case 'IPay88':
                                $PayIpay = TransactionPaymentIpay88::find($mp['id_payment']);
                                $payment['name']    = $PayIpay->payment_method;
                                $payment['amount']    = $PayIpay->amount / 100;
                                $trx['payment'][] = $payment;
                                if($trx['transaction_payment_status'] == 'Pending'){
                                    $redirectUrl = config('url.api_url').'/api/ipay88/pay?type=trx&id_reference='.$trx['id_transaction'].'&payment_id='.$PayIpay->payment_id;
                                    $continuePayment =  true;
                                    $totalPayment = $PayIpay->amount / 100;
                                    $paymentType = strtoupper($PayIpay->payment_method);
                                    $paymentGateway = 'IPay88';
                                }
                                break;
                            case 'Shopeepay':
                                $shopeePay = TransactionPaymentShopeePay::find($mp['id_payment']);
                                $payment['name']    = 'ShopeePay';
                                $payment['amount']  = $shopeePay->amount / 100;
                                $payment['reject']  = $shopeePay->err_reason?:'payment expired';
                                $trx['payment'][]  = $payment;
                                if($trx['transaction_payment_status'] == 'Pending'){
                                    $redirectUrl = $shopeePay->redirect_url_http;
                                    $redirectUrlApp = $shopeePay->redirect_url_app;
                                    $continuePayment =  true;
                                    $totalPayment = $shopeePay->amount / 100;
                                    $shopeeTimer = (int) MyHelper::setting('shopeepay_validity_period', 'value', 300);
                                    $shopeeMessage ='Sorry, your payment has expired';
                                    $paymentGateway = 'Shopeepay';
                                }
                                break;
                            case 'Xendit':
                                $payXendit = TransactionPaymentXendit::find($dataPay['id_payment']);
                                $payment[$dataKey]['name']      = $payXendit->type??'';
                                $payment[$dataKey]['amount']    = $payXendit->amount;
                                $payment[$dataKey]['reject']    = $payXendit->err_reason?:'payment expired';
                                if($trx['transaction_payment_status'] == 'Pending') {
                                    $redirectUrl = $payXendit->redirect_url_http;
                                    $redirectUrlApp = $payXendit->redirect_url_app;
                                    $continuePayment =  true;
                                    $totalPayment = $payXendit->amount;
                                    $paymentGateway = 'Xendit';
                                }
                                break;
                            case 'Offline':
                                $payment = TransactionPaymentOffline::where('id_transaction', $trx['id_transaction'])->get();
                                foreach ($payment as $key => $value) {
                                    $trx['payment'][$key] = [
                                        'name'      => $value['payment_bank'],
                                        'amount'    => $value['payment_amount']
                                    ];
                                }
                                break;
                            default:
                                break;
                        }
                    }
                } else {
                    $log = LogBalance::where('id_reference', $trx['id_transaction'])->first();
                    if ($log['balance'] < 0) {
                        $trx['balance'] = $log['balance'];
                        $trx['check'] = 'tidak topup';
                    } else {
                        $trx['balance'] = $trx['transaction_grandtotal'] - $log['balance'];
                        $trx['check'] = 'topup';
                    }
                    $trx['payment'][] = [
                        'name'      => 'Balance',
                        'amount'    => $trx['balance']
                    ];
                }
                break;
            case 'Manual':
                $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $trx['id_transaction'])->first();
                $trx['payment'] = $payment;
                $trx['payment'][] = [
                    'name'      => 'Cash',
                    'amount'    => $payment['payment_nominal']
                ];
                break;
            case 'Midtrans':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Midtrans'){
                        $payMidtrans = TransactionPaymentMidtran::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                        $payment[$dataKey]['amount']    = $payMidtrans->gross_amount;
                        if($trx['transaction_payment_status'] == 'Pending' && !empty($payMidtrans->token)){
                            $redirectUrl = $payMidtrans->redirect_url;
                            $tokenPayment = $payMidtrans->token;
                            $continuePayment =  true;
                            $totalPayment = $payMidtrans->gross_amount;
                            $paymentType = strtoupper($payMidtrans->payment_type);
                            $paymentGateway = 'Midtrans';
                        }

                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $trx['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Ovo':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Ovo'){
                        $payment[$dataKey] = TransactionPaymentOvo::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = 'OVO';
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $trx['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Ipay88':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'IPay88'){
                        $PayIpay = TransactionPaymentIpay88::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = $PayIpay->payment_method;
                        $payment[$dataKey]['amount']    = $PayIpay->amount / 100;

                        if($trx['transaction_payment_status'] == 'Pending'){
                            $redirectUrl = config('url.api_url').'/api/ipay88/pay?type=trx&id_reference='.$trx['id_transaction'].'&payment_id='.$PayIpay->payment_id;
                            $continuePayment =  true;
                            $totalPayment = $PayIpay->amount / 100;
                            $paymentType = strtoupper($PayIpay->payment_method);
                            $paymentGateway = 'Ipay88';
                        }
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $trx['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Shopeepay':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Shopeepay'){
                        $payShopee = TransactionPaymentShopeePay::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = 'ShopeePay';
                        $payment[$dataKey]['amount']    = $payShopee->amount / 100;
                        $payment[$dataKey]['reject']    = $payShopee->err_reason?:'payment expired';
                        if($trx['transaction_payment_status'] == 'Pending') {
                            $redirectUrl = $payShopee->redirect_url_http;
                            $redirectUrlApp = $payShopee->redirect_url_app;
                            $continuePayment =  true;
                            $totalPayment = $payShopee->amount / 100;
                            $shopeeTimer = (int) MyHelper::setting('shopeepay_validity_period', 'value', 300);
                            $shopeeMessage ='Sorry, your payment has expired';
                            $paymentGateway = 'Shopeepay';
                        }
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey]              = $dataPay;
                        $trx['balance']                = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']      = 'Balance';
                        $payment[$dataKey]['amount']    = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Xendit':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Xendit'){
                        $payXendit = TransactionPaymentXendit::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = $payXendit->type??'';
                        $payment[$dataKey]['amount']    = $payXendit->amount ;
                        $payment[$dataKey]['reject']    = $payXendit->err_reason?:'payment expired';
                        if($trx['transaction_payment_status'] == 'Pending') {
                            $redirectUrl = $payXendit->redirect_url_http;
                            $redirectUrlApp = $payXendit->redirect_url_app;
                            $continuePayment =  true;
                            $totalPayment = $payXendit->amount;
                            $paymentGateway = 'Xendit';
                        }
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey]              = $dataPay;
                        $trx['balance']                = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']      = 'Balance';
                        $payment[$dataKey]['amount']    = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Offline':
                $payment = TransactionPaymentOffline::where('id_transaction', $trx['id_transaction'])->get();
                foreach ($payment as $key => $value) {
                    $trx['payment'][$key] = [
                        'name'      => $value['payment_bank'],
                        'amount'    => $value['payment_amount']
                    ];
                }
                break;

            case 'Cash':
                $payment = TransactionPaymentCash::where('id_transaction', $trx['id_transaction'])->first();
                $trx['payment'] = [];
                $trx['payment'][] = [
                    'name'      => 'Cash',
                    'amount'    => $payment['cash_nominal']
                ];
                break;
            case 'Installment':
                $payments = TransactionAcademy::join('transaction_academy_installment', 'transaction_academy_installment.id_transaction_academy', 'transaction_academy.id_transaction_academy')
                            ->where('id_transaction', $trx['id_transaction'])->get()->toArray();

                foreach ($payments as $payment){
                    $getPaymentMethod = TransactionAcademyInstallment::leftJoin('transaction_academy_installment_payment_midtrans as tam', 'tam.id_transaction_academy_installment', 'transaction_academy_installment.id_transaction_academy_installment')
                                        ->leftJoin('transaction_academy_installment_payment_xendits as tax', 'tax.id_transaction_academy_installment', 'transaction_academy_installment.id_transaction_academy_installment')
                                        ->where('transaction_academy_installment.id_transaction_academy_installment', $payment['id_transaction_academy_installment'])
                                        ->select('tax.type', 'tam.payment_type', 'installment_step', 'transaction_academy_installment.amount', 'transaction_academy_installment.paid_status')->orderBy('installment_step', 'asc')->get()->toArray();

                    foreach ($getPaymentMethod as $key=>$paymentMethod){
                        $amount = $paymentMethod['amount'];
                        if(!empty($paymentMethod['type']) && $paymentMethod['paid_status'] == 'Completed'){
                            $trx['payment'][] = [
                                'name'      => ($paymentMethod['installment_step'] == 1 ? 'DP'.' ('.$paymentMethod['type'].')' : 'Tahap '.($paymentMethod['installment_step']-1).' ('.$paymentMethod['type'].')'),
                                'amount'    => $amount
                            ];
                        }elseif(!empty($paymentMethod['payment_type']) && $paymentMethod['paid_status'] == 'Completed'){
                            $trx['payment'][] = [
                                'name'      => ($paymentMethod['installment_step'] == 1 ? 'DP'.' ('.$paymentMethod['payment_type'].')' : 'Tahap '.($paymentMethod['installment_step']-1).' ('.$paymentMethod['payment_type'].')'),
                                'amount'    => $amount
                            ];
                        }else{
                            $trx['payment'][] = [
                                'name'      => ($paymentMethod['installment_step'] == 1 ? 'DP'.' (Not Yet Paid)' : 'Tahap '.($paymentMethod['installment_step']-1).' (Not Yet Paid)'),
                                'amount'    => $amount
                            ];
                        }
                    }
                }
                break;
            default:
                break;
        }

        $res = [
            'payment' 					=> $trx['payment'] ?? [],
            'continue_payment'          => $continuePayment,
            'payment_gateway'           => $paymentGateway,
            'payment_type'              => $paymentType,
            'payment_redirect_url'      => $redirectUrl,
            'payment_redirect_url_app'  => $redirectUrlApp,
            'payment_token'             => $tokenPayment,
            'total_payment'             => (int)$totalPayment,
            'timer_shopeepay'           => $shopeeTimer,
            'message_timeout_shopeepay' => $shopeeMessage,
        ];

        return $res;
    }

    public function transactionPaymentDetail(Transaction $trx)
    {
        $trx = clone $trx;
        $trx->load(
            'transaction_vouchers.deals_voucher.deal',
            'promo_campaign_promo_code.promo_campaign',
            'transaction_payment_subscription.subscription_user_voucher',
            'subscription_user_voucher'
        );

        $paymentDetail = [];
        $totalItem = $trx['transaction_item_service_total'];
        $paymentDetail[] = [
            'name'      => 'Subtotal',
            'desc'      => $totalItem . ' items',
            'amount'    => MyHelper::requestNumber($trx['transaction_subtotal'],'_CURRENCY')
        ];

        // if ($trx['transaction_discount']) {
        //     $discount = abs($trx['transaction_discount']);
        //     $p = 0;
        //     if (!empty($trx['transaction_vouchers'])) {
        //         foreach ($trx['transaction_vouchers'] as $valueVoc) {
        //             $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
        //             $paymentDetail[] = [
        //                 'name'          => 'Diskon',
        //                 'desc'          => 'Promo',
        //                 "is_discount"   => 1,
        //                 'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
        //             ];
        //         }
        //     }

        //     if (!empty($trx['promo_campaign_promo_code'])) {
        //         $result['promo']['code'][$p++]   = $trx['promo_campaign_promo_code']['promo_code'];
        //         $paymentDetail[] = [
        //             'name'          => 'Diskon',
        //             'desc'          => 'Promo',
        //             "is_discount"   => 1,
        //             'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
        //         ];
        //     }

        //     if (!empty($trx['id_subscription_user_voucher']) && !empty($trx['transaction_discount'])) {
        //         $paymentDetail[] = [
        //             'name'          => 'Subscription',
        //             'desc'          => 'Diskon',
        //             "is_discount"   => 1,
        //             'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
        //         ];
        //     }
        // }

        return $paymentDetail;
    }

    public function cancelTransaction(Request $request)
    {
        $trx = TransactionAcademyInstallment::where(['installment_receipt_number' => $request->receipt_number])->first();

        if($trx->paid_status != 'Pending'){
            return [
                'status'=>'fail',
                'messages' => ['Transaksi tidak dapat dibatalkan.']
            ];
        }

        $payment_type = $trx->installment_payment_type;

        switch (strtolower($payment_type)) {
            case 'midtrans':
                $midtransStatus = Midtrans::status($trx->installment_receipt_number);
                if ((($midtransStatus['status'] ?? false) == 'fail' && ($midtransStatus['messages'][0] ?? false) == 'Midtrans payment not found') || in_array(($midtransStatus['response']['transaction_status'] ?? false), ['deny', 'cancel', 'expire', 'failure']) || ($midtransStatus['status_code'] ?? false) == '404' ||
                    (!empty($midtransStatus['payment_type']) && $midtransStatus['payment_type'] == 'gopay' && $midtransStatus['transaction_status'] == 'pending')) {
                    $connectMidtrans = Midtrans::expire($trx->installment_receipt_number);

                    if($connectMidtrans){
                        $trx->triggerPaymentCancelled();
                        return ['status'=>'success', 'result' => ['message' => 'Pembayaran berhasil dibatalkan']];
                    }
                }
            case 'xendit':
                $dtXendit = TransactionAcademyInstallmentPaymentXendit::where('order_id', $trx->installment_receipt_number)->first();
                $status = app('Modules\Xendit\Http\Controllers\XenditController')->checkStatus($dtXendit->xendit_id, $dtXendit->type);

                if ($status && $status['status'] == 'PENDING' && !empty($status['id'])) {
                    $cancel = app('Modules\Xendit\Http\Controllers\XenditController')->expireInvoice($status['id']);

                    if($cancel){
                        $trx->triggerPaymentCancelled();
                        return ['status'=>'success', 'result' => ['message' => 'Pembayaran berhasil dibatalkan']];
                    }
                }
                return [
                    'status'=>'fail',
                    'messages' => ['Transaksi tidak dapat dibatalkan karena proses pembayaran sedang berlangsung']
                ];
        }
        return ['status' => 'fail', 'messages' => ["Cancel $payment_type transaction is not supported yet"]];
    }
}
