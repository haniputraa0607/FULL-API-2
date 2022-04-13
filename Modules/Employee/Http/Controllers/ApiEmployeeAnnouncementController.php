<?php

namespace Modules\Employee\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\User;
use Modules\Employee\Entities\EmployeeAnnouncement;
use Modules\Employee\Entities\EmployeeAnnouncementRule;
use Modules\Employee\Entities\EmployeeAnnouncementRuleParent;
use DB;

class ApiEmployeeAnnouncementController extends Controller
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
		if (isset($post['id_employee_announcement'])) {
			$queryAnn = EmployeeAnnouncement::where('id_employee_announcement', $post['id_employee_announcement'])->first();
			$queryAnn->update($data);
		} else {
			$queryAnn = EmployeeAnnouncement::create($data);
		}
		if($queryAnn){
			$data = [];
			$data['id_employee_announcement'] = $queryAnn->id_employee_announcement;

			$queryAnnRule = $this->insertCondition('employee_announcement', $data['id_employee_announcement'], $post['rule'], $post['operator']);
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
		if($type == 'employee_announcement'){
			$deleteRuleParent = EmployeeAnnouncementRuleParent::where('id_'.$type, $id)->get();
			if(count($deleteRuleParent)>0){
				foreach ($deleteRuleParent as $key => $value) {
					$delete = EmployeeAnnouncementRule::where('id_'.$type.'_rule_parent', $value['id_'.$type.'_rule_parent'])->delete();
				}
				$deleteRuleParent = EmployeeAnnouncementRuleParent::where('id_'.$type, $id)->delete();
			}
		}

		$operatorexception = [
			'id_province',
			'id_city',
			'id_outlet',
			'id_role'
		];

		$data_rule = array();

		$dataRuleParent['id_'.$type] = $id;
		$dataRuleParent[$type.'_rule'] = $ruleParent['rule'] ?? $rule;
		$dataRuleParent[$type.'_rule_next'] = $ruleParent['rule_next'] ?? 'and';

		if ($type == 'employee_announcement') {
			$createRuleParent = EmployeeAnnouncementRuleParent::create($dataRuleParent);
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

		if ($type == 'employee_announcement') {
			$insert = EmployeeAnnouncementRule::insert($data_rule);
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
        $ann = EmployeeAnnouncement::with('employee_announcement_rule_parents.rules');

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
}
