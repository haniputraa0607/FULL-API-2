<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Queue;
use App\Lib\Midtrans;

use App\Lib\MyHelper;
use App\Lib\PushNotificationHelper;
use App\Lib\classTexterSMS;
use App\Lib\classMaskingJson;
use App\Lib\apiwha;
use Validator;
use Hash;
use DB;
use Mail;
use Mailgun;

use App\Jobs\CronBalance;

use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\User;
use App\Http\Models\LogBalance;
use App\Http\Models\AutocrmEmailLog;
use App\Http\Models\Autocrm;
use App\Http\Models\Setting;
use App\Http\Models\LogPoint;

class ApiCronTrxController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        ini_set('max_execution_time', 600);
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->balance  = "Modules\Balance\Http\Controllers\BalanceController";
    }

    public function cron(Request $request)
    {
        $crossLine = date('Y-m-d H:i:s', strtotime('- 3days'));
        $dateLine  = date('Y-m-d H:i:s', strtotime('- 1days'));
        $now       = date('Y-m-d H:i:s');

        $getTrx = Transaction::where('transaction_payment_status', 'Pending')->where('created_at', '>=', $crossLine)->where('created_at', '<=', $now)->get();

        if (empty($getTrx)) {
            return response()->json(['empty']);
        }

        foreach ($getTrx as $key => $value) {
            $singleTrx = Transaction::where('id_transaction', $value->id_transaction)->first();
            if (empty($singleTrx)) {
                continue;
            }

            $expired_at = date('Y-m-d H:i:s', strtotime('+ 1days', strtotime($singleTrx->transaction_date)));

            if ($expired_at >= $now) {
                continue;
            }

            $productTrx = TransactionProduct::where('id_transaction', $singleTrx->id_transaction)->get();
            if (empty($productTrx)) {
                continue;
            }

            $user = User::where('id', $singleTrx->id_user)->first();
            if (empty($user)) {
                continue;
            }

            $connectMidtrans = Midtrans::expire($singleTrx->transaction_receipt_number);
            // $detail = $this->getHtml($singleTrx, $productTrx, $user->name, $user->phone, $singleTrx->created_at, $singleTrx->transaction_receipt_number);

            // $autoCrm = app($this->autocrm)->SendAutoCRM('Transaction Online Cancel', $user->phone, ['date' => $singleTrx->created_at, 'status' => $singleTrx->transaction_payment_status, 'name'  => $user->name, 'id' => $singleTrx->transaction_receipt_number, 'receipt' => $detail, 'id_reference' => $singleTrx->transaction_receipt_number]);
            // if (!$autoCrm) {
            //     continue;
            // }

            $singleTrx->transaction_payment_status = 'Cancelled';
            $singleTrx->save();
            if (!$singleTrx) {
                continue;
            }

            //reversal balance
            $logBalance = LogBalance::where('id_reference', $singleTrx->id_transaction)->where('source', 'Transaction')->where('balance', '<', 0)->get();
            foreach($logBalance as $logB){
                $reversal = app($this->balance)->addLogBalance( $singleTrx->id_user, abs($logB['balance']), $singleTrx->id_transaction, 'Reversal', $singleTrx->transaction_grandtotal);
            }
        }

        return response()->json(['success']);
    }
    
    public function checkSchedule()
    {
        $result = [];

        $data = LogBalance::orderBy('id_log_balance', 'DESC')->whereNotNull('enc')->get()->toArray();

        foreach ($data as $key => $val) {
            $dataHash = [
                'id_log_balance'                 => $val['id_log_balance'],
                'id_user'                        => $val['id_user'],
                'balance'                        => $val['balance'],
                'balance_before'                 => $val['balance_before'],
                'balance_after'                  => $val['balance_after'],
                'id_reference'                   => $val['id_reference'],
                'source'                         => $val['source'],
                'grand_total'                    => $val['grand_total'],
                'ccashback_conversion'           => $val['ccashback_conversion'],
                'membership_level'               => $val['membership_level'],
                'membership_cashback_percentage' => $val['membership_cashback_percentage']
            ];


            $encodeCheck = json_encode($dataHash);
            if (MyHelper::decryptkhususnew($val['enc']) != $encodeCheck) {
                $result[] = $val;
            }
        }

        if (!empty($result)) {
            $crm = Autocrm::where('autocrm_title','=','Cron Transaction')->with('whatsapp_content')->first();
            if (!empty($crm)) {
                if(!empty($crm['autocrm_forward_email'])){
                    $exparr = explode(';',str_replace(',',';',$crm['autocrm_forward_email']));
                    foreach($exparr as $email){
                        $n   = explode('@',$email);
                        $name = $n[0];
                        
                        $to      = $email;
                        
                        $content = $crm['autocrm_forward_email_content'];

                        $content .= $this->html($result);

                        // get setting email
                        $getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();
                        $setting = array();
                        foreach ($getSetting as $key => $value) {
                            $setting[$value['key']] = $value['value']; 
                        }

                        $subject = $crm['autocrm_forward_email_subject'];

                        $data = array(
                            'customer'     => $name,
                            'html_message' => $content,
                            'setting'      => $setting
                        );
                        
                        Mailgun::send('emails.test', $data, function($message) use ($to,$subject,$name,$setting)
                        {
                            $message->to($to, $name)->subject($subject)
                                            ->trackClicks(true)
                                            ->trackOpens(true);
                            if(!empty($setting['email_from']) && !empty($setting['email_sender'])){
                                $message->from($setting['email_from'], $setting['email_sender']);
                            }else if(!empty($setting['email_from'])){
                                $message->from($setting['email_from']);
                            }

                            if(!empty($setting['email_reply_to'])){
                                $message->replyTo($setting['email_reply_to'], $setting['email_reply_to_name']);
                            }

                            if(!empty($setting['email_cc']) && !empty($setting['email_cc_name'])){
                                $message->cc($setting['email_cc'], $setting['email_cc_name']);
                            }

                            if(!empty($setting['email_bcc']) && !empty($setting['email_bcc_name'])){
                                $message->bcc($setting['email_bcc'], $setting['email_bcc_name']);
                            }
                        });
                        
                        // $logData = [];
                        // $logData['id_user'] = 999999999;
                        // $logData['email_log_to'] = $email;
                        // $logData['email_log_subject'] = $subject;
                        // $logData['email_log_message'] = $content;
                        
                        // $logs = AutocrmEmailLog::create($logData);
                    }
                }
            }
        }

        return $result;
    }

    public function html($data)
    {
        $label = '';
        foreach ($data as $key => $value) {
            $user = User::where('id', $value['id_user'])->first();
            if ($value['source'] == 'Transaction' || $value['source'] == 'Rejected Order' || $value['source'] == 'Reverse Point from Rejected Order') {
                $detail = Transaction::with('outlet', 'transaction_pickup')->where('id_transaction', $value['id_reference'])->first();

                $label .= '<tr>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$user['name'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['source'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.date('Y-m-d', strtotime($detail['created_at'])).'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$detail['transaction_receipt_number'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$detail['transaction_pickup']['order_id'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$detail['outlet']['outlet_name'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['balance'].'</td>
  </tr>';
            } else {
                $label .= '<tr>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$user['name'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['source'].'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.date('Y-m-d', strtotime($value['created_at'])).'</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">-</td>
    <td style="border: 1px solid #dddddd;text-align: left;padding: 8px;">'.$value['balance'].'</td>
  </tr>';
            }
        }
        return '<table style="font-family: arial, sans-serif;border-collapse: collapse;width: 100%;border: 1px solid #dddddd;">
  <tr>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Name</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Type</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Date</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Receipt Number</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Order ID</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Outlet</th>
    <th style="border: 1px solid #dddddd;text-align: left;padding: 8px;">Point</th>
  </tr>
  '.$label.'
</table>';
    }

    public function completeTransactionPickup(){
        $idTrx = Transaction::whereDate('transaction_date', '<', date('Y-m-d'))->pluck('id_transaction')->toArray();
        //update ready_at
        $dataTrx = TransactionPickup::whereIn('id_transaction', $idTrx)
                                    ->whereNull('ready_at')
                                    ->whereNull('reject_at')
                                    ->update(['ready_at' => date('Y-m-d 00:00:00')]);
        //update receive_at
        $dataTrx = TransactionPickup::whereIn('id_transaction', $idTrx)
                                    ->whereNull('receive_at')
                                    ->whereNull('reject_at')
                                    ->update(['receive_at' => date('Y-m-d 00:00:00')]);
        //update taken_at                           
        $dataTrx = TransactionPickup::whereIn('id_transaction', $idTrx)
                                    ->whereNull('taken_at')
                                    ->whereNull('reject_at')
                                    ->update(['taken_at' => date('Y-m-d 00:00:00')]);

        return response()->json(['status' => 'success']);

    }
}
