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
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Entities\AchievementGroup;

class ApiMembershipWebview extends Controller
{
	public function detailV2(Request $request)
	{
		$user = $request->user();
		$currentMembership = $user->memberships()->first();
		if (!$currentMembership) {
			return [
				'status' => 'fail',
				'messages' => [
					'Pengguna tidak memiliki membership',
				],
			];
		}
		$memberships = Membership::all();
		$progress = [
			'current' => 0,
			'min' => 0,
			'max' => 0,
			'progress_percent' => 0,
			'membership_text' => 0,
		];

		switch ($currentMembership->membership_type) {
			case 'balance':
				$memberships = $memberships->sortBy('min_total_balance');
				$nextMembership = null;
				$nextFlag = false;

				foreach ($memberships as $membership) {
					if ($nextFlag) {
						$nextMembership = $membership;
						break;
					}
					if ($currentMembership->id_membership == $membership->id_membership) {
						$nextFlag = true;
						continue;
					}
				}

				$currentValue = LogBalance::where('id_user', $user['id'])->whereNotIn('source', [ 'Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal', 'Point Injection', 'Welcome Point'])->where('balance', '>', 0)->sum('balance');
				$kurang = ($nextMembership ? ($nextMembership['min_total_balance'] - $currentValue) : 0);
				$currentMinValue = $currentMembership['min_total_balance'];
				$nextMinValue = $nextMembership ? $nextMembership['min_total_balance'] : 0;
				$membershipText = 'Silahkan kumpulkan ' .env('POINT_NAME', 'poin'). ' sebanyak <b>' . MyHelper::requestNumber($kurang, '_POINT') . ' '.env('POINT_NAME', 'poin').'</b> lagi untuk menuju <b>' . ($nextMembership['membership_name'] ?? '') . '</b>';
				break;
			case 'count':
				$memberships = $memberships->sortBy('min_total_count');
				$nextMembership = null;
				$nextFlag = false;

				foreach ($memberships as $membership) {
					if ($nextFlag) {
						$nextMembership = $membership;
						break;
					}
					if ($currentMembership->id_membership == $membership->id_membership) {
						$nextFlag = true;
						continue;
					}
				}

				$currentValue = Transaction::where('id_user', $user['id'])->whereNotNull('completed_at')->count('id_transaction');
				$kurang = ($nextMembership ? ($nextMembership['min_total_count'] - $currentValue) : 0);
				$currentMinValue = $currentMembership['min_total_count'];
				$nextMinValue = $nextMembership ? $nextMembership['min_total_count'] : 0;
				$membershipText = 'Silahkan memesan order sebanyak <b>' . MyHelper::requestNumber($kurang, '_POINT') . ' kali</b> lagi untuk menuju <b>' . ($nextMembership['membership_name'] ?? '') . '</b>';
				break;
			case 'value':
				$memberships = $memberships->sortBy('min_total_value');
				$nextMembership = null;
				$nextFlag = false;

				foreach ($memberships as $membership) {
					if ($nextFlag) {
						$nextMembership = $membership;
						break;
					}
					if ($currentMembership->id_membership == $membership->id_membership) {
						$nextFlag = true;
						continue;
					}
				}
				$currentValue = Transaction::where('id_user', $user['id'])->whereNotNull('completed_at')->sum('transaction_grandtotal');
				$kurang = ($nextMembership ? ($nextMembership['min_total_value'] - $currentValue) : 0);
				$currentMinValue = $currentMembership['min_total_value'];
				$nextMinValue = $nextMembership ? $nextMembership['min_total_value'] : 0;
				$membershipText = 'Silahkan memesan order senilai <b>Rp' . MyHelper::requestNumber($kurang, '_CURRENCY') . '</b> lagi untuk menuju <b>' . ($nextMembership['membership_name'] ?? '') . '</b>';
				break;
			default:
				return [
					'status' => 'fail',
					'messages' => ['Perhitungan membership tidak diketahui']
				];
		}

		$progress = [
			'current' => $currentValue,
			'min_value' => $currentMinValue,
			'max_value' => $nextMinValue,
			'membership_text' => $nextMembership ? $membershipText : 'Selamat! Kamu sudah menjadi <b>'.$currentMembership['membership_name'].'</b>. Silahkan nikmati berbagai keuntungannya ya!',
		];

		try {
			$progress['progress_percent'] = (int) (($progress['current'] - $progress['min_value']) * 100 / ($progress['max_value'] ? ($progress['max_value'] - $progress['min_value']) : $progress['min_value']));
		} catch (\Exception $e) {
			$progress['progress_percent'] = 100;
		}
		
		if ($progress['progress_percent'] > 100) {
            $progress['progress_percent'] = 100;
		}

		$memberships->transform(function ($item, $index) use ($currentMembership) {
			if($index) {
				switch ($currentMembership->membership_type) {
					case 'balance':
						$text = 'Perolehan ' . env('POINT_NAME') . ' sebanyak ' . MyHelper::requestNumber($item->min_total_balance, '_POINT') . ' ' . env('POINT_NAME');
						break;
					case 'count':
						$text = 'Total transaksi & Order Layanan kumulatif sebanyak ' . MyHelper::requestNumber($item->min_total_count, '_POINT') . ' kali';
						break;
					case 'value':
						$text = 'Total transaksi & Order Layanan kumulatif sebesar Rp' . MyHelper::requestNumber($item->min_total_value, '_CURRENCY');
						break;
				}
			} else {
				$text = 'Anda memulai dari level membership ini';
			}
			return [
				'membership_name' => $item->membership_name,
				'membership_image' => config('url.storage_url_api') . $item->membership_image,
				'membership_description' => implode("\n", json_decode($item->benefit_text,true)),
				'membership_requirement_text' => $text,
				'membership_requirement_text_muted' => !$index,
			];
		});

		$result = [
			'user' => [
				'name' => $user->name,
				'member_since' => MyHelper::adjustTimezone($user->created_at, null, 'd F Y', true),
			],
			'current_membership' => [
				'membership_name' => $currentMembership['membership_name'],
				'membership_image' => config('url.storage_url_api') . $currentMembership['membership_image'],
				'membership_next_image' => ($nextMembership['membership_image'] ?? false) ? config('url.storage_url_api') . $nextMembership['membership_image'] : null ,
				'membership_card' => config('url.storage_url_api') . $currentMembership['membership_card'],
				'membership_text' => '',
				'progress' => $progress,
			],
			'memberships' => $memberships,
		];
		return MyHelper::checkGet($result);
	}

