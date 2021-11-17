<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Brand\Entities\Brand;
use Modules\Product\Entities\ProductDetail;
use App\Http\Models\TransactionPayment;
use App\Http\Models\User;
use App\Http\Models\Product;
use App\Http\Models\StockLog;
use DB;
use App\Http\Models\Configs;
use Modules\Transaction\Entities\TransactionAcademy;
use Modules\Transaction\Entities\TransactionAcademyInstallment;
use Modules\UserFeedback\Entities\UserFeedbackLog;

class ApiTransactionAcademy extends Controller
{
    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->product      = "Modules\Product\Http\Controllers\ApiProductController";
        $this->online_trx      = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->transaction   = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
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
            } elseif($valueTotal == 'tax'){
                $post['tax'] = app($this->setting_trx)->countTransaction($valueTotal, $post);

                if (isset($post['tax']->original['messages'])) {
                    $mes = $post['tax']->original['messages'];

                    if ($post['tax']->original['messages'] == ['Product Service not found']) {
                        if (isset($post['tax']->original['product'])) {
                            $mes = ['Price Not Found with product '.$post['tax']->original['product']];
                        }
                    }

                    if ($post['sub']->original['messages'] == ['Price Service Product Not Valid']) {
                        if (isset($post['tax']->original['product'])) {
                            $mes = ['Price Not Valid with product '.$post['tax']->original['product']];
                        }
                    }

                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }
            }
            else {
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
            'outlet_address' => $outlet['outlet_address']
        ];

        $result['item_academy'] = $itemAcademy;
        $result['subtotal'] = $post['subtotal'];
        $result['tax'] = (int) $post['tax'];
        $result['grandtotal'] = (int)$result['subtotal'] + (int)$post['tax'] ;
        $balance = app($this->balance)->balanceNow($user->id);
        $result['points'] = (int) $balance;
        $result['total_payment'] = $result['grandtotal'];

        $settingGetPoint = Configs::where('config_name', 'transaction academy get point')->first()['is_active']??0;
        $result['point_earned'] = null;
        if($settingGetPoint == 1){
            $earnedPoint = app($this->online_trx)->countTranscationPoint($post, $user);
            $cashback = $earnedPoint['cashback'] ?? 0;
            if ($cashback) {
                $result['point_earned'] = [
                    'value' => MyHelper::requestNumber($cashback, '_CURRENCY'),
                    'text' => MyHelper::setting('cashback_earned_text', 'value', 'Point yang akan didapatkan')
                ];
            }
        }

        $result['currency'] = 'Rp';
        $result['complete_profile'] = (empty($user->complete_profile) ?false:true);
        $result['continue_checkout'] = $continueCheckOut;
        $result['payment_method'] = [
            ['type' => 'one_time_payment', 'text' => 'One-time Payment'],
            ['type' => 'installment', 'text' => 'Cicilan Bertahap']
        ];
        $result['messages_all'] = (empty($errAll)? null:implode(".", array_unique($errAll)));
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
            'duration' => 'Durasi '.$academy['product_academy_duration'].' bulan',
            'total_meeting' => (!empty($academy['product_academy_total_meeting'])? $academy['product_academy_total_meeting'].' x Pertemuan @'.$academy['product_academy_hours_meeting'].' jam':''),
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
            } elseif($valueTotal == 'tax'){
                $post['tax'] = app($this->setting_trx)->countTransaction($valueTotal, $post);

                if (isset($post['tax']->original['messages'])) {
                    $mes = $post['tax']->original['messages'];

                    if ($post['tax']->original['messages'] == ['Product Service not found']) {
                        if (isset($post['tax']->original['product'])) {
                            $mes = ['Price Not Found with product '.$post['tax']->original['product']];
                        }
                    }

                    if ($post['sub']->original['messages'] == ['Price Service Product Not Valid']) {
                        if (isset($post['tax']->original['product'])) {
                            $mes = ['Price Not Valid with product '.$post['tax']->original['product']];
                        }
                    }

                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }
            }
            else {
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

        DB::beginTransaction();
        UserFeedbackLog::where('id_user',$request->user()->id)->delete();
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

        $receipt = config('configs.PREFIX_TRANSACTION_NUMBER').'-'.MyHelper::createrandom(4,'Angka').time().substr($insertTransaction['id_outlet'], 0, 4);
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

        $createTransactionAcademy = TransactionAcademy::create([
            'id_transaction' => $insertTransaction['id_transaction'],
            'payment_method' => $post['payment_method'],
            'total_installment' => (($post['payment_method'] == 'installment')? count($post['installment']) : null),
            'amount_completed' => 0,
            'amount_not_completed' => $insertTransaction['transaction_grandtotal']
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
            $sumTotal = array_sum(array_column($post['installment'], 'amount'));
            $sumPercent = array_sum(array_column($post['installment'], 'percent'));
            if($sumTotal != $insertTransaction['transaction_grandtotal']){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Total installment does not match with grand total']
                ]);
            }

            if($sumPercent != 100){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Invalid Total Percent Installment']
                ]);
            }

            foreach ($post['installment'] as $value){
                $installment[] = [
                    'id_transaction_academy' => $createTransactionAcademy['id_transaction_academy'],
                    'percent' => $value['percent'],
                    'amount' => $value['amount'],
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
        DB::commit();
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
        $resinstallment = [];
        foreach ($data as $dt){
            $dt = (array)$dt;
            $listinstallment[] = [
                'key' => $dt['total_installment'],
                'text' => $dt['total_installment'].' x'
            ];
            $step = [];
            $allMinimumStep = array_values((array)$dt['step']);
            $sumMinimumStep = array_sum($allMinimumStep);
            $minimumStep = array_count_values($allMinimumStep);
            $getEmptyMinimum = $minimumStep[0];
            if($getEmptyMinimum > 0){
                $diff = (100 - $sumMinimumStep) / $getEmptyMinimum;
            }

            foreach ($dt['step'] as $key=>$value){
                $percent = (empty($value) ? $diff:$value);
                $step[] = [
                    'text' => 'Tahap '.$key,
                    'minimum' => $percent,
                    'amount' => ($percent/100) * $post['grandtotal']
                ];
            }

            $resinstallment[$dt['total_installment']] = [
                'description' => $dt['description'],
                'step' => $step
            ];
        }

        $result = [
            'total_amount' => $post['grandtotal'],
            'list_installment' => $listinstallment,
            'detail_installment' => $resinstallment
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function listHomeService(Request $request)
    {
        $list = Transaction::where('transaction_from', 'academy')
            ->join('transaction_academy','transactions.id_transaction', 'transaction_academy.id_transaction')
            ->join('users','transactions.id_user','=','users.id')
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
        });

        if ($rules = $new_rule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->where(\DB::raw('DATE(transaction_date)'), $rul['operator'], $rul['parameter']);
            }
        }
    }
}
