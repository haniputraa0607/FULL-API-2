<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Product;
use App\Http\Models\Province;
use Modules\Outlet\Entities\OutletTimeShift;

use Modules\Recruitment\Entities\HairstylistLogBalance;
use Modules\Recruitment\Entities\OutletCash;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistInbox;
use Modules\Recruitment\Entities\HairstylistIncome;
use Modules\Recruitment\Entities\HairstylistAttendance;

use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\Transaction\Entities\TransactionProductService;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\UserRating\Entities\UserRatingSummary;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use Modules\Recruitment\Entities\HairstylistOverTime;
use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Entities\OutletCashAttachment;
use Modules\Recruitment\Entities\HairstylistAttendanceLog;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use App\Http\Models\TransactionPaymentMidtran;

use App\Lib\MyHelper;
use DB;
use DateTime;
use DateTimeZone;
use Modules\Users\Http\Requests\users_forgot;
use Modules\Users\Http\Requests\users_phone_pin_new_v2;
use PharIo\Manifest\EmailTest;
use Auth;
use Modules\Transaction\Entities\TransactionPaymentCashDetail;
use App\Jobs\RefreshBalanceHS;
use Modules\Recruitment\Entities\HairstylistPayrollQueue;

class ApiMitraSupervisor extends Controller
{

