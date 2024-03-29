<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\Treatment;
use App\Http\Models\Consultation;
use App\Http\Models\Outlet;
use App\Http\Models\LogPoint;
use App\Http\Models\Reservation;
use App\Http\Models\LogActivitiesApps;
use App\Http\Models\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Report\Http\Requests\DetailReport;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;


class ApiReportWehelpyou extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }
	
    public function getReport(Request $request){
        $post = $request->json()->all();

        $data = Transaction::join('outlets','outlets.id_outlet', 'transactions.id_outlet')
            ->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
            ->join('transaction_pickup_wehelpyous', 'transaction_pickup_wehelpyous.id_transaction_pickup', 'transaction_pickups.id_transaction_pickup')
            ->where('transactions.transaction_payment_status','Completed')
            ->orderBy('transactions.transaction_date', 'desc');

        if (!empty($post['date_start']) && !empty($post['date_end'])) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data = $data->whereDate('transactions.transaction_date', '>=', $start_date)
                ->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $checkFilterStatus = array_search('status', array_column($post['conditions'], 'subject'));
            if($checkFilterStatus === false){
                $data = $data->where('transaction_pickup_wehelpyous.latest_status_id', '2');
            }

            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'status'){
                            $data = $data->where('transaction_pickup_wehelpyous.latest_status_id', $row['operator']);
                        }

                        if($row['subject'] == 'outlet_code'){
                            if($row['operator'] == '='){
                                $data = $data->where('outlets.outlet_code', $row['parameter']);
                            }else{
                                $data = $data->where('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'outlet_name'){
                            if($row['operator'] == '='){
                                $data = $data->where('outlets.outlet_name', $row['parameter']);
                            }else{
                                $data = $data->where('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'transaction_receipt_number'){
                            if($row['operator'] == '='){
                                $data = $data->where('transactions.transaction_receipt_number', $row['parameter']);
                            }else{
                                $data = $data->where('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'order_id'){
                            if($row['operator'] == '='){
                                $data = $data->where('transaction_pickups.order_id', $row['parameter']);
                            }else{
                                $data = $data->where('transaction_pickups.order_id', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'destination_name'){
                            if($row['operator'] == '='){
                                $data = $data->where('transaction_pickup_wehelpyous.receiver_name', $row['parameter']);
                            }else{
                                $data = $data->where('transaction_pickup_wehelpyous.receiver_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'destination_phone'){
                            if($row['operator'] == '='){
                                $data = $data->where('transaction_pickup_wehelpyous.receiver_phone', $row['parameter']);
                            }else{
                                $data = $data->where('transaction_pickup_wehelpyous.receiver_phone', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'driver_name'){
                            if($row['operator'] == '='){
                                $data = $data->where('transaction_pickup_wehelpyous.tracking_driver_name', $row['parameter']);
                            }else{
                                $data = $data->where('transaction_pickup_wehelpyous.tracking_driver_name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'driver_phone'){
                            if($row['operator'] == '='){
                                $data = $data->where('transaction_pickup_wehelpyous.tracking_driver_phone', $row['parameter']);
                            }else{
                                $data = $data->where('transaction_pickup_wehelpyous.tracking_driver_phone', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'transaction_shipment'){
                            $data = $data->where('transactions.transaction_shipment',$row['operator'] ,$row['parameter']);
                        }

                        if($row['subject'] == 'transaction_grandtotal'){
                            $data = $data->where('transactions.transaction_grandtotal',$row['operator'] ,$row['parameter']);
                        }
                    }
                }
            }else{
                $data = $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'status'){
                                $subquery->orWhere('transaction_pickup_wehelpyous.latest_status_id', $row['operator']);
                            }

                            if($row['subject'] == 'outlet_code'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_code', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_code', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'outlet_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('outlets.outlet_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('outlets.outlet_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'transaction_receipt_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transactions.transaction_receipt_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transactions.transaction_receipt_number', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'order_id'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transaction_pickups.order_id', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transaction_pickups.order_id', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'destination_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transaction_pickup_wehelpyous.receiver_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transaction_pickup_wehelpyous.receiver_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'destination_phone'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transaction_pickup_wehelpyous.receiver_phone', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transaction_pickup_wehelpyous.receiver_phone', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'driver_name'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transaction_pickup_wehelpyous.tracking_driver_name', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transaction_pickup_wehelpyous.tracking_driver_name', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'driver_phone'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('transaction_pickup_wehelpyous.tracking_driver_phone', $row['parameter']);
                                }else{
                                    $subquery->orWhere('transaction_pickup_wehelpyous.tracking_driver_phone', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'transaction_shipment'){
                                $subquery->orWhere('transactions.transaction_shipment',$row['operator'] ,$row['parameter']);
                            }

                            if($row['subject'] == 'transaction_grandtotal'){
                                $subquery->orWhere('transactions.transaction_grandtotal',$row['operator'] ,$row['parameter']);
                            }
                        }
                    }
                });
            }
        }else{
            $data = $data->where('transaction_pickup_wehelpyous.latest_status_id', '2');
        }

        if(isset($post['export']) && $post['export'] == 1){
            $data = $data->select('outlets.outlet_name as Oultet Name', 'outlets.outlet_code as Oultet Code', 'transactions.transaction_date as Transactin Date',
                'transactions.transaction_receipt_number as Receipt Number',
                'transaction_pickups.order_id as Order ID',
                DB::raw('FORMAT(transactions.transaction_grandtotal, 0) as "Grand Total"'),
                DB::raw('FORMAT(transactions.transaction_shipment, 0) as "Price Wehelpyou"'),
                'transaction_pickup_wehelpyous.receiver_name as Receiver Name',
                'transaction_pickup_wehelpyous.receiver_phone as Receiver Phone',
                'transaction_pickup_wehelpyous.tracking_driver_name as Driver Name',
                'transaction_pickup_wehelpyous.tracking_driver_phone as Driver Phone',
                DB::raw('(
                    CASE
                        WHEN transaction_pickup_wehelpyous.latest_status_id = "1" THEN "Booking is received"
                        WHEN transaction_pickup_wehelpyous.latest_status_id = "8" THEN "Driver is found and on their way to pick-up location"
                        WHEN transaction_pickup_wehelpyous.latest_status_id = "9" THEN "Driver is enroute to deliver the item"
                        WHEN transaction_pickup_wehelpyous.latest_status_id = "91" THEN "Booking is cancelled by CS"
                        WHEN transaction_pickup_wehelpyous.latest_status_id = "2" THEN "Completed"
                        WHEN transaction_pickup_wehelpyous.latest_status_id = "95" THEN "Driver not found"
                        WHEN transaction_pickup_wehelpyous.latest_status_id = "11" THEN "Finding Driver"
                        ELSE "-"
                    END
                ) as "Status"'))->get()->toArray();

            return response()->json(MyHelper::checkGet($data));
        }else{
            $sum = $data->select(DB::raw('SUM(transactions.transaction_shipment) as total_price_wehelpyou'))->first();
            $data = $data->select('outlets.outlet_name', 'outlets.outlet_code', 'transactions.*', 'transaction_pickup_wehelpyous.*', 'transaction_pickups.order_id')->paginate(20);

            if($data){
                $result = [
                    'status' => 'success',
                    'result' => [
                        'data' => $data,
                        'sum' => $sum
                    ]
                ];

                return response()->json($result);
            }else{
                return response()->json(MyHelper::checkGet($data));
            }
        }

    }
}