	public function detail(Request $request)
    {
		$post = [
			'id_user' => $request->user()->id
		];
		$result = [];
		$result['user_membership'] = UsersMembership::with('user', 'membership')->where('id_user', $post['id_user'])->orderBy('id_log_membership', 'desc')->first();
		$settingCashback = Setting::where('key', 'cashback_conversion_value')->first();
		if(!$settingCashback || !$settingCashback->value){
			return response()->json([
				'status' => 'fail',
				'messages' => ['Cashback conversion not found']
			]);
		}
		switch ($result['user_membership']->membership_type) {
			case 'balance':
				$result['user_membership']['min_value'] 		= $result['user_membership']->min_total_balance;
				$result['user_membership']['retain_min_value'] 	= $result['user_membership']->retain_min_total_balance;
				break;
			case 'count':
				$result['user_membership']['min_value'] 		= $result['user_membership']->min_total_count;
				$result['user_membership']['retain_min_value'] 	= $result['user_membership']->retain_min_total_count;
				break;
			case 'value':
				$result['user_membership']['min_value'] 		= $result['user_membership']->min_total_value;
				$result['user_membership']['retain_min_value'] 	= $result['user_membership']->retain_min_total_value;
				break;
			case 'achievement':
				$result['user_membership']['min_value'] 		= $result['user_membership']->min_total_achievement;
				$result['user_membership']['retain_min_value'] 	= $result['user_membership']->retain_min_total_achievement;
				
				break;
		}

		$getUserAch = AchievementGroup::select(
			'achievement_groups.id_achievement_group',
			'achievement_groups.name as name_group',
			'achievement_groups.logo_badge_default',
			'achievement_groups.description',
			'achievement_details.name as name_badge',
			'achievement_details.logo_badge'
		)->join('achievement_details', 'achievement_groups.id_achievement_group', 'achievement_details.id_achievement_group')
		->join('achievement_users', 'achievement_details.id_achievement_detail', 'achievement_users.id_achievement_detail')
		->where('id_user', $post['id_user'])->orderBy('achievement_details.id_achievement_detail', 'DESC')->get()->toArray();
		$result['user_badge'] = [];
		foreach ($getUserAch as $userAch) {
			$search = array_search(MyHelper::decSlug($userAch['id_achievement_group']), array_column($result['user_badge'], 'id_achievement_group'));
			if ($search === false) {
				$result['user_badge'][] = [
					'id_achievement_group'		=> MyHelper::decSlug($userAch['id_achievement_group']),
					'name_group'				=> $userAch['name_group'],
					'logo_badge_default'		=> config('url.storage_url_api').$userAch['logo_badge_default'],
					'description'				=> $userAch['description'],
					'name_badge'				=> $userAch['name_badge'],
					'logo_badge'				=> config('url.storage_url_api').$userAch['logo_badge']
				];
			}
		}
		// $result['user_membership']['membership_bg_image'] = config('url.storage_url_api') . $result['user_membership']->membership->membership_bg_image;
		// $result['user_membership']['membership_background_card_color'] = $result['user_membership']->membership->membership_background_card_color;
		// $result['user_membership']['membership_background_card_pattern'] = (is_null($result['user_membership']->membership->membership_background_card_pattern)) ? null : config('url.storage_url_api') . $result['user_membership']->membership->membership_background_card_pattern;
		// $result['user_membership']['membership_text_color'] = $result['user_membership']->membership->membership_text_color;

		unset($result['user_membership']['membership']);
		unset($result['user_membership']['min_total_count']);
		unset($result['user_membership']['min_total_value']);
		unset($result['user_membership']['min_total_balance']);
		unset($result['user_membership']['min_total_achievement']);
		unset($result['user_membership']['retain_min_total_value']);
		unset($result['user_membership']['retain_min_total_count']);
		unset($result['user_membership']['retain_min_total_balance']);
		unset($result['user_membership']['retain_min_total_achievement']);
		unset($result['user_membership']['created_at']);
		unset($result['user_membership']['updated_at']);

		$membershipUser['name'] = $result['user_membership']->user->name;
		$allMembership = Membership::with('membership_promo_id')->orderBy('min_total_value','asc')->orderBy('min_total_count', 'asc')->orderBy('min_total_balance', 'asc')->orderBy('min_total_achievement', 'asc')->get()->toArray();
		$nextMembershipName = "";
		// $nextMembershipImage = "";
		$nextTrx = 0;
		$nextTrxType = '';
		if(count($allMembership) > 0){
			if($result['user_membership']){
				$result['user_membership']['membership_image'] = config('url.storage_url_api') . $result['user_membership']['membership_image'];
				$result['user_membership']['membership_card'] = config('url.storage_url_api') . $result['user_membership']['membership_card'];
				foreach($allMembership as $index => $dataMembership){
					$allMembership[$index]['benefit_text']=json_decode($dataMembership['benefit_text'],true)[0]??"";
					switch ($dataMembership['membership_type']) {
						case 'count':
							$allMembership[$index]['min_value'] 		= $dataMembership['min_total_count'];
							$allMembership[$index]['retain_min_value'] 	= $dataMembership['retain_min_total_count'];
							if($dataMembership['min_total_count'] > $result['user_membership']['min_total_count']){
								if($nextMembershipName == ""){
									$nextTrx = $dataMembership['min_total_count'];
									$nextTrxType = 'count';
									$nextMembershipName = $dataMembership['membership_name'];
									// $nextMembershipImage =  config('url.storage_url_api') . $dataMembership['membership_image'];
								}
							}
							break;
						case 'value':
							$allMembership[$index]['min_value'] 		= $dataMembership['min_total_value'];
							$allMembership[$index]['retain_min_value'] 	= $dataMembership['retain_min_total_value'];
							if($dataMembership['min_total_value'] > $result['user_membership']['min_total_value']){
								if($nextMembershipName == ""){
									$nextTrx = $dataMembership['min_total_value'];
									$nextTrxType = 'value';
									$nextMembershipName = $dataMembership['membership_name'];
									// $nextMembershipImage =  config('url.storage_url_api') . $dataMembership['membership_image'];
								}
							}
							break;
						case 'balance':
							$allMembership[$index]['min_value'] 		= $dataMembership['min_total_balance'];
							$allMembership[$index]['retain_min_value'] 	= $dataMembership['retain_min_total_balance'];
							if($dataMembership['min_total_balance'] > $result['user_membership']['min_total_balance']){
								if($nextMembershipName == ""){
									$nextTrx = $dataMembership['min_total_balance'];
									$nextTrxType = 'balance';
									$nextMembershipName = $dataMembership['membership_name'];
									// $nextMembershipImage =  config('url.storage_url_api') . $dataMembership['membership_image'];
								}
							}
							break;
						case 'achievement':
							$allMembership[$index]['min_value'] 		= $dataMembership['min_total_achievement'];
							$allMembership[$index]['retain_min_value'] 	= $dataMembership['retain_min_total_achievement'];
							if($dataMembership['min_total_achievement'] > $result['user_membership']['min_total_achievement']){
								if($nextMembershipName == ""){
									$nextTrx = $dataMembership['min_total_achievement'];
									$nextTrxType = 'achievement';
									$nextMembershipName = $dataMembership['membership_name'];
									// $nextMembershipImage =  config('url.storage_url_api') . $dataMembership['membership_image'];
								}
							}
							break;
					}
					
					if ($dataMembership['membership_name'] == $result['user_membership']['membership_name']) {
						$indexNow = $index;
					}

					unset($allMembership[$index]['min_total_count']);
					unset($allMembership[$index]['min_total_value']);
					unset($allMembership[$index]['min_total_balance']);
					unset($allMembership[$index]['min_total_achievement']);
					unset($allMembership[$index]['retain_min_total_value']);
					unset($allMembership[$index]['retain_min_total_count']);
					unset($allMembership[$index]['retain_min_total_balance']);
					unset($allMembership[$index]['retain_min_total_achievement']);
					unset($allMembership[$index]['created_at']);
					unset($allMembership[$index]['updated_at']);
					
					$allMembership[$index]['membership_image'] = config('url.storage_url_api').$allMembership[$index]['membership_image'];
					$allMembership[$index]['membership_card'] = config('url.storage_url_api').$allMembership[$index]['membership_card'];
					// $allMembership[$index]['membership_bg_image'] = config('url.storage_url_api').$allMembership[$index]['membership_bg_image'];
					$allMembership[$index]['membership_next_image'] = $allMembership[$index]['membership_next_image']?config('url.storage_url_api').$allMembership[$index]['membership_next_image']:null;
					$allMembership[$index]['benefit_cashback_multiplier'] = $allMembership[$index]['benefit_cashback_multiplier'] * $settingCashback->value;
				}
			}else{
				$membershipUser = User::find($post['id_user']);
				$nextMembershipName = $allMembership[0]['membership_name'];
				// $nextMembershipImage = config('url.storage_url_api') . $allMembership[0]['membership_image'];
				if($allMembership[0]['membership_type'] == 'count'){
					$nextTrx = $allMembership[0]['min_total_count'];
					$nextTrxType = 'count';
				}
				if($allMembership[0]['membership_type'] == 'value'){
					$nextTrx = $allMembership[0]['min_total_value'];
					$nextTrxType = 'value';
				}
				foreach($allMembership as $j => $dataMember){
					$allMembership[$j]['membership_image'] = config('url.storage_url_api').$allMembership[$j]['membership_image'];
					$allMembership[$j]['membership_card'] = config('url.storage_url_api').$allMembership[$j]['membership_card'];
					$allMembership[$j]['benefit_cashback_multiplier'] = $allMembership[$j]['benefit_cashback_multiplier'] * $settingCashback->value;
				}
			}
		}
		$membershipUser['next_level'] = $nextMembershipName;
		// $result['next_membership_image'] = $nextMembershipImage;
		if(isset($result['user_membership'])){
			if($nextTrxType == 'count'){
				$count_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->count('transaction_grandtotal');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($count_transaction,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $count_transaction;
			}elseif($nextTrxType == 'value'){
				$subtotal_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->sum('transaction_grandtotal');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($subtotal_transaction,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $subtotal_transaction;
				$membershipUser['progress_active'] = ($subtotal_transaction / $nextTrx) * 100;
				// $result['next_trx']		= $subtotal_transaction - $nextTrx;
			}elseif($nextTrxType == 'balance'){
				$total_balance = LogBalance::where('id_user', $post['id_user'])->whereNotIn('source', [ 'Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal', 'Point Injection', 'Welcome Point'])->where('balance', '>', 0)->sum('balance');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($total_balance,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $total_balance;
				$membershipUser['progress_active'] = ($total_balance / $nextTrx) * 100;
				// $result['next_trx']		= $nextTrx - $total_balance;
			}elseif($nextTrxType == 'achievement'){
				$total_achievement = DB::table('achievement_users')
				->join('achievement_details', 'achievement_users.id_achievement_detail', '=', 'achievement_details.id_achievement_detail')
				->join('achievement_groups', 'achievement_details.id_achievement_group', '=', 'achievement_groups.id_achievement_group')
				->where('id_user', $post['id_user'])
				->where('achievement_groups.status', 'Active')
				->where('achievement_groups.is_calculate', 1)
				->groupBy('achievement_groups.id_achievement_group')->get()->count();

				//for achievement display balance now
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($result['user_membership']->user->balance, '_POINT');

				$membershipUser['progress_now'] = (int) $total_achievement;
				$membershipUser['progress_active'] = ($total_achievement / $nextTrx) * 100;
				// $result['next_trx']		= $nextTrx - $total_balance;
			}
		}
		$result['all_membership'] = $allMembership;
		//user dengan level tertinggi
		if($nextMembershipName == ""){
			$result['progress_active'] = 100;
			$result['next_trx'] = 0;
			if($allMembership[0]['membership_type'] == 'count'){
				$count_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->count('transaction_grandtotal');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($count_transaction,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $count_transaction;
			}elseif($allMembership[0]['membership_type'] == 'value'){
				$subtotal_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->sum('transaction_grandtotal');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($subtotal_transaction,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $subtotal_transaction;
			}elseif($allMembership[0]['membership_type'] == 'balance'){
				$total_balance = LogBalance::where('id_user', $post['id_user'])->whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal', 'Point Injection', 'Welcome Point'])->where('balance', '>', 0)->sum('balance');
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($total_balance,'_CURRENCY');
				$membershipUser['progress_now'] = (int) $total_balance;
			}elseif($allMembership[0]['membership_type'] == 'achievement'){
				$total_achievement = DB::table('achievement_users')
				->join('achievement_details', 'achievement_users.id_achievement_detail', '=', 'achievement_details.id_achievement_detail')
				->join('achievement_groups', 'achievement_details.id_achievement_group', '=', 'achievement_groups.id_achievement_group')
				->where('id_user', $post['id_user'])
				->where('achievement_groups.status', 'Active')
				->where('achievement_groups.is_calculate', 1)
				->groupBy('achievement_groups.id_achievement_group')->count();

				//for achievement display balance now
				$membershipUser['progress_now_text'] = MyHelper::requestNumber($result['user_membership']->user->balance, '_POINT');
				$membershipUser['progress_now'] = (int) $total_achievement;
			}
		}
		unset($result['user_membership']['user']);
		$membershipUser['progress_min_text']		=  MyHelper::requestNumber($result['user_membership']['min_value'],'_CURRENCY');
		$membershipUser['progress_min']		= $result['user_membership']['min_value'];
		if (isset($allMembership[$indexNow + 1])) {
			$membershipUser['progress_max_text']	= MyHelper::requestNumber($result['all_membership'][$indexNow + 1]['min_value'],'_CURRENCY');
			$membershipUser['progress_max']	= $result['all_membership'][$indexNow + 1]['min_value'];

			//wording membership
			//for 0 badge
			if($membershipUser['progress_now'] == 0){
				$membershipUser['description']= 'Anda belum mengumpulkan badge, ayo kumpulkan '.$membershipUser['progress_max'].' badge untuk menuju <b>'.strtoupper($result['all_membership'][$indexNow + 1]['membership_name']).'</b>';
			}else{
				$membershipUser['description']= 'Anda telah mengumpulkan '.$membershipUser['progress_now'].' badge, lengkapi '.($membershipUser['progress_max']-$membershipUser['progress_now']).' badge lagi untuk menuju <b>'.strtoupper($result['all_membership'][$indexNow + 1]['membership_name']).'</b>';
			}
		} else {
			$membershipUser['progress_max_text']	= MyHelper::requestNumber($result['all_membership'][$indexNow]['min_value'],'_CURRENCY');
			$membershipUser['progress_max']	= $result['all_membership'][$indexNow]['min_value'];
			//for highest level progress now always end progress
			$membershipUser['progress_now_text'] = $result['all_membership'][$indexNow]['min_value'];
			$membershipUser['progress_now'] = $result['all_membership'][$indexNow]['min_value'];

			//wording membership
			$membershipUser['description'] = 'Selamat! Kamu sudah menjadi <b>'.$result['all_membership'][$indexNow]['membership_name'].'</b>. Silahkan nikmati berbagai keuntungannya ya!';
		}

		$membershipUser['member_since'] = MyHelper::adjustTimezone($transaction->transaction_date, null, 'd F Y', true);
		$result['user_membership']['user']	= $membershipUser;

		return response()->json(MyHelper::checkGet($result));
	}
    // public function webview(Request $request)
    // {
	// 	$check = $request->json('check');
    //     if (empty($check)) {
	// 		$user = $request->user();
	// 		$dataEncode = [
	// 			'id_user' => $user->id,
	// 		];
	// 		$encode = json_encode($dataEncode);
	// 		$base = base64_encode($encode);
	// 		$send = [
	// 			'status' => 'success',
	// 			'result' => [
	// 				'url'              => config('url.api_url').'api/membership/web/view?data='.$base
	// 			],
	// 		];
	// 		return response()->json($send);
    //     }
	// 	$post = $request->json()->all();
	// 	$result = [];
	// 	$result['user_membership'] = UsersMembership::with('user')->where('id_user', $post['id_user'])->orderBy('id_log_membership', 'desc')->first();
	// 	$settingCashback = Setting::where('key', 'cashback_conversion_value')->first();
	// 	if(!$settingCashback || !$settingCashback->value){
	// 		return response()->json([
	// 			'status' => 'fail',
	// 			'messages' => ['Cashback conversion not found']
	// 		]);
	// 	}
	// 	$allMembership = Membership::with('membership_promo_id')->orderBy('min_total_value','asc')->orderBy('min_total_count', 'asc')->orderBy('min_total_balance', 'asc')->get()->toArray();
	// 	$nextMembershipName = "";
	// 	$nextMembershipImage = "";
	// 	$nextTrx = 0;
	// 	$nextTrxType = '';
	// 	if(count($allMembership) > 0){
	// 		if($result['user_membership']){
	// 			foreach($allMembership as $index => $dataMembership){
	// 				$allMembership[$index]['benefit_text']=json_decode($dataMembership['benefit_text'],true)??[];
	// 				if($dataMembership['membership_type'] == 'count'){
	// 				    $allMembership[$index]['min_value'] = $dataMembership['min_total_count'];
	// 					if($dataMembership['min_total_count'] > $result['user_membership']['min_total_count']){
	// 						if($nextMembershipName == ""){
	// 							$nextTrx = $dataMembership['min_total_count'];
	// 							$nextTrxType = 'count';
	// 							$nextMembershipName = $dataMembership['membership_name'];
	// 							$nextMembershipImage = $dataMembership['membership_image'];
	// 						}
	// 					}
	// 				}
	// 				if($dataMembership['membership_type'] == 'value'){
	// 					$allMembership[$index]['min_value'] = $dataMembership['min_total_value'];
	// 					if($dataMembership['min_total_value'] > $result['user_membership']['min_total_value']){
	// 						if($nextMembershipName == ""){
	// 							$nextTrx = $dataMembership['min_total_value'];
	// 							$nextTrxType = 'value';
	// 							$nextMembershipName = $dataMembership['membership_name'];
	// 							$nextMembershipImage = $dataMembership['membership_image'];
	// 						}
	// 					}
	// 				}
	// 				if($dataMembership['membership_type'] == 'balance'){
	// 				    $allMembership[$index]['min_value'] = $dataMembership['min_total_balance'];
	// 					if($dataMembership['min_total_balance'] > $result['user_membership']['min_total_balance']){
	// 						if($nextMembershipName == ""){
	// 							$nextTrx = $dataMembership['min_total_balance'];
	// 							$nextTrxType = 'balance';
	// 							$nextMembershipName = $dataMembership['membership_name'];
	// 							$nextMembershipImage = $dataMembership['membership_image'];
	// 						}
	// 					}
	// 				}
	// 				$allMembership[$index]['membership_image'] = config('url.storage_url_api').$allMembership[$index]['membership_image'];
	// 				$allMembership[$index]['membership_next_image'] = $allMembership[$index]['membership_next_image']?config('url.storage_url_api').$allMembership[$index]['membership_next_image']:null;
	// 				$allMembership[$index]['benefit_cashback_multiplier'] = $allMembership[$index]['benefit_cashback_multiplier'] * $settingCashback->value;
	// 			}
	// 		}else{
	// 			$result['user_membership']['user'] = User::find($post['id_user']);
	// 			$nextMembershipName = $allMembership[0]['membership_name'];
	// 			$nextMembershipImage = $allMembership[0]['membership_image'];
	// 			if($allMembership[0]['membership_type'] == 'count'){
	// 				$nextTrx = $allMembership[0]['min_total_count'];
	// 				$nextTrxType = 'count';
	// 			}
	// 			if($allMembership[0]['membership_type'] == 'value'){
	// 				$nextTrx = $allMembership[0]['min_total_value'];
	// 				$nextTrxType = 'value';
	// 			}
	// 			foreach($allMembership as $j => $dataMember){
	// 				$allMembership[$j]['membership_image'] = config('url.storage_url_api').$allMembership[$j]['membership_image'];
	// 				$allMembership[$j]['benefit_cashback_multiplier'] = $allMembership[$j]['benefit_cashback_multiplier'] * $settingCashback->value;
	// 			}
	// 		}
	// 	}
	// 	$result['next_membership_name'] = $nextMembershipName;
	// 	$result['next_membership_image'] = $nextMembershipImage;
	// 	if(isset($result['user_membership'])){
	// 		if($nextTrxType == 'count'){
	// 			$count_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->count('transaction_grandtotal');
	// 			$result['user_membership']['user']['progress_now'] = $count_transaction;
	// 		}elseif($nextTrxType == 'value'){
	// 			$subtotal_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->sum('transaction_grandtotal');
	// 			$result['user_membership']['user']['progress_now'] = $subtotal_transaction;
	// 			$result['progress_active'] = ($subtotal_transaction / $nextTrx) * 100;
	// 			$result['next_trx']		= $subtotal_transaction - $nextTrx;
	// 		}elseif($nextTrxType == 'balance'){
	// 			$total_balance = LogBalance::where('id_user', $post['id_user'])->whereNotIn('source', [ 'Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])->where('balance', '>', 0)->sum('balance');
	// 			$result['user_membership']['user']['progress_now'] = $total_balance;
	// 			$result['progress_active'] = ($total_balance / $nextTrx) * 100;
	// 			$result['next_trx']		= $nextTrx - $total_balance;
	// 		}
	// 	}
	// 	$result['all_membership'] = $allMembership;
	// 	//user dengan level tertinggi
	// 	if($nextMembershipName == ""){
	// 		$result['progress_active'] = 100;
	// 		$result['next_trx'] = 0;
	// 		if($allMembership[0]['membership_type'] == 'count'){
	// 			$count_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->count('transaction_grandtotal');
	// 			$result['user_membership']['user']['progress_now'] = $count_transaction;
	// 		}elseif($allMembership[0]['membership_type'] == 'value'){
	// 			$subtotal_transaction = Transaction::where('id_user', $post['id_user'])->where('transaction_payment_status', 'Completed')->sum('transaction_grandtotal');
	// 			$result['user_membership']['user']['progress_now'] = $subtotal_transaction;
	// 		}elseif($allMembership[0]['membership_type'] == 'balance'){
	// 			$total_balance = LogBalance::where('id_user', $post['id_user'])->whereNotIn('source', ['Rejected Order', 'Rejected Order Midtrans', 'Rejected Order Point', 'Reversal'])->where('balance', '>', 0)->sum('balance');
	// 			$result['user_membership']['user']['progress_now'] = $total_balance;
	// 		}
	// 	}
	// 	return response()->json(MyHelper::checkGet($result));
	// }
	// public function detailWebview(Request $request)
	// {
	// 	$bearer = $request->header('Authorization');

	// 	if ($bearer == "") {
	// 		return view('error', ['msg' => 'Unauthenticated']);
	// 	}
	// 	$data = json_decode(base64_decode($request->get('data')), true);
	// 	$data['check'] = 1;
	// 	$check = MyHelper::postCURLWithBearer('api/membership/detail/webview?log_save=0', $data, $bearer);
	// 	if (isset($check['status']) && $check['status'] == 'success') {
	// 		$data['result'] = $check['result'];
	// 	} elseif (isset($check['status']) && $check['status'] == 'fail') {
	// 		return view('error', ['msg' => 'Data failed']);
	// 	} else {
	// 		return view('error', ['msg' => 'Something went wrong, try again']);
	// 	}
	// 	$data['max_value'] = end($check['result']['all_membership'])['min_value'];

	// 	return view('membership::webview.detail_membership', $data);
	// }
}