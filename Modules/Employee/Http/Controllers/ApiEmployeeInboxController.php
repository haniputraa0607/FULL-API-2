<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use Maatwebsite\Excel\Concerns\ToArray;
use Modules\Employee\Entities\EmployeeInbox;
use App\Lib\MyHelper;


class ApiEmployeeInboxController extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
    }

    public function listInbox(Request $request){
        $post = $request->all();
        $user = $request->user();
        $id_employee = $user['id'];
        $id_outlet = $user['id_outlet'];

        $category = null;
        if($post['category'] == 'Presensi'){
            $category = 'Attendance';
        }elseif($post['category'] == 'Cuti'){
            $category = 'Time Off';
        }elseif($post['category'] == 'Lembur'){
            $category = 'Overtime';
        }

    	$max_date = date('Y-m-d',time() - ((Setting::select('value')->where('key','inbox_max_days')->pluck('value')->first()?:30) * 86400));
        $inbox = EmployeeInbox::where('id_employee',$id_employee)->whereDate('inboxes_send_at','>',$max_date);

        if ($category!=null) {
    		$inbox->where('inboxes_category', $category);
    	}

        $inbox = $inbox->select('id_employee_inboxes','inboxes_send_at','inboxes_subject','inboxes_content','inboxes_category', 'read', 'inboxes_clickto', 'inboxes_id_reference')->get()->toArray();

        $send = [];
        $unread = 0;
        foreach($inbox ?? [] as $val){
            if($val['read'] == 0){
                $unread = $unread + 1;
            }
            $send[] = [
                'id_inbox' => $val['id_employee_inboxes'],
                'date' => MyHelper::dateFormatInd($val['inboxes_send_at'], true, false, false),
                'subject' => $val['inboxes_subject'],
                'content' => $val['inboxes_content'],
                'category' => $val['inboxes_category'],
                'click_action' => $val['inboxes_clickto'],
                'id_reference' => $val['inboxes_id_reference'],
                'read' => $val['read']
            ];
        }

		return MyHelper::checkGet(['unread'=>$unread,'result'=>$send]);
    }

    public function listReqApproval(Request $request){
        $post = $request->all();
        $user = $request->user();
        $id_employee = $user['id'];
        $id_outlet = $user['id_outlet'];

        
        
    }
}
