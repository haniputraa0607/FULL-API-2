<?php

namespace Modules\UserRating\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingSummary;

use Modules\Transaction\Entities\TransactionProductService;

use App\Lib\MyHelper;

use Modules\UserRating\Entities\UserRatingLog;
use Modules\OutletApp\Http\Controllers\ApiOutletApp;

use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Favorite\Entities\FavoriteUserHiarStylist;

class ApiUserRatingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();
        $data = UserRating::with([
        	'transaction' => function($query) {
	            $query->select('id_transaction','transaction_receipt_number','transaction_from','trasaction_type','transaction_grandtotal','id_outlet');
	        },
	        'transaction.outlet' => function($query) {
	            $query->select('id_outlet','outlet_code','outlet_name');
	        },
	        'user' => function($query) {
	            $query->select('id','name','phone');
	        },
	        'user_hair_stylist' => function($query) {
                $query->select('id_user_hair_stylist','nickname','fullname','phone_number');
            }
	    ])->orderBy('id_user_rating','desc');

        // if($outlet_code = ($request['outlet_code']??false)){
        //     $data->whereHas('transaction.outlet',function($query) use ($outlet_code){
        //         $query->where('outlet_code',$outlet_code);
        //     });
        // }

        if($post['rule']??false){
            $this->filterList($data,$post['rule'],$post['operator']??'and');
        }

        $data= $data->paginate(10)->toArray();
        return MyHelper::checkGet($data);
    }

    public function filterList($model,$rule,$operator='and'){
        $newRule=[];
        $where=$operator=='and'?'where':'orWhere';
        foreach ($rule as $var) {
            $var1=['operator'=>$var['operator']??'=','parameter'=>$var['parameter']??null];
            if($var1['operator']=='like'){
                $var1['parameter']='%'.$var1['parameter'].'%';
            }
            $newRule[$var['subject']][]=$var1;
        }
        if($rules=$newRule['review_date']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Date'}('created_at',$rul['operator'],$rul['parameter']);
            }
        }
        if($rules=$newRule['star']??false){
            foreach ($rules as $rul) {
                $model->$where('rating_value',$rul['operator'],$rul['parameter']);
            }
        }
        if($rules=$newRule['transaction_date']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('transaction',function($query) use ($rul){
                    $query->whereDate('transaction_date',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['transaction_type']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('transaction',function($query) use ($rul){
                    $query->where('transaction_type',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['transaction_receipt_number']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('transaction',function($query) use ($rul){
                    $query->where('transaction_receipt_number',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['user_name']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('user',function($query) use ($rul){
                    $query->where('name',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['user_phone']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('user',function($query) use ($rul){
                    $query->where('phone',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['user_email']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('user',function($query) use ($rul){
                    $query->where('email',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['outlet']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('transaction.outlet',function($query) use ($rul){
                    $query->where('id_outlet',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['hairstylist_phone']??false){
            foreach ($rules as $rul) {
                $model->{$where.'Has'}('user_hair_stylist',function($query) use ($rul){
                    $query->where('phone_number',$rul['operator'],$rul['parameter']);
                });
            }
        }
        if($rules=$newRule['rating_target']??false){
            foreach ($rules as $rul) {
            	if ($rul['parameter'] == 'hairstylist') {
                	$model->{$where.'NotNull'}('user_ratings.id_user_hair_stylist');
            	} else {
                	$model->{$where.'NotNull'}('user_ratings.id_outlet');
            	}
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->json()->all();
        $id = $post['id'];
        $user = $request->user();
        $trx = Transaction::where([
            'id_transaction'=>$id,
            'id_user'=>$request->user()->id
        ])->first();
        if(!$trx){
            return [
                'status'=>'fail',
                'messages'=>['Transaction not found']
            ];
        }

        $id_outlet = $trx->id_outlet;
        $id_user_hair_stylist = null;
        $id_transaction_product_service = null;
        if (isset($post['id_user_hair_stylist'])) {

        	if (isset($post['id_transaction_product_service'])) {
				$trxService = TransactionProductService::where('id_transaction', $id)
							->where('id_user_hair_stylist', $post['id_user_hair_stylist'])
							->where('id_transaction_product_service', $post['id_transaction_product_service'])
							->first();

				if (!$trxService) {
					return [
		                'status'=>'fail',
		                'messages'=>['Hairstylist not found']
		            ];
				}

				$id_user_hair_stylist = $trxService->id_user_hair_stylist;
				$id_transaction_product_service = $trxService->id_transaction_product_service;
				$hs = $trxService->user_hair_stylist;
        	} else {
        		$hs = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->first();
        		if (!$hs) {
					return [
		                'status'=>'fail',
		                'messages'=>['Hairstylist not found']
		            ];
				}
				$id_user_hair_stylist = $hs->id_user_hair_stylist;
        	}
			$id_outlet = null;
        }
        if ($id_user_hair_stylist) {
        	$max_rating_value = Setting::select('value')->where('key','response_max_rating_value_hairstylist')->pluck('value')->first()?:2;
	        if($post['rating_value'] <= $max_rating_value){
	            $trx->load('outlet_name');
	            $variables = [
	                'receipt_number' => $trx->transaction_receipt_number,
	                'outlet_name' => $trx->outlet_name->outlet_name,
	                'transaction_date' => date('d F Y H:i',strtotime($trx->transaction_date)),
	                'rating_value' => (string) $post['rating_value'],
	                'suggestion' => $post['suggestion']??'',
	                'question' => $post['option_question'],
	                'nickname' => $hs['nickname'],
	                'fullname' => $hs['fullname'],
	                'selected_option' => implode(',',array_map(function($var){return trim($var,'"');},$post['option_value']??[]))
	            ];
	            app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('User Rating Hairstylist', $user->phone, $variables,null,true);
	        }
        } else {
	        $max_rating_value = Setting::select('value')->where('key','response_max_rating_value')->pluck('value')->first()?:2;
	        if($post['rating_value'] <= $max_rating_value){
	            $trx->load('outlet_name');
	            $variables = [
	                'receipt_number' => $trx->transaction_receipt_number,
	                'outlet_name' => $trx->outlet_name->outlet_name,
	                'transaction_date' => date('d F Y H:i',strtotime($trx->transaction_date)),
	                'rating_value' => (string) $post['rating_value'],
	                'suggestion' => $post['suggestion']??'',
	                'question' => $post['option_question'],
	                'selected_option' => implode(',',array_map(function($var){return trim($var,'"');},$post['option_value']??[]))
	            ];
	            app("Modules\Autocrm\Http\Controllers\ApiAutoCrm")->SendAutoCRM('User Rating Outlet', $user->phone, $variables,null,true);
	        }
        }

        $insert = [
            'id_transaction' => $trx->id_transaction,
            'id_user' => $request->user()->id,
            'id_outlet' => $id_outlet,
            'id_user_hair_stylist' => $id_user_hair_stylist,
            'rating_value' => $post['rating_value'],
            'suggestion' => $post['suggestion']??'',
            'option_question' => $post['option_question'],
            'option_value' => implode(',',array_map(function($var){return trim($var,'"');},$post['option_value']??[]))
        ];

        $create = UserRating::updateOrCreate([
        	'id_user' => $request->user()->id,
        	'id_transaction' => $id,
        	'id_outlet'	=> $id_outlet,
        	'id_user_hair_stylist' => $id_user_hair_stylist,
        	'id_transaction_product_service' => $id_transaction_product_service
        ],$insert);

        if ($id_user_hair_stylist) {
        	$hsRating = UserRating::where('id_user_hair_stylist', $id_user_hair_stylist)->get()->toArray();
        	if ($hsRating) {
	        	$totalHsRating = array_sum(array_column($hsRating,'rating_value')) / count($hsRating);
	        	UserHairStylist::where('id_user_hair_stylist', $id_user_hair_stylist)->update(['total_rating' => $totalHsRating]);
        	}
        }

        UserRatingLog::where([
        	'id_user' => $request->user()->id, 
        	'id_transaction' => $id,
        	'id_outlet'	=> $id_outlet,
        	'id_user_hair_stylist' => $id_user_hair_stylist,
        	'id_transaction_product_service' => $id_transaction_product_service
        ])->delete();

        $unrated = UserRatingLog::where('id_transaction',$trx->id_transaction)->first();
        if(!$unrated){
        	$uncompleteTrx = TransactionProduct::where('id_transaction', $trx->id_transaction)
	    					->whereNull('transaction_product_completed_at')
	    					->first();

        	if (!$uncompleteTrx) {
        		(new ApiOutletApp)->insertUserCashback($trx);
        	}
            Transaction::where('id_transaction',$trx->id_transaction)->update(['show_rate_popup'=>0]);
        }

        $countRatingValue = UserRating::where([
        	'id_outlet'	=> $id_outlet,
        	'id_user_hair_stylist' => $id_user_hair_stylist,
        	'rating_value'=> $post['rating_value']
        ])->count();

        $summaryRatingValue = UserRatingSummary::updateOrCreate([
        	'id_outlet'	=> $id_outlet,
        	'id_user_hair_stylist' => $id_user_hair_stylist,
        	'key' => $post['rating_value'],
        	'summary_type' => 'rating_value'
        ],[
        	'value' => $countRatingValue
        ]);

        foreach ($post['option_value'] ?? [] as $value) {
			$countOptionValue = UserRating::where([
	        	'id_outlet'	=> $id_outlet,
	        	'id_user_hair_stylist' => $id_user_hair_stylist,
	        	['option_value', 'like', '%' . $value . '%']
	        ])->count();

	        $summaryOptionValue = UserRatingSummary::updateOrCreate([
	        	'id_outlet'	=> $id_outlet,
	        	'id_user_hair_stylist' => $id_user_hair_stylist,
	        	'key' => $value,
	        	'summary_type' => 'option_value'
	        ],[
	        	'value' => $countOptionValue
	        ]);        	
        }

        return MyHelper::checkCreate($create);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        $post = $request->json()->all();
        $data = UserRating::with([
        	'transaction' => function($query) {
	            $query->select('id_transaction','transaction_receipt_number','transaction_from','trasaction_type','transaction_grandtotal','id_outlet');
	        },
	        'transaction.outlet' => function($query) {
	            $query->select('id_outlet','outlet_code','outlet_name');
	        },
	        'user' => function($query) {
	            $query->select('id','name','phone');
	        },
	        'user_hair_stylist' => function($query) {
                $query->select('id_user_hair_stylist','nickname','fullname','phone_number');
            }
	    ])->where(['id_user_rating'=>$post['id']])->first();
        return MyHelper::checkGet($data);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        return MyHelper::checkDelete(UserRating::find($request->json('id_user_rating'))->delete());
    }
    
    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function getDetail(Request $request) {
        $post = $request->json()->all();
        $user = clone $request->user();

        if (isset($post['id'])) {
            $id_transaction = $post['id'];
            $user->load('log_popup_user_rating');

            $transaction = Transaction::find($id_transaction);
            if(!$transaction){
                return [
                    'status' => 'fail',
                    'messages' => ['Transaction not found']
                ];
            }

	        $logRatings = UserRatingLog::where('id_transaction', $id_transaction)
						->where('id_user', $user->id)
						->with('transaction.outlet.brands')
						->get();

        } else {
            $user->load('log_popup_user_rating.transaction.outlet.brands');
            $log_popup_user_ratings = $user->log_popup_user_rating;
            $log_popup_user_rating = null;
            $logRatings = [];
            $interval = (Setting::where('key','popup_min_interval')->pluck('value')->first()?:900);
            $max_date = date('Y-m-d',time() - ((Setting::select('value')->where('key','popup_max_days')->pluck('value')->first()?:3) * 86400));
            $maxList = Setting::where('key','popup_max_list')->pluck('value')->first() ?: 5;

            if (empty($log_popup_user_ratings)) {
                return MyHelper::checkGet([]);
            }

            foreach ($log_popup_user_ratings as $log_pop) {
                if (
                    $log_pop->refuse_count>=(Setting::where('key','popup_max_refuse')->pluck('value')->first()?:3) ||
                    strtotime($log_pop->last_popup)+$interval>time()
                ) {
                    continue;
                }

                if ($log_popup_user_rating && $log_popup_user_rating->last_popup < $log_pop->last_popup) {
                    continue;
                }

                $log_popup_user_rating = $log_pop;
	            $transaction = Transaction::select('id_transaction','transaction_receipt_number','transaction_from','transaction_date','id_outlet')
	            ->with(['outlet'=>function($query){
	                $query->select('outlet_name','id_outlet');
	            }])
	            ->where('id_transaction', $log_popup_user_rating->id_transaction)
	            ->where(['id_user'=>$user->id])
	            ->whereDate('transaction_date','>',$max_date)
	            ->orderBy('transaction_date','asc')
	            ->first();

	            // check if transaction is exist
	            if(!$transaction){
	                // log popup is not valid
	                continue;
	                $log_popup_user_rating->delete();
	                return $this->getDetail($request);
	            }

	            $log_popup_user_rating->refuse_count++;
	            $log_popup_user_rating->last_popup = date('Y-m-d H:i:s');
	            $log_popup_user_rating->save();
	            $logRatings[] = $log_popup_user_rating;

	            if ($maxList <= count($logRatings)) {
	            	break;
	            }
            }

            if (empty($logRatings)) {
                return MyHelper::checkGet([]);
            }
        }

        $defaultOptions = [
            'question'=>Setting::where('key','default_rating_question')->pluck('value_text')->first()?:'What\'s best from us?',
            'options' =>explode(',',Setting::where('key','default_rating_options')->pluck('value_text')->first()?:'Cleanness,Accuracy,Employee Hospitality,Process Time')
        ];

    	$optionOutlet = ['1'=>$defaultOptions,'2'=>$defaultOptions,'3'=>$defaultOptions,'4'=>$defaultOptions,'5'=>$defaultOptions];
        $ratingOptionOutlet = RatingOption::select('star','question','options')->where('rating_target', 'outlet')->get();
        foreach ($ratingOptionOutlet as $rt) {
            $stars = explode(',',$rt['star']);
            foreach ($stars as $star) {
                $optionOutlet[$star] = [
                    'question'=>$rt['question'],
                    'options'=>explode(',',$rt['options'])
                ];
            }
        }
        $optionOutlet = array_values($optionOutlet);
        
    	$optionHs = ['1'=>$defaultOptions,'2'=>$defaultOptions,'3'=>$defaultOptions,'4'=>$defaultOptions,'5'=>$defaultOptions];
        $ratingOptionHs = RatingOption::select('star','question','options')->where('rating_target', 'hairstylist')->get();
        foreach ($ratingOptionHs as $rt) {
            $stars = explode(',',$rt['star']);
            foreach ($stars as $star) {
                $optionHs[$star] = [
                    'question'=>$rt['question'],
                    'options'=>explode(',',$rt['options'])
                ];
            }
        }
        $optionHs = array_values($optionHs);

        $ratingList = [];
        $title = 'Beri Penilaian';
        $message = "Dapatkan loyalty points dengan memberikan penilaian atas transaksi Anda pada hari:  /n <b>'%date%' di '%outlet_address%'</b>";
        foreach ($logRatings as $key => $log) {
			$rating['id'] = $log['id_transaction'];
			$rating['id_transaction_product_service'] = $log['id_transaction_product_service'];
			$rating['id_user_hair_stylist'] = null;
			$rating['detail_hairstylist'] = null;
	        $rating['transaction_receipt_number'] = $log['transaction']['transaction_receipt_number'];
	        $rating['transaction_date'] = date('d M Y H:i',strtotime($log['transaction']['transaction_date']));
	        $rating['transaction_from'] = $log['transaction']['transaction_from'];

	        $trxDate = MyHelper::dateFormatInd($log['transaction']['transaction_date'], true, false, true);
	        $outletName = $log['transaction']['outlet']['outlet_name'];
	        $rating['title'] = $title;
	        $rating['messages'] = "Dapatkan loyalty points dengan memberikan penilaian atas transaksi Anda pada hari:  \n <b>" . $trxDate . " di " . $outletName . "</b>";

	        $rating['outlet'] = [
				'id_outlet' => $log['transaction']['outlet']['id_outlet'],
				'outlet_code' => $log['transaction']['outlet']['outlet_code'],
				'outlet_name' => $log['transaction']['outlet']['outlet_name'],
				'outlet_address' => $log['transaction']['outlet']['outlet_address'],
				'outlet_latitude' => $log['transaction']['outlet']['outlet_latitude'],
				'outlet_longitude' => $log['transaction']['outlet']['outlet_longitude']
			];
			$rating['brand'] = [
				'id_brand' => $log['transaction']['outlet']['brands'][0]['id_brand'],
				'brand_code' => $log['transaction']['outlet']['brands'][0]['code_brand'],
				'brand_name' => $log['transaction']['outlet']['brands'][0]['name_brand'],
				'brand_logo' => $log['transaction']['outlet']['brands'][0]['logo_brand'],
				'brand_logo_landscape' => $log['transaction']['outlet']['brands'][0]['logo_landscape_brand']
			];
	        $rating['question_text'] = Setting::where('key','rating_question_text')->pluck('value_text')->first()?:'How about our Service';
			$rating['rating'] = null;
			$rating['options'] = null;
			
        	if (!empty($log['id_user_hair_stylist'])) {

        		$rating['id_user_hair_stylist'] = $log['id_user_hair_stylist'];
	        	$rating['options'] = $optionHs;

        		if (!empty($log['id_transaction_product_service'])) {
		        	$service = TransactionProductService::with('user_hair_stylist')
					        	->where('id_transaction', $log['id_transaction'])
					        	->where('id_user_hair_stylist', $log['id_user_hair_stylist'])
					        	->where('id_transaction_product_service', $log['id_transaction_product_service'])
					        	->first();
		        	$hs = $service->user_hair_stylist;
        		} else {
		        	$hs = UserHairStylist::where('id_user_hair_stylist', $log['id_user_hair_stylist'])->first();
        		}

	        	$isFavorite = FavoriteUserHiarStylist::where('id_user_hair_stylist',$log['id_user_hair_stylist'])
	        					->where('id_user', $log['id_user'])
	        					->first();

				$rating['detail_hairstylist'] = [
					'nickname' => $hs->nickname ?? null,
					'fullname' => $hs->fullname ?? null,
					'user_hair_stylist_photo' => $hs->user_hair_stylist_photo ?? null,
					'is_favorite' => $isFavorite ? 1 : 0
				];
        	} else {
	        	$rating['options'] = $optionOutlet;
        	}

        	$currentRating = UserRating::where([
        		'id_transaction' => $log['id_transaction'],
        		'id_user' => $log['id_user'],
        		'id_outlet' => $log['id_outlet'],
        		'id_user_hair_stylist' => $log['id_user_hair_stylist']
        	])
        	->first();
	        
	        if ($currentRating) {
	        	$currentOption = explode(',', $currentRating['option_value']);
	        	$rating['rating'] = [
			        "rating_value" => $currentRating['rating_value'],
			        "suggestion" => $currentRating['suggestion'],
			        "option_value" => $currentOption
	        	];
	        }

	        $ratingList[] = $rating;
        }

        $result = $ratingList;
        return MyHelper::checkGet($result);
    }

    public function report(Request $request) {
        $post = $request->json()->all();
        $showOutlet = 10;
        $counter = UserRating::select(\DB::raw('rating_value,count(id_user_rating) as total'))
        ->join('transactions','transactions.id_transaction','=','user_ratings.id_transaction')
        ->groupBy('rating_value');
        $this->applyFilter($counter,$post);
        $counter = $counter->get()->toArray();
        foreach ($counter as &$value) {
            $datax = UserRating::where('rating_value',$value['rating_value'])
                ->join('transactions','transactions.id_transaction','=','user_ratings.id_transaction')
                ->with([
                'transaction'=>function($query){
                    $query->select('id_transaction','transaction_receipt_number','transaction_from','trasaction_type','transaction_grandtotal');
                },
                'user'=>function($query){
                    $query->select('id','name','phone');
                },
                'user_hair_stylist'=>function($query){
                    $query->select('id_user_hair_stylist','nickname','fullname','phone_number');
                }
            ])->take(10);
            $this->applyFilter($datax,$post);
            $value['data'] = $datax->get();
        }
        $outlet5 = UserRating::select(\DB::raw('outlets.id_outlet,outlet_name,outlet_code,user_ratings.rating_value,count(*) as total'))
        ->join('transactions','transactions.id_transaction','=','user_ratings.id_transaction')
        ->join('outlets','transactions.id_outlet','=','outlets.id_outlet')
        ->where('rating_value','5')
        ->groupBy('outlets.id_outlet')
        ->orderBy('total','desc')
        ->take($showOutlet);
        $this->applyFilter($outlet5,$post);
        for ($i=4; $i > 0 ; $i--) { 
            $outlet = UserRating::select(\DB::raw('outlets.id_outlet,outlet_name,outlet_code,user_ratings.rating_value,count(*) as total'))
            ->join('transactions','transactions.id_transaction','=','user_ratings.id_transaction')
            ->join('outlets','transactions.id_outlet','=','outlets.id_outlet')
            ->where('rating_value',$i)
            ->groupBy('outlets.id_outlet')
            ->orderBy('total','desc')
            ->take($showOutlet);
            $this->applyFilter($outlet,$post);
            $outlet5->union($outlet);
        }
        $data['rating_item'] = $counter;
        $data['rating_item_count'] = count($counter);
        $data['rating_data'] = $outlet5->get();
        return MyHelper::checkGet($data);
    }
    // apply filter photos only/notes_only
    public function applyFilter($model,$rule,$col='user_ratings'){
        if($rule['notes_only']??false){
            $model->whereNotNull($col.'.suggestion');
            $model->where($col.'.suggestion','<>','');
        }
        if(($rule['transaction_type']??false) == 'online'){
            $model->where('trasaction_type', 'pickup order');
        } elseif (($rule['transaction_type']??false) == 'offline'){
            $model->where('trasaction_type', 'offline');
        }

        if (($rule['rating_target'] ?? false) == 'hairstylist') {
        	$model->whereNotNull($col.'.id_user_hair_stylist');
        } else {
        	$model->whereNotNull($col.'.id_outlet');
        }
        $model->whereDate($col.'.created_at','>=',$rule['date_start'])->whereDate($col.'.created_at','<=',$rule['date_end']);
    }
    public function reportOutlet(Request $request) {
        $post = $request->json()->all();
        if($post['outlet_code']??false){
            $outlet = Outlet::select(\DB::raw('outlets.id_outlet,outlets.outlet_code,outlets.outlet_name,count(f1.id_user_rating) as rating1,count(f2.id_user_rating) as rating2,count(f3.id_user_rating) as rating3,count(f4.id_user_rating) as rating4,count(f5.id_user_rating) as rating5'))
            ->where('outlet_code',$post['outlet_code'])->join('transactions','outlets.id_outlet','=','transactions.id_outlet')
            ->leftJoin('user_ratings as f1',function($join) use ($post){
                $join->on('f1.id_transaction','=','transactions.id_transaction')
                ->where('f1.rating_value','=','1');
                $this->applyFilter($join,$post,'f1');
            })
            ->leftJoin('user_ratings as f2',function($join) use ($post){
                $join->on('f2.id_transaction','=','transactions.id_transaction')
                ->where('f2.rating_value','=','2');
                $this->applyFilter($join,$post,'f2');
            })
            ->leftJoin('user_ratings as f3',function($join) use ($post){
                $join->on('f3.id_transaction','=','transactions.id_transaction')
                ->where('f3.rating_value','=','3');
                $this->applyFilter($join,$post,'f3');
            })
            ->leftJoin('user_ratings as f4',function($join) use ($post){
                $join->on('f4.id_transaction','=','transactions.id_transaction')
                ->where('f4.rating_value','=','4');
                $this->applyFilter($join,$post,'f4');
            })
            ->leftJoin('user_ratings as f5',function($join) use ($post){
                $join->on('f5.id_transaction','=','transactions.id_transaction')
                ->where('f5.rating_value','=','5');
                $this->applyFilter($join,$post,'f5');
            })->first();
            if(!$outlet){
                return MyHelper::checkGet($outlet);
            }
            $data['rating_data'] = $outlet;
            $post['id_outlet'] = $outlet->id_outlet;
            $counter['data'] = [];
            for ($i = 1; $i<=5 ;$i++) {
                $datax = UserRating::where('rating_value',$i)->with([
                    'transaction'=>function($query){
                        $query->select('id_transaction','transaction_receipt_number','transaction_from','trasaction_type','transaction_grandtotal');
                    },
                    'user'=>function($query){
                        $query->select('id','name','phone');
                    },
                    'user_hair_stylist'=>function($query){
                        $query->select('id_user_hair_stylist','nickname','fullname','phone_number');
                    }
                ])
                ->join('transactions','transactions.id_transaction','=','user_ratings.id_transaction')
                ->where('transactions.id_outlet',$outlet->id_outlet)
                ->take(10);
                $this->applyFilter($datax,$post);
                $counter['data'][$i] = $datax->get();
            }
            $data['rating_item'] = $counter;
            return MyHelper::checkGet($data);
        }else{
            $dasc = ($post['order']??'outlet_name')=='outlet_name'?'asc':'desc';
            $outlet = Outlet::select(\DB::raw('outlets.id_outlet,outlets.outlet_code,outlets.outlet_name,count(f1.id_user_rating) as rating1,count(f2.id_user_rating) as rating2,count(f3.id_user_rating) as rating3,count(f4.id_user_rating) as rating4,count(f5.id_user_rating) as rating5'))
            ->join('transactions','outlets.id_outlet','=','transactions.id_outlet')
            ->leftJoin('user_ratings as f1',function($join) use ($post){
                $join->on('f1.id_transaction','=','transactions.id_transaction')
                ->where('f1.rating_value','=','1');
                $this->applyFilter($join,$post,'f1');
            })
            ->leftJoin('user_ratings as f2',function($join) use ($post){
                $join->on('f2.id_transaction','=','transactions.id_transaction')
                ->where('f2.rating_value','=','2');
                $this->applyFilter($join,$post,'f2');
            })
            ->leftJoin('user_ratings as f3',function($join) use ($post){
                $join->on('f3.id_transaction','=','transactions.id_transaction')
                ->where('f3.rating_value','=','3');
                $this->applyFilter($join,$post,'f1');
            })
            ->leftJoin('user_ratings as f4',function($join) use ($post){
                $join->on('f4.id_transaction','=','transactions.id_transaction')
                ->where('f4.rating_value','=','4');
                $this->applyFilter($join,$post,'f4');
            })
            ->leftJoin('user_ratings as f5',function($join) use ($post){
                $join->on('f5.id_transaction','=','transactions.id_transaction')
                ->where('f5.rating_value','=','5');
                $this->applyFilter($join,$post,'f5');
            })
            ->orderBy($post['order']??'outlet_name',$dasc)
            ->groupBy('outlets.id_outlet');
            if($post['search']??false){
                $outlet->where(function($query) use($post){
                    $param = '%'.$post['search'].'%';
                    $query->where('outlet_name','like',$param)
                    ->orWhere('outlet_code','like',$param);
                });
            }
            return MyHelper::checkGet($outlet->paginate(15)->toArray());
        }
    }

    public function reportHairstylist(Request $request) {
        $post = $request->json()->all();
        if($post['id_user_hair_stylist'] ?? false){
            $hs = UserHairStylist::select(\DB::raw('
            	user_hair_stylist.id_user_hair_stylist,
            	user_hair_stylist.phone_number,
            	user_hair_stylist.nickname,
            	user_hair_stylist.fullname,
            	count(f1.id_user_rating) as rating1,
            	count(f2.id_user_rating) as rating2,
            	count(f3.id_user_rating) as rating3,
            	count(f4.id_user_rating) as rating4,
            	count(f5.id_user_rating) as rating5
        	'))
            ->where('user_hair_stylist.id_user_hair_stylist',$post['id_user_hair_stylist'])
            ->join('transaction_product_services','user_hair_stylist.id_user_hair_stylist','=','transaction_product_services.id_user_hair_stylist')
            ->leftJoin('user_ratings as f1',function($join) use ($post){
                $join->on('f1.id_transaction','=','transaction_product_services.id_transaction')
                ->where('f1.rating_value','=','1');
                $this->applyFilter($join,$post,'f1');
            })
            ->leftJoin('user_ratings as f2',function($join) use ($post){
                $join->on('f2.id_transaction','=','transaction_product_services.id_transaction')
                ->where('f2.rating_value','=','2');
                $this->applyFilter($join,$post,'f2');
            })
            ->leftJoin('user_ratings as f3',function($join) use ($post){
                $join->on('f3.id_transaction','=','transaction_product_services.id_transaction')
                ->where('f3.rating_value','=','3');
                $this->applyFilter($join,$post,'f3');
            })
            ->leftJoin('user_ratings as f4',function($join) use ($post){
                $join->on('f4.id_transaction','=','transaction_product_services.id_transaction')
                ->where('f4.rating_value','=','4');
                $this->applyFilter($join,$post,'f4');
            })
            ->leftJoin('user_ratings as f5',function($join) use ($post){
                $join->on('f5.id_transaction','=','transaction_product_services.id_transaction')
                ->where('f5.rating_value','=','5');
                $this->applyFilter($join,$post,'f5');
            })->first();
            if(!$hs){
                return MyHelper::checkGet($hs);
            }
            $data['rating_data'] = $hs;
            $counter['data'] = [];
            for ($i = 1; $i<=5 ;$i++) {
                $datax = UserRating::where('rating_value',$i)->with([
                    'transaction'=>function($query){
                        $query->select('id_transaction','transaction_receipt_number','transaction_from','trasaction_type','transaction_grandtotal');
                    },
                    'user'=>function($query){
                        $query->select('id','name','phone');
                    },
                    'user_hair_stylist'=>function($query){
                        $query->select('id_user_hair_stylist','nickname','fullname','phone_number');
                    }
                ])
                ->join('transactions','transactions.id_transaction','=','user_ratings.id_transaction')
                ->where('user_ratings.id_user_hair_stylist',$hs->id_user_hair_stylist)
                ->take(10);
                $this->applyFilter($datax,$post);
                $counter['data'][$i] = $datax->get();
            }
            $data['rating_item'] = $counter;
            return MyHelper::checkGet($data);
        }else{
            $dasc = ($post['order'] ?? 'fullname') == 'fullname' ? 'asc' : 'desc';
            $hs = UserHairStylist::select(\DB::raw('
            		user_hair_stylist.id_user_hair_stylist,
            		user_hair_stylist.phone_number,
            		user_hair_stylist.nickname,
            		user_hair_stylist.fullname,
            		count(f1.id_user_rating) as rating1,
            		count(f2.id_user_rating) as rating2,
            		count(f3.id_user_rating) as rating3,
            		count(f4.id_user_rating) as rating4,
            		count(f5.id_user_rating) as rating5
        		'))
            ->join('transaction_product_services','user_hair_stylist.id_user_hair_stylist','=','transaction_product_services.id_user_hair_stylist')
            ->leftJoin('user_ratings as f1',function($join) use ($post){
                $join->on('f1.id_transaction','=','transaction_product_services.id_transaction')
                ->where('f1.rating_value','=','1');
                $this->applyFilter($join,$post,'f1');
            })
            ->leftJoin('user_ratings as f2',function($join) use ($post){
                $join->on('f2.id_transaction','=','transaction_product_services.id_transaction')
                ->where('f2.rating_value','=','2');
                $this->applyFilter($join,$post,'f2');
            })
            ->leftJoin('user_ratings as f3',function($join) use ($post){
                $join->on('f3.id_transaction','=','transaction_product_services.id_transaction')
                ->where('f3.rating_value','=','3');
                $this->applyFilter($join,$post,'f1');
            })
            ->leftJoin('user_ratings as f4',function($join) use ($post){
                $join->on('f4.id_transaction','=','transaction_product_services.id_transaction')
                ->where('f4.rating_value','=','4');
                $this->applyFilter($join,$post,'f4');
            })
            ->leftJoin('user_ratings as f5',function($join) use ($post){
                $join->on('f5.id_transaction','=','transaction_product_services.id_transaction')
                ->where('f5.rating_value','=','5');
                $this->applyFilter($join,$post,'f5');
            })
            ->orderBy($post['order'] ?? 'fullname',$dasc)
            ->groupBy('user_hair_stylist.id_user_hair_stylist');
            if($post['search'] ?? false){
                $hs->where(function($query) use($post){
                    $param = '%'.$post['search'].'%';
                    $query->where('fullname','like',$param)
                    ->orWhere('nickname','like',$param);
                });
            }
            return MyHelper::checkGet($hs->paginate(15)->toArray());
        }
    }

    public function getList(Request $request) {
        $post = $request->json()->all();
        $user = clone $request->user();

        $logTrxs = UserRatingLog::where('id_user', $user->id)
        		->groupBy('id_transaction')
				->get();

        $logRatings = [];
        $interval = Setting::where('key','popup_min_interval')->pluck('value')->first() ?: 900;
        $max_date = date('Y-m-d',time() - ((Setting::select('value')->where('key','popup_max_days')->pluck('value')->first()?:3) * 86400));
        $maxList = Setting::where('key','popup_max_list')->pluck('value')->first() ?: 5;
        $maxRefuse = Setting::where('key','popup_max_refuse')->pluck('value')->first() ?: 3;

        if (empty($logTrxs)) {
            return MyHelper::checkGet([]);
        }

        foreach ($logTrxs as $logTrx) {

        	$trx = Transaction::where('id_transaction', $logTrx->id_transaction)->first();
        	if (!$trx) {
        		continue;
        	}

			$logs = UserRatingLog::where('id_user', $user->id)
					->where('id_transaction', $logTrx->id_transaction)
					->whereNotNull('id_user_hair_stylist')
					->get();

			foreach ($logs as $log) {
				if ($log->refuse_count >= $maxRefuse
					|| (strtotime($log->last_popup) + $interval) > time()
	            ) {
	                continue;
	            }

	            $log->refuse_count++;
	            $log->last_popup = date('Y-m-d H:i:s');
	            $log->save();
				$logRatings[] = $log;

				if ($maxList <= count($logRatings)) {
	            	break;
	            }
			}

            if ($maxList <= count($logRatings)) {
            	break;
            }
        }

        if (empty($logRatings)) {
            return MyHelper::checkGet([]);
        }

        $ratingList = [];
        $title = 'Beri Penilaian';
        foreach ($logRatings as $key => $log) {
			$rating['id'] = $log['id_transaction'];
			$rating['id_transaction_product_service'] = $log['id_transaction_product_service'];
    		$rating['id_user_hair_stylist'] = $log['id_user_hair_stylist'];
	        $rating['transaction_receipt_number'] = $log['transaction']['transaction_receipt_number'];
	        $rating['transaction_date'] = date('d M Y H:i',strtotime($log['transaction']['transaction_date']));
	        $rating['transaction_from'] = $log['transaction']['transaction_from'];
	        $rating['outlet_rating'] = null;
	        $rating['hairstylist_rating'] = null;

	        $trxDate = MyHelper::dateFormatInd($log['transaction']['transaction_date'], true, false, true);
	        $outletName = $log['transaction']['outlet']['outlet_name'];
	        $rating['title'] = $title;
	        $rating['messages'] = "Dapatkan loyalty points dengan memberikan penilaian atas transaksi Anda pada hari:  \n <b>" . $trxDate;
	        if ($outletName) {
	        	$rating['messages'] = $rating['messages'] . " di " . $outletName . "</b>";
	        }

	        $rating['outlet'] = null;
	        if (!empty($log['transaction']['outlet'])) {
		        $rating['outlet'] = [
					'id_outlet' => $log['transaction']['outlet']['id_outlet'],
					'outlet_code' => $log['transaction']['outlet']['outlet_code'],
					'outlet_name' => $log['transaction']['outlet']['outlet_name'],
					'outlet_address' => $log['transaction']['outlet']['outlet_address'],
					'outlet_latitude' => $log['transaction']['outlet']['outlet_latitude'],
					'outlet_longitude' => $log['transaction']['outlet']['outlet_longitude']
				];
	        }

			$rating['brand'] = null;
	        if (!empty($log['transaction']['outlet']['brands'])) {
				$rating['brand'] = [
					'id_brand' => $log['transaction']['outlet']['brands'][0]['id_brand'],
					'brand_code' => $log['transaction']['outlet']['brands'][0]['code_brand'],
					'brand_name' => $log['transaction']['outlet']['brands'][0]['name_brand'],
					'brand_logo' => $log['transaction']['outlet']['brands'][0]['logo_brand'],
					'brand_logo_landscape' => $log['transaction']['outlet']['brands'][0]['logo_landscape_brand']
				];
			}
			
			if (!empty($log['id_transaction_product_service'])) {
	        	$service = TransactionProductService::with('user_hair_stylist')
				        	->where('id_transaction', $log['id_transaction'])
				        	->where('id_user_hair_stylist', $log['id_user_hair_stylist'])
				        	->where('id_transaction_product_service', $log['id_transaction_product_service'])
				        	->first();

	        	$hs = $service->user_hair_stylist;
			} else {
	        	$hs = UserHairStylist::where('id_user_hair_stylist', $log['id_user_hair_stylist'])->first();
			}

        	$isFavorite = FavoriteUserHiarStylist::where('id_user_hair_stylist',$log['id_user_hair_stylist'])
	        					->where('id_user', $log['id_user'])
	        					->first();

			$rating['detail_hairstylist'] = [
				'nickname' => $hs->nickname ?? null,
				'fullname' => $hs->fullname ?? null,
				'user_hair_stylist_photo' => $hs->user_hair_stylist_photo ?? null,
				'is_favorite' => $isFavorite ? 1 : 0
			];

        	$currentRatingHs = UserRating::where([
        		'id_transaction' => $log['id_transaction'],
        		'id_user' => $log['id_user'],
        		'id_outlet' => null,
        		'id_user_hair_stylist' => $log['id_user_hair_stylist'],
        		'id_transaction_product_service' => $log['id_transaction_product_service'],
        	])
        	->first();
	        
	        if ($currentRatingHs) {
		        $rating['hairstylist_rating'] = $currentRatingHs['rating_value'];
	        }

	        $currentRatingOutlet = UserRating::where([
        		'id_transaction' => $log['id_transaction'],
        		'id_user' => $log['id_user'],
        		'id_outlet' => $log['transaction']['outlet']['id_outlet'] ?? null,
        		'id_user_hair_stylist' => null
        	])
        	->first();
	        
	        if ($currentRatingOutlet) {
		        $rating['outlet_rating'] = $currentRatingOutlet['rating_value'];
	        }

	        $ratingList[] = $rating;
        }

        $result = $ratingList;
        return MyHelper::checkGet($result);
    }

    public function getRated(Request $request) {
        $post = $request->json()->all();
        $user = clone $request->user();

        $logRatings = UserRating::where('id_user', $user->id)
						->with('transaction.outlet.brands');

        if (isset($post['id'])) {
            $id_transaction = $post['id'];

            $transaction = Transaction::find($id_transaction);
            if(!$transaction){
                return [
                    'status' => 'fail',
                    'messages' => ['Transaction not found']
                ];
            }

	        $logRatings = $logRatings->where('id_transaction', $id_transaction);

	        if (isset($post['id_transaction_product_service'])) {
	        	$logRatings = $logRatings->where('id_transaction_product_service', $post['id_transaction_product_service']);
	        }

        }

        $logRatings = $logRatings->get();

        $ratingList = [];
        foreach ($logRatings as $key => $log) {
			$rating['id'] = $log['id_transaction'];
			$rating['id_transaction_product_service'] = $log['id_transaction_product_service'];
			$rating['id_user_hair_stylist'] = null;
			$rating['detail_hairstylist'] = null;
	        $rating['transaction_receipt_number'] = $log['transaction']['transaction_receipt_number'];
	        $rating['transaction_date'] = date('d M Y H:i',strtotime($log['transaction']['transaction_date']));
	        $rating['transaction_from'] = $log['transaction']['transaction_from'];

	        $trxDate = MyHelper::dateFormatInd($log['transaction']['transaction_date'], true, false, true);
	        $outletName = $log['transaction']['outlet']['outlet_name'];

	        $rating['outlet'] = null;
	        if (!empty($log['transaction']['outlet'])) {
		        $rating['outlet'] = [
					'id_outlet' => $log['transaction']['outlet']['id_outlet'],
					'outlet_code' => $log['transaction']['outlet']['outlet_code'],
					'outlet_name' => $log['transaction']['outlet']['outlet_name'],
					'outlet_address' => $log['transaction']['outlet']['outlet_address'],
					'outlet_latitude' => $log['transaction']['outlet']['outlet_latitude'],
					'outlet_longitude' => $log['transaction']['outlet']['outlet_longitude']
				];
	        }
	        $rating['brand'] = null;
	        if (!empty($log['transaction']['outlet']['brands'])) {
				$rating['brand'] = [
					'id_brand' => $log['transaction']['outlet']['brands'][0]['id_brand'],
					'brand_code' => $log['transaction']['outlet']['brands'][0]['code_brand'],
					'brand_name' => $log['transaction']['outlet']['brands'][0]['name_brand'],
					'brand_logo' => $log['transaction']['outlet']['brands'][0]['logo_brand'],
					'brand_logo_landscape' => $log['transaction']['outlet']['brands'][0]['logo_landscape_brand']
				];
	        }
			$rating['rating'] = null;
			
        	if (!empty($log['id_user_hair_stylist'])) {

        		$rating['id_user_hair_stylist'] = $log['id_user_hair_stylist'];

        		if (!empty($log['id_transaction_product_service'])) {
		        	$service = TransactionProductService::with('user_hair_stylist')
					        	->where('id_transaction', $log['id_transaction'])
					        	->where('id_user_hair_stylist', $log['id_user_hair_stylist'])
					        	->where('id_transaction_product_service', $log['id_transaction_product_service'])
					        	->first();
		        	$hs = $service->user_hair_stylist;
        		} else {
        			$hs = UserHairStylist::where('id_user_hair_stylist', $log['id_user_hair_stylist'])->first();
        		}

	        	$isFavorite = FavoriteUserHiarStylist::where('id_user_hair_stylist',$log['id_user_hair_stylist'])
	        					->where('id_user', $log['id_user'])
	        					->first();

				$rating['detail_hairstylist'] = [
					'nickname' => $hs->nickname ?? null,
					'fullname' => $hs->fullname ?? null,
					'user_hair_stylist_photo' => $hs->user_hair_stylist_photo ?? null,
					'is_favorite' => $isFavorite ? 1 : 0
				];
        	}

        	$currentRating = $log;
	        
	        if ($currentRating) {
	        	$currentOption = explode(',', $currentRating['option_value']);
	        	$rating['rating'] = [
			        "rating_value" => $currentRating['rating_value'],
			        "suggestion" => $currentRating['suggestion'],
			        "option_value" => $currentOption
	        	];
	        }

	        $ratingList[] = $rating;
        }

        $result = $ratingList;
        return MyHelper::checkGet($result);
    }
}