	public function cash_outlet(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['date'])){
			return ['status' => 'fail', 'messages' => ['Date can not be empty']];
		}

		if($user->level != 'Supervisor'){
			return ['status' => 'fail', 'messages' => ['Your level not available for this detail']];
		}
                $listHS = UserHairStylist::where('id_outlet', $user->id_outlet)
		->where('user_hair_stylist_status', 'Active')->select('id_user_hair_stylist', 'fullname as name')->get()->toArray();
		$currency = 'Rp';
		$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
		$result = [
			'total_current_cash_outlet' => $outlet['total_current_cash'],
			'currency' => $currency,
                        'list_hair_stylist' => $listHS,
		];
		return ['status' => 'success', 'result' => $result];
	}
	public function total_projection(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['date'])){
			return ['status' => 'fail', 'messages' => ['Date can not be empty']];
		}

		if($user->level != 'Supervisor'){
			return ['status' => 'fail', 'messages' => ['Your level not available for this detail']];
		}

		$date = date('Y-m-d', strtotime($post['date']));
		$currency = 'Rp';
		$projection = Transaction::join('transaction_payment_cash', 'transaction_payment_cash.id_transaction', 'transactions.id_transaction')
		->join('transaction_payment_cash_details','transaction_payment_cash_details.id_transaction_payment_cash','transaction_payment_cash.id_transaction_payment_cash')
		->join('transaction_products','transaction_products.id_transaction_product','transaction_payment_cash_details.id_transaction_product')
		->join('hairstylist_log_balances', 'hairstylist_log_balances.id_reference', 'transaction_products.id_transaction_product')
		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_products.id_user_hair_stylist')
		->whereDate('transactions.transaction_date', $date)
		->where('transaction_payment_status', 'Completed')
		->where('transactions.id_outlet', $user->id_outlet)
                ->groupby('transaction_products.id_transaction_product')
		->select('hairstylist_log_balances.balance','transaction_grandtotal', 'transactions.id_transaction', 'transactions.transaction_receipt_number', 'transaction_payment_cash_details.*', 'user_hair_stylist.fullname','transaction_products.transaction_product_price','transaction_products.transaction_product_discount_all');
		
		if(!empty($post['id_user_hair_stylist'])){
			$projection = $projection->where('transaction_products.id_user_hair_stylist', $post['id_user_hair_stylist']);
		}

		$totalProjection = $projection->orderBy('transaction_date', 'desc')->get();
		$amount = 0;
		foreach ($totalProjection as $value){
			$amount = $amount + $value['balance'];
		}
		$result = [
			'total_projection' => $amount,
		];
		return ['status' => 'success', 'result' => $result];
	}
        public function total_reception(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['date'])){
			return ['status' => 'fail', 'messages' => ['Date can not be empty']];
		}

		if($user->level != 'Supervisor'){
			return ['status' => 'fail', 'messages' => ['Your level not available for this detail']];
		}

		$date = date('Y-m-d', strtotime($post['date']));
		
		$history = OutletCash::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'outlet_cash.id_user_hair_stylist')
		->join('user_hair_stylist as confirm', 'confirm.id_user_hair_stylist', 'outlet_cash.confirm_by')
		->where('outlet_cash.id_outlet', $user->id_outlet)
		->whereDate('outlet_cash.confirm_at', $date)
		->where('outlet_cash_status', 'Confirm')
		->where('outlet_cash_type', 'Transfer To Supervisor')
		->select('outlet_cash_amount');
                
		if(!empty($post['id_user_hair_stylist'])){
			$history = $history->where('outlet_cash.id_user_hair_stylist', $post['id_user_hair_stylist']);
		}
		$totalAcceptance = $history->orderBy('outlet_cash.confirm_at', 'desc')->sum('outlet_cash_amount');
		$result = [
			'total_reception' => $totalAcceptance,
		];
		return ['status' => 'success', 'result' => $result];
	}
        
        public function spv_cash(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['date'])){
			return ['status' => 'fail', 'messages' => ['Date can not be empty']];
		}

		if($user->level != 'Supervisor'){
			return ['status' => 'fail', 'messages' => ['Your level not available for this detail']];
		}

		$date = date('Y-m-d', strtotime($post['date']));
		
		 $spvProjection =  Transaction::join('transaction_payment_cash', 'transaction_payment_cash.id_transaction', 'transactions.id_transaction')
		->join('transaction_payment_cash_details','transaction_payment_cash_details.id_transaction_payment_cash','transaction_payment_cash.id_transaction_payment_cash')
		->join('transaction_products','transaction_products.id_transaction_product','transaction_payment_cash_details.id_transaction_product')
		->join('hairstylist_log_balances', 'hairstylist_log_balances.id_reference', 'transaction_products.id_transaction_product')
		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_products.id_user_hair_stylist')
		->whereDate('transactions.transaction_date', $date)
		->where('transaction_payment_status', 'Completed')
		->where('transactions.id_outlet', $user->id_outlet)
		->where('transfer_status', 0)
                ->groupby('transaction_products.id_transaction_product')
		->select('hairstylist_log_balances.balance','transaction_grandtotal', 'transactions.id_transaction', 'transactions.transaction_receipt_number', 'transaction_payment_cash_details.*', 'user_hair_stylist.fullname','transaction_products.transaction_product_price','transaction_products.transaction_product_discount_all');
		
		$spvAcceptance = OutletCash::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'outlet_cash.id_user_hair_stylist')
		->join('user_hair_stylist as confirm', 'confirm.id_user_hair_stylist', 'outlet_cash.confirm_by')
		->where('outlet_cash.id_outlet', $user->id_outlet)
		->whereDate('outlet_cash.confirm_at', $date)
		->where('outlet_cash_status', 'Confirm')
		->where('outlet_cash_type', 'Transfer To Supervisor');
                if(!empty($post['id_user_hair_stylist'])){
			$spvProjection = $spvProjection->where('transaction_products.id_user_hair_stylist', $post['id_user_hair_stylist']);
			$spvAcceptance = $spvAcceptance->where('outlet_cash.id_user_hair_stylist', $post['id_user_hair_stylist']);
		}
		$spvProjection = $spvProjection->get();
		$amount = 0;
		foreach ($spvProjection as $value){
			$amount = $amount + $value['balance'];
		}
		
		$spvAcceptance = $spvAcceptance->select('outlet_cash_amount as amount')->sum('outlet_cash_amount');
		$result = [
			'spv_cash_projection' => (int)$amount,
			'spv_cash_acceptance' => (int)$spvAcceptance,
		];
		return ['status' => 'success', 'result' => $result];
	}
        public function projection(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['date'])){
			return ['status' => 'fail', 'messages' => ['Date can not be empty']];
		}

		if($user->level != 'Supervisor'){
			return ['status' => 'fail', 'messages' => ['Your level not available for this detail']];
		}

		$date = date('Y-m-d', strtotime($post['date']));
		$currency = 'Rp';
		$listHS = UserHairStylist::where('id_outlet', $user->id_outlet)
		->where('user_hair_stylist_status', 'Active')->select('id_user_hair_stylist', 'fullname as name')->get()->toArray();

		$projection = Transaction::join('transaction_payment_cash', 'transaction_payment_cash.id_transaction', 'transactions.id_transaction')
		->join('transaction_payment_cash_details','transaction_payment_cash_details.id_transaction_payment_cash','transaction_payment_cash.id_transaction_payment_cash')
		->join('transaction_products','transaction_products.id_transaction_product','transaction_payment_cash_details.id_transaction_product')
		->join('hairstylist_log_balances', 'hairstylist_log_balances.id_reference', 'transaction_products.id_transaction_product')
		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_products.id_user_hair_stylist')
		->whereDate('transactions.transaction_date', $date)
		->where('transaction_payment_status', 'Completed')
		->where('transactions.id_outlet', $user->id_outlet)
		->where('transfer_status', 0)
                ->groupby('transaction_products.id_transaction_product')
		->select('hairstylist_log_balances.balance','transaction_grandtotal', 'transactions.id_transaction', 'transactions.transaction_receipt_number', 'transaction_payment_cash_details.*', 'user_hair_stylist.fullname','transaction_products.transaction_product_price','transaction_products.transaction_product_discount_all');
		
		if(!empty($post['id_user_hair_stylist'])){
			$projection = $projection->where('transaction_products.id_user_hair_stylist', $post['id_user_hair_stylist']);
		}

		$projection = $projection->orderBy('transaction_date', 'desc')->get()->toArray();

		$resProjection = [];
		foreach ($projection as $value){
			$resProjection[] = [
				'id_transaction' => $value['id_transaction'],
				'time' => date('H:i', strtotime($value['updated_at'])),
				'hair_stylist_name' => $value['fullname'],
				'receipt_number' => $value['transaction_receipt_number'],
				'amount' => (int)$value['balance']
			];
		}

		$result = [
			'projection' => $resProjection,
		];
		return ['status' => 'success', 'result' => $result];
	}
        public function acceptance(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['date'])){
			return ['status' => 'fail', 'messages' => ['Date can not be empty']];
		}

		if($user->level != 'Supervisor'){
			return ['status' => 'fail', 'messages' => ['Your level not available for this detail']];
		}

		$date = date('Y-m-d', strtotime($post['date']));
		$currency = 'Rp';
		$listHS = UserHairStylist::where('id_outlet', $user->id_outlet)
		->where('user_hair_stylist_status', 'Active')->select('id_user_hair_stylist', 'fullname as name')->get()->toArray();

		$acceptance = OutletCash::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'outlet_cash.id_user_hair_stylist')
		->where('outlet_cash.id_outlet', $user->id_outlet)
		->whereDate('outlet_cash.created_at', $date)
		->where('outlet_cash_status', 'Pending')
		->where('outlet_cash_type', 'Transfer To Supervisor')
		->select('id_outlet_cash', DB::raw('DATE_FORMAT(outlet_cash.created_at, "%H:%i") as time'), 'fullname as hair_stylist_name',
			'outlet_cash_status', 'outlet_cash_code', 'outlet_cash_amount as amount');

		if(!empty($post['id_user_hair_stylist'])){
			$acceptance = $acceptance->where('outlet_cash.id_user_hair_stylist', $post['id_user_hair_stylist']);
		}

		$acceptance = $acceptance->orderBy('outlet_cash.created_at', 'desc')->get()->toArray();
		$result = [
			'acceptance' => $acceptance,
		];
		return ['status' => 'success', 'result' => $result];
	}
        
        public function history(Request $request){
		$user = $request->user();
		$post = $request->json()->all();
		if(empty($post['date'])){
			return ['status' => 'fail', 'messages' => ['Date can not be empty']];
		}

		if($user->level != 'Supervisor'){
			return ['status' => 'fail', 'messages' => ['Your level not available for this detail']];
		}

		$date = date('Y-m-d', strtotime($post['date']));
		$history = OutletCash::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'outlet_cash.id_user_hair_stylist')
		->join('user_hair_stylist as confirm', 'confirm.id_user_hair_stylist', 'outlet_cash.confirm_by')
		->where('outlet_cash.id_outlet', $user->id_outlet)
		->whereDate('outlet_cash.confirm_at', $date)
		->where('outlet_cash_status', 'Confirm')
		->where('outlet_cash_type', 'Transfer To Supervisor')
		->select('id_outlet_cash', DB::raw('DATE_FORMAT(outlet_cash.created_at, "%H:%i") as time'), 'user_hair_stylist.fullname as hair_stylist_name',
			'outlet_cash_status', 'outlet_cash_code', 'outlet_cash_amount as amount', 'confirm.fullname as confirm_by_name');

		if(!empty($post['id_user_hair_stylist'])){
			$history = $history->where('outlet_cash.id_user_hair_stylist', $post['id_user_hair_stylist']);
		}

		$history = $history->orderBy('outlet_cash.confirm_at', 'desc')->get()->toArray();

		$result = [
			'history' => $history
		];
		return ['status' => 'success', 'result' => $result];
	}
}
