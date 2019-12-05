<?php
namespace Modules\Membership\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Membership;
use App\Http\Models\UsersMembership;
use App\Http\Models\Transaction;
use App\Http\Models\User;
use App\Http\Models\Setting;
use App\Http\Models\LogBalance;
use App\Lib\MyHelper;
class ApiMembershipWebview extends Controller
{
    public function webview(Request $request)
    {
		$check = $request->json('check');
        if (empty($check)) {
			$user = $request->user();
			$dataEncode = [
				'id_user' => $user->id,
			];
			$encode = json_encode($dataEncode);
			$base = base64_encode($encode);
			$send = [
				'status' => 'success',
				'result' => [
					'url'              => env('API_URL').'api/membership/web/view?data='.$base
				],
			];
			return response()->json($send);
        }
		$post = $request->json()->all();
		$result = [];
		$result['user_membership'] = UsersMembership::with('user')->where('id_user', $post['id_user'])->orderBy('id_log_membership', 'desc')->first();
		$settingCashback = Setting::where('key', 'cashback_conversion_value')->first();
		if(!$settingCashback || !$settingCashback->value){
			return response()->json([
				'status' => 'fail',
				'messages' => ['Cashback conversion not found']
			]);
		}
		$allMembership = Membership::with('membership_promo_id')->orderBy('min_total_value','asc')->orderBy('min_total_count', 'asc')->orderBy('min_total_balance', 'asc')->get()->toArray();
		$nextMembershipName = "";
		$nextMembershipImage = "";
		$nextTrx = 0;
		$nextTrxType = '';
		if(count($allMembership) > 0){
			if($result['user_membership']){
				foreach($allMembership as $index => $dataMembership){
					$allMembership[$index]['benefit_text']=json_decode($dataMembership['benefit_text'],true)??[];
					if($dataMembership['membership_type'] == 'count'){
					    $allMembership[$index]['min_value'] = $dataMembership['min_total_count'];
						if($dataMembership['min_total_count'] > $result['user_membership']['min_total_count']){
							if($nextMembershipName == ""){
								$nextTrx = $dataMembership['min_total_count'];
								$nextTrxType = 'count';
								$nextMembershipName = $dataMembership['membership_name'];
								$nextMembershipImage = $dataMembership['membership_image'];
							}
						}
					}
					if($dataMembership['membership_type'] == 'value'){
						$allMembership[$index]['min_value'] = $dataMembership['min_total_value'];
						if($dataMembership['min_total_value'] > $result['user_membership']['min_total_value']){
							if($nextMembershipName == ""){
								$nextTrx = $dataMembership['min_total_value'];
								$nextTrxType = 'value';
								$nextMembershipName = $dataMembership['membership_name'];
								$nextMembershipImage = $dataMembership['membership_image'];
							}
						}
					}
					if($dataMembership['membership_type'] == 'balance'){
					    $allMembership[$index]['min_value'] = $dataMembership['min_total_balance'];
						if($dataMembership['min_total_balance'] > $result['user_membership']['min_total_balance']){
							if($nextMembershipName == ""){
								$nextTrx = $dataMembership['min_total_balance'];
								$nextTrxType = 'balance';
								$nextMembershipName = $dataMembership['membership_name'];
								$nextMembershipImage = $dataMembership['membership_image'];
							}
						}
					}
					$allMembership[$index]['membership_image'] = env('S3_URL_API').$allMembership[$index]['membership_image'];
					$allMembership[$index]['membership_next_image'] = $allMembership[$index]['membership_next_image']?env('S3_URL_API').$allMembership[$index]['membership_next_image']:null;
					$allMembership[$index]['benefit_cashback_multiplier'] = $allMembership[$index]['benefit_cashback_multiplier'] * $settingCashback->value;
				}
			}else{
				$result['user_membership']['user'] = User::find($post['id_user']);
				$nextMembershipName = $allMembership[0]['membership_name'];
				$nextMembershipImage = $allMembership[0]['membership_image'];
				if($allMembership[0]['membership_type'] == 'count'){
					$nextTrx = $allMembership[0]['min_total_count'];
					$nextTrxType = 'count';
				}
				if($allMembership[0]['membership_type'] == 'value'){
					$nextTrx = $allMembership[0]['min_total_value'];
					$nextTrxType = 'value';
				}
				foreach($allMembership as $j => $dataMember){
					$allMembership[$j]['membership_image'] = env('S3_URL_API').$allMembership[$j]['membership_image'];
					$allMembership[$j]['benefit_cashback_multiplier'] = $allMembership[$j]['benefit_cashback_multiplier'] * $settingCashback->value;
				}
			}
		}
		$result['next_membership_name'] = $nextMembershipName;
		$result['next_membership_image'] = $nextMembershipImage;
		if(isset($result['user_membership'])){
			if($nextTrxType == 'count'){
				$count_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->count('transaction_grandtotal');
				$result['user_membership']['user']['progress_now'] = $count_transaction;
			}elseif($nextTrxType == 'value'){
				$subtotal_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->sum('transaction_grandtotal');
				$result['user_membership']['user']['progress_now'] = $subtotal_transaction;
				$result['progress_active'] = ($subtotal_transaction / $nextTrx) * 100;
				$result['next_trx']		= $subtotal_transaction - $nextTrx;
			}elseif($nextTrxType == 'balance'){
				$total_balance = LogBalance::where('id_user', $post['id_user'])->whereNotIn('source', [ 'Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])->where('balance', '>', 0)->sum('balance');
				$result['user_membership']['user']['progress_now'] = $total_balance;
				$result['progress_active'] = ($total_balance / $nextTrx) * 100;
				$result['next_trx']		= $nextTrx - $total_balance;
			}
		}
		$result['all_membership'] = $allMembership;
		//user dengan level tertinggi
		if($nextMembershipName == ""){
			$result['progress_active'] = 100;
			$result['next_trx'] = 0;
			if($allMembership[0]['membership_type'] == 'count'){
				$count_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->count('transaction_grandtotal');
				$result['user_membership']['user']['progress_now'] = $count_transaction;
			}elseif($allMembership[0]['membership_type'] == 'value'){
				$subtotal_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->sum('transaction_grandtotal');
				$result['user_membership']['user']['progress_now'] = $subtotal_transaction;
			}elseif($allMembership[0]['membership_type'] == 'balance'){
				$total_balance = LogBalance::where('id_user', $post['id_user'])->whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])->where('balance', '>', 0)->sum('balance');
				$result['user_membership']['user']['progress_now'] = $total_balance;
			}
		}
		return response()->json(MyHelper::checkGet($result));
	}
	public function detailWebview(Request $request)
	{
		$bearer = $request->header('Authorization');

		if ($bearer == "") {
			return view('error', ['msg' => 'Unauthenticated']);
		}
		$data = json_decode(base64_decode($request->get('data')), true);
		$data['check'] = 1;
		$check = MyHelper::postCURLWithBearer('api/membership/detail/webview?log_save=0', $data, $bearer);
		if (isset($check['status']) && $check['status'] == 'success') {
			$data['result'] = $check['result'];
		} elseif (isset($check['status']) && $check['status'] == 'fail') {
			return view('error', ['msg' => 'Data failed']);
		} else {
			return view('error', ['msg' => 'Something went wrong, try again']);
		}
		$data['max_value'] = end($check['result']['all_membership'])['min_value'];

		return view('membership::webview.detail_membership', $data);
	}
}