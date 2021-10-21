<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistAnnouncementRule;
use Modules\Recruitment\Entities\HairstylistAnnouncementRuleParent;
use DB;

class ApiAnnouncement extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function createAnnouncement(Request $request){
		$post = $request->json()->all(); 
		
		$data['content'] 		= $post['announcement_subject'];
		
		if(!empty($post['announcement_date_start'])){
			$datetimearr 				= explode(' - ',$post['announcement_date_start']);
			$datearr 					= explode(' ',$datetimearr[0]);
			$date 						= date("Y-m-d", strtotime($datearr[2].", ".$datearr[1]." ".$datearr[0]));
			$data['date_start'] 	= $date." ".$datetimearr[1].":00";
		} else $data['date_start'] = null;
		
		if(!empty($post['announcement_date_end'])){
			$datetimearr 				= explode(' - ',$post['announcement_date_end']);
			$datearr 					= explode(' ',$datetimearr[0]);
			$date 						= date("Y-m-d", strtotime($datearr[2].", ".$datearr[1]." ".$datearr[0]));
			$data['date_end'] 	= $date." ".$datetimearr[1].":00";
		} else $data['date_end'] = null;

		DB::beginTransaction();
		if (isset($post['id_hairstylist_announcement'])) {
			$queryAnn = HairstylistAnnouncement::where('id_hairstylist_announcement', $post['id_hairstylist_announcement'])->first();
			$queryAnn->update($data);
		} else {
			$queryAnn = HairstylistAnnouncement::create($data);
		}
		if($queryAnn){
			$data = [];
			$data['id_hairstylist_announcement'] = $queryAnn->id_hairstylist_announcement;

			$queryAnnRule = $this->insertCondition('hairstylist_announcement', $data['id_hairstylist_announcement'], $post['rule'], $post['operator']);
			if(isset($queryAnnRule['status']) && $queryAnnRule['status'] == 'success'){
				$resultrule = $queryAnnRule['data'];
			}else{
				DB::rollBack();
				$result = [
					'status'  => 'fail',
					'messages'  => ['Create Announcement Failed']
				];
			}
			$result = [
				'status'  => 'success',
				'result'  => 'Set Announcement & Rule Success',
				'announcement'  => $queryAnn,
				'rule'  => $resultrule
			];
			DB::commit();
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['Create Announcement Failed']
				];
		}
		
		return response()->json($result);
	}

	public static function insertCondition($type, $id, $conditions, $rule){
		if($type == 'hairstylist_announcement'){
			$deleteRuleParent = HairstylistAnnouncementRuleParent::where('id_'.$type, $id)->get();
			if(count($deleteRuleParent)>0){
				foreach ($deleteRuleParent as $key => $value) {
					$delete = HairstylistAnnouncementRule::where('id_'.$type.'_rule_parent', $value['id_'.$type.'_rule_parent'])->delete();
				}
				$deleteRuleParent = HairstylistAnnouncementRuleParent::where('id_'.$type, $id)->delete();
			}
		}

		$operatorexception = [
			'id_brand',
			'id_province',
			'id_city',
			'id_outlet',
			'hairstylist_level'
		];

		$data_rule = array();

		$dataRuleParent['id_'.$type] = $id;
		$dataRuleParent[$type.'_rule'] = $ruleParent['rule'] ?? $rule;
		$dataRuleParent[$type.'_rule_next'] = $ruleParent['rule_next'] ?? 'and';

		if ($type == 'hairstylist_announcement') {
			$createRuleParent = HairstylistAnnouncementRuleParent::create($dataRuleParent);
		}

		if(!$createRuleParent){
			return ['status' => 'fail'];
		}

		foreach ($conditions as $i => $row) {
			$condition['id_'.$type.'_rule_parent'] = $createRuleParent['id_'.$type.'_rule_parent'];
			$condition[$type.'_rule_subject'] = $row['subject'];

			if ($row['subject'] == 'all_data') {
				$condition[$type.'_rule_operator'] = "";
            } elseif (in_array($row['subject'], $operatorexception)) {
				$condition[$type.'_rule_operator'] = '=';
			} else {
				$condition[$type.'_rule_operator'] = $row['operator'];
			}

            $condition[$type.'_rule_param_id'] = NULL;
			if ($row['subject'] == 'all_data') {
				$condition[$type.'_rule_param'] = "";
			} else {
				$condition[$type.'_rule_param'] = $row['parameter'];
			}

			$condition['created_at'] =  date('Y-m-d H:i:s');
			$condition['updated_at'] =  date('Y-m-d H:i:s');

			array_push($data_rule, $condition);
		}

		if ($type == 'hairstylist_announcement') {
			$insert = HairstylistAnnouncementRule::insert($data_rule);
		}

		if($insert){
			return ['status' => 'success', 'data' =>  $data_rule];
		}else{
			return ['status' => 'fail'];
		}
	}

	public function listAnnouncement(Request $request)
	{
        $post = $request->json()->all();
        $ann = HairstylistAnnouncement::with('hairstylist_announcement_rule_parents.rules');

        if (!empty($post['date_start']) && !empty($post['date_end'])) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $ann->whereDate('date_start', '>=', $start_date)->whereDate('date_end', '<=', $end_date);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'content') {
                            if ($row['operator'] == '=') {
                                $ann->where('content', $row['parameter']);
                            } else {
                                $ann->where('content', 'like', '%'.$row['parameter'].'%');
                            }
                        }
                    }
                }
            } else {
            	$ann->where(function ($subquery) use ($post) {
            		foreach ($post['conditions'] as $row) {
            			if (isset($row['subject'])) {
            				if ($row['subject'] == 'content') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('content', $row['parameter']);
            					} else {
            						$subquery->orWhere('content', 'like', '%'.$row['parameter'].'%');
            					}
            				}
            			}
                    }
                });
            }
        }

        $ann = $ann->paginate(10);

        return response()->json(MyHelper::checkGet($ann));
    }

    public function detailAnnouncement(Request $request){
		$post = $request->json()->all(); 
	
		$ann = HairstylistAnnouncement::with('hairstylist_announcement_rule_parents.rules')
				->where('id_hairstylist_announcement', $post['id_hairstylist_announcement'])
				->first();
		
		if(isset($ann) && !empty($ann)) {
			$result = [
					'status'  => 'success',
					'result'  => $ann
				];
		} else {
			$result = [
					'status'  => 'fail',
					'messages'  => ['No Announcement']
				];
		}
		return response()->json($result);
	}

	public function deleteAnnouncement(Request $request){
		$post = $request->json()->all();

		$checkAnn = HairstylistAnnouncement::where('id_hairstylist_announcement','=',$post['id_hairstylist_announcement'])->first();
		if($checkAnn){
			$delete = HairstylistAnnouncement::where('id_hairstylist_announcement','=',$post['id_hairstylist_announcement'])->delete();
			
			if($delete){
				$result = ['status'	=> 'success',
						   'result'	=> ['Announcement has been deleted']
						  ];
			} else {
				$result = [
						'status'	=> 'fail',
						'messages'	=> ['Delete Failed']
						];
			}
		} else {
			$result = [
						'status'	=> 'fail',
						'messages'	=> ['Announcement Not Found']
						];
		}
		return response()->json($result);
	}
}
