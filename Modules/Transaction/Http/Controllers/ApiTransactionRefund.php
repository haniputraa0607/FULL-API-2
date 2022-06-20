<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionPayment;
use App\Http\Models\User;
use App\Http\Models\StockLog;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\Outlet;
use App\Http\Models\TransactionPaymentMidtran;
use Illuminate\Routing\Controller;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Http\Requests\RuleUpdate;

use Modules\Transaction\Http\Requests\TransactionNew;
use App\Lib\MyHelper;
use App\Lib\GoSend;
use App\Lib\Midtrans;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use Validator;
use Hash;
use DB;
use Mail;
use Image;

class ApiTransactionRefund extends Controller
{
    public $saveImage = "img/transaction/manual-payment/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->xendit         = 'Modules\Xendit\Http\Controllers\XenditController';
        $this->trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->balance = "Modules\Balance\Http\Controllers\BalanceController";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function refundPayment($trx)
    {
        $order = Transaction::where('transactions.id_transaction', $trx['id_transaction'])
            ->leftJoin('users', 'transactions.id_user', 'users.id')
            ->first();

        $user = User::find($trx['id_user']);
        $shared = \App\Lib\TemporaryDataManager::create('reject_order');
        $refund_failed_process_balance = MyHelper::setting('refund_failed_process_balance');
        $rejectBalance = false;
        $point = 0;

        $multiple = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get()->toArray();

        foreach ($multiple as $pay) {
            if ($pay['type'] == 'Balance') {
                $payBalance = TransactionPaymentBalance::find($pay['id_payment']);
                if ($payBalance) {
                    $refund = app($this->balance)->addLogBalance($trx['id_user'], $point = $payBalance['balance_nominal'], $order['id_transaction'], 'Rejected Order Point', ($trx['refundnominal']??$order['transaction_grandtotal']));
                    if ($refund == false) {
                        return [
                            'status'   => 'fail',
                            'messages' => ['Insert Cashback Failed'],
                        ];
                    }
                    $rejectBalance = true;
                }
            } elseif (strtolower($pay['type']) == 'cash') {
                $payCash = TransactionPaymentCash::find($pay['id_payment']);
                if ($payCash) {
                    $order->update([
                        'refund_requirement' => $trx['refundnominal']??$payCash['cash_nominal'],
                        'reject_type' => 'refund',
                        'need_manual_void' => 1
                    ]);
                }
            } elseif (strtolower($pay['type']) == 'xendit') {
                $point = 0;
                $payXendit = TransactionPaymentXendit::find($pay['id_payment']);
                if ($payXendit) {
                    $doRefundPayment = MyHelper::setting('refund_xendit');
                    $amountXendit = $trx['refundnominal']??($payXendit['amount']/100);
                    if($doRefundPayment){
                        $ewallets = ["OVO","DANA","LINKAJA","SHOPEEPAY","SAKUKU"];
                        if(in_array(strtoupper($payXendit['type']), $ewallets)){
                            if(!empty($trx['refundnominal'])){
                                $refund = app($this->xendit)->refund($payXendit['id_transaction'], 'trx', [
                                    'amount' => $trx['refundnominal'],
                                    'reason' => $trx['reject_reason']
                                ], $errors);
                            }else{
                                $refund = app($this->xendit)->refund($payXendit['id_transaction'], 'trx', [], $errors);
                            }

                            $order->update([
                                'reject_type'   => 'refund',
                            ]);
                            if (!$refund) {
                                $order->update(['failed_void_reason' => implode(', ', $errors ?: [])]);
                                if ($refund_failed_process_balance) {
                                    $doRefundPayment = false;
                                } else {
                                    $order->update(['need_manual_void' => 1, 'refund_requirement' => $amountXendit]);
                                    $order2 = clone $order;
                                    $order2->payment_method = 'Xendit';
                                    $order2->manual_refund = $payXendit['amount'];
                                    $order2->payment_reference_number = $payXendit['xendit_id'];
                                    if ($shared['reject_batch'] ?? false) {
                                        $shared['void_failed'][] = $order2;
                                    } else {
                                        $variables = [
                                            'detail' => view('emails.failed_refund', ['transaction' => $order2])->render()
                                        ];
                                        app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('Payment Void Failed', $order->phone, $variables, null, true);
                                    }
                                }
                            }
                        }else{
                            $order->update([
                                'refund_requirement' => $amountXendit,
                                'reject_type' => 'refund',
                                'need_manual_void' => 1
                            ]);
                        }
                    }

                    // don't use elseif / else because in the if block there are conditions that should be included in this process too
                    if (!$doRefundPayment) {
                        $order->update([
                            'reject_type'   => 'point',
                        ]);
                        $refund = app($this->balance)->addLogBalance($order['id_user'], $point = ($payXendit['amount']/100), $order['id_transaction'], 'Rejected Order', $order['transaction_grandtotal']);
                        if ($refund == false) {
                            return [
                                'status'   => 'fail',
                                'messages' => ['Insert Cashback Failed'],
                            ];
                        }
                        $rejectBalance = true;
                    }
                }
            } else {
                $point = 0;
                $payMidtrans = TransactionPaymentMidtran::find($pay['id_payment']);

                if ($payMidtrans) {
                    $doRefundPayment = MyHelper::setting('refund_midtrans');
                    if ($doRefundPayment) {
                        $order->update([
                            'refund_requirement' => $trx['refundnominal']??$payMidtrans['gross_amount'],
                            'reject_type' => 'refund',
                            'need_manual_void' => 1
                        ]);
                    }

                    // don't use elseif / else because in the if block there are conditions that should be included in this process too
                    if (!$doRefundPayment) {
                        $order->update([
                            'reject_type'   => 'point',
                        ]);
                        $refund = app($this->balance)->addLogBalance( $order['id_user'], $point = $payMidtrans['gross_amount'], $order['id_transaction'], 'Rejected Order Midtrans', $order['transaction_grandtotal']);
                        if ($refund == false) {
                            return [
                                'status'    => 'fail',
                                'messages'  => ['Insert Cashback Failed']
                            ];
                        }
                        $rejectBalance = true;
                    }
                }
            }

        }

        //send notif point refund
        if($rejectBalance == true){
            $outlet = Outlet::find($trx['id_outlet']);
            $send = app($this->autocrm)->SendAutoCRM('Rejected Order Point Refund', $user['phone'],
                [
                    "outlet_name"      => $outlet['outlet_name'],
                    "transaction_date" => $order['transaction_date'],
                    'id_transaction'   => $order['id_transaction'],
                    'receipt_number'   => $order['transaction_receipt_number'],
                    'received_point'   => (string) $point,
                    'order_id'         => $order->order_id,
                ]);
            if ($send != true) {
                return [
                    'status'   => 'fail',
                    'messages' => ['Failed Send notification to customer'],
                ];
            }
        }

        return [
            'status' => 'success',
            'reject_balance' => $rejectBalance,
            'received_point' => (string) $point
        ];
    }

    public function refundNotFullPayment($id_transaction)
    {
        $transaction = Transaction::where('id_transaction', $id_transaction)->first();
        $nominalRefund = 0;

        if($transaction['transaction_from'] == 'outlet-service'){
            $products = TransactionProduct::where('id_transaction', $id_transaction)->get()->toArray();

            foreach ($products as $product){
                if(empty($product['transaction_product_completed_at']) && empty($product['reject_at'])){
                    break;
                }

                if(!empty($product['reject_at'])){
                    $nominalRefund = $nominalRefund + ($product['transaction_product_subtotal'] - $product['transaction_product_discount_all']);
                }
            }

            if($nominalRefund > 0){
                $transaction['refundnominal'] = $nominalRefund;
                $this->refundPayment($transaction);
            }
        }

        return ['status' => 'success'];
    }
}
