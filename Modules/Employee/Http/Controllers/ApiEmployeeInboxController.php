<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use DB;

use App\Http\Models\Setting;
use Maatwebsite\Excel\Concerns\ToArray;
use Modules\Employee\Entities\EmployeeInbox;
use App\Lib\MyHelper;
use Modules\Users\Entities\RolesFeature;
use Modules\Employee\Entities\EmployeeAttendance;
use Modules\Employee\Entities\EmployeeAttendanceRequest;
use Modules\Employee\Entities\EmployeeAttendanceLog;
use Modules\Employee\Entities\EmployeeOutletAttendance;
use Modules\Employee\Entities\EmployeeOutletAttendanceRequest;
use Modules\Employee\Entities\EmployeeOutletAttendanceLog;
use Modules\Employee\Entities\EmployeeTimeOff;
use Modules\Employee\Entities\EmployeeOvertime;
use Modules\Employee\Entities\EmployeeReimbursement;
use Modules\Employee\Entities\AssetInventoryLog;
use Modules\Employee\Entities\EmployeeTimeOffImage;
use Modules\Employee\Entities\EmployeeOfficeHour;
use Modules\Product\Entities\RequestProduct;
use Modules\Product\Entities\RequestProductDetail;
use Modules\Product\Entities\RequestProductImage;
use Modules\Employee\Entities\EmployeeChangeShift;
use Modules\Employee\Entities\EmployeeSchedule;
use Modules\Employee\Entities\EmployeeOfficeHourShift;
use App\Http\Models\Province;
use App\Http\Models\Outlet;
use App\Http\Models\User;
use Modules\Employee\Entities\EmployeeScheduleDate;
use Modules\Employee\Http\Requests\AssetInventory\ApproveLoan;
use Modules\Employee\Http\Requests\AssetInventory\ApproveReturn;


class ApiEmployeeInboxController extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
    }

    public function getListInbox(Request $request){
        $user = $request->user();
        $id_employee = $user['id'];
        $id_outlet = $user['id_outlet'];

        $max_date = date('Y-m-d',time() - ((Setting::select('value')->where('key','inbox_max_days')->pluck('value')->first()?:30) * 86400));
        $inbox = EmployeeInbox::where('id_employee',$id_employee)->whereDate('inboxes_send_at','>',$max_date)->get()->keyBy('inboxes_category');

        $flag_all = 0;
        $tab = [
            [
                'name' => 'Semua',
                'value' => null,
                'flag' => $flag_all
            ],
        ];

        foreach($inbox ?? [] as $key => $value){
            $flag = 0;
            $flag = EmployeeInbox::where('id_employee',$id_employee)->whereDate('inboxes_send_at','>',$max_date)->where('inboxes_category',$key)->where('read',0)->count();
            if($key==''){
                $tab[] = [
                    'name' => 'Lain-lain',
                    'value' => $key,
                    'flag' => $flag
                ];
            }else{
                $tab[] = [
                    'name' => $key,
                    'value' => $key,
                    'flag' => $flag
                ];
            }
            $flag_all = $flag_all + $flag;
        }
        $tab[0]['flag'] = $flag_all;

        return MyHelper::checkGet($tab);
    }

    public function listInbox(Request $request){
        $post = $request->all();
        $user = $request->user();
        $id_employee = $user['id'];
        $id_outlet = $user['id_outlet'];

        $category = $post['category'];

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

    public function getListReqApproval(Request $request){
        $post = $request->all();
        $user = $request->user();
        $id_employee = $user['id'];
        $id_outlet = $user['id_outlet'];
        
        $roles = RolesFeature::where('id_role', $user['id_role'])->select('id_feature')->get()->toArray();
        $roles = array_pluck($roles, 'id_feature');

        $tab[] = null;
        $key = 1;
        $flag_all = 0;

        if(in_array('497',$roles) || in_array('500',$roles)){
            $flag = 0;
            if(in_array('497',$roles)){
                $a_pending = EmployeeAttendanceLog::join('employee_attendances', 'employee_attendances.id_employee_attendance', 'employee_attendance_logs.id_employee_attendance')->where('employee_attendances.id_outlet', $id_outlet)->where('employee_attendance_logs.status', 'Pending')->where('read',0)->count();
                if($a_pending>0){
                    $flag = $flag + $a_pending;
                    $flag_all = $flag_all + $a_pending;
                }
            }
            if(in_array('500',$roles)){
                $a_req = EmployeeAttendanceRequest::where('id_outlet', $id_outlet)->where('status', 'Pending')->where('read',0)->count();
                if($a_req>0){
                    $flag = $flag + $a_req;
                    $flag_all = $flag_all + $a_req;
                }
            }
            $tab[$key] = [
                'name' => 'Presensi',
                'value' => 'attendance',
                'flag' => $flag
            ];
            $key++;
        }

        if(in_array('503',$roles) || in_array('506',$roles)){
            $flag = 0;
            if(in_array('503',$roles)){
                $a_pending = EmployeeOutletAttendanceLog::join('employee_outlet_attendances', 'employee_outlet_attendances.id_employee_outlet_attendance', 'employee_outlet_attendance_logs.id_employee_outlet_attendance')->join('users','users.id','employee_outlet_attendances.id')->where('users.id_outlet', $id_outlet)->where('employee_outlet_attendance_logs.status', 'Pending')->where('read',0)->count();
                if($a_pending>0){
                    $flag = $flag + $a_pending;
                    $flag_all = $flag_all + $a_pending;
                }
            }
            if(in_array('506',$roles)){
                $a_req = EmployeeOutletAttendanceRequest::join('users','users.id','employee_outlet_attendance_requests.id')->where('users.id_outlet', $id_outlet)->where('employee_outlet_attendance_requests.status', 'Pending')->where('read',0)->count();
                if($a_req>0){
                    $flag = $flag + $a_req;
                    $flag_all = $flag_all + $a_req;
                }
            }
            $tab[$key] = [
                'name' => 'Presensi Outlet',
                'value' => 'attendance_outlet',
                'flag' => $flag
            ];
            $key++;
        }

        if(in_array('510',$roles)){
            $flag = 0;
            if(in_array('529',$roles)){
                $time_off = EmployeeTimeOff::where('employee_time_off.id_outlet',$id_outlet)->whereNull('employee_time_off.approve_by')->whereNull('employee_time_off.reject_at')->where('employee_time_off.status','Manager Approved')->where('employee_time_off.read',0)->count();
            }else{
                $time_off = EmployeeTimeOff::join('employees','employees.id_user','employee_time_off.id_employee')->where('employees.id_manager', $id_employee)->where('employee_time_off.id_outlet',$id_outlet)->whereNull('employee_time_off.approve_by')->whereNull('employee_time_off.reject_at')->where('employee_time_off.status','Pending')->where('employee_time_off.read',0)->count();
            }
            
            if($time_off>0){
                $flag = $flag + $time_off;
                $flag_all = $flag_all + $time_off;
            }
            $tab[$key] = [
                'name' => 'Cuti',
                'value' => 'time_off',
                'flag' => $flag,
            ];
            $key++;
        }

        if(in_array('514',$roles)){
            $flag = 0;
            if(in_array('529',$roles)){
                $overtime = EmployeeOvertime::where('employee_overtime.id_outlet',$id_outlet)->whereNull('employee_overtime.approve_by')->whereNull('employee_overtime.reject_at')->where('employee_overtime.status','Manager Approved')->where('employee_overtime.read',0)->count();
            }else{
                $overtime = EmployeeOvertime::join('employees','employees.id_user','employee_overtime.id_employee')->where('employees.id_manager', $id_employee)->where('employee_overtime.id_outlet',$id_outlet)->whereNull('employee_overtime.approve_by')->whereNull('employee_overtime.reject_at')->where('employee_overtime.status','Pending')->where('employee_overtime.read',0)->count();
            }

            if($overtime>0){
                $flag = $flag + $overtime;
                $flag_all = $flag_all + $overtime;
            }
            $tab[$key] = [
                'name' => 'Lembur',
                'value' => 'overtime',
                'flag' => $flag,
            ];
            $key++;
        }

        if(in_array('546',$roles)){
            $flag = 0;
            $change_shift = EmployeeChangeShift::join('users','users.id','employee_change_shifts.id_user')->where('users.id_outlet',$id_outlet)->where('employee_change_shifts.status','Pending')->where('employee_change_shifts.read',0)->count();
            if($change_shift>0){
                $flag = $flag + $change_shift;
                $flag_all = $flag_all + $change_shift;
            }
            $tab[$key] = [
                'name' => 'Atur Shift',
                'value' => 'change_shift',
                'flag' => $flag,
            ];
            $key++;
        }

        if(in_array('517',$roles)){
            $flag = 0;
            $reim = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')->where('users.id_outlet', $id_outlet)->where('employee_reimbursements.status', 'Pending')->where('read',0)->count();
            if($reim>0){
                $flag = $flag + $reim;
                $flag_all = $flag_all + $reim;
            }
            $tab[$key] = [
                'name' => 'Pengembalian Dana',
                'value' => 'reimbursement',
                'flag' => $flag,
            ];
            $key++;
        }

        if(in_array('520',$roles)){
            $flag = 0;
            $loan = AssetInventoryLog::join('users','users.id','asset_inventory_logs.id_user')->where('users.id_outlet', $id_outlet)->where('asset_inventory_logs.type_asset_inventory','Loan')->where('asset_inventory_logs.status_asset_inventory','Pending')->where('read',0)->count();
            if($loan>0){
                $flag = $flag + $loan;
                $flag_all = $flag_all + $loan;
            }
            $tab[$key] = [
                'name' => 'Peminjaman Barang',
                'value' => 'loan_assets',
                'flag' => $flag,
            ];
            $key++;
        }

        if(in_array('523',$roles)){
            $flag = 0;
            $ret = AssetInventoryLog::join('users','users.id','asset_inventory_logs.id_user')->where('users.id_outlet', $id_outlet)->where('asset_inventory_logs.type_asset_inventory','Return')->where('asset_inventory_logs.status_asset_inventory','Pending')->where('read',0)->count();
            if($ret>0){
                $flag = $flag + $ret;
                $flag_all = $flag_all + $ret;
            }
            $tab[$key] = [
                'name' => 'Pengembalian Barang',
                'value' => 'return_assets',
                'flag' => $flag,
            ];
            $key++;
        }

        if(in_array('415',$roles)){
            $flag = 0;
            $req_product = RequestProduct::where('id_outlet',$id_outlet)->where('status','Pending')->where('read',0)->count();
            if($req_product>0){
                $flag = $flag + $req_product;
                $flag_all = $flag_all + $req_product;
            }
            $tab[$key] = [
                'name' => 'Permintaan Product',
                'value' => 'request_product',
                'flag' => $flag,
            ];
            $key++;
        }

        if($key>1){
            $tab[0] = [
                'name' => 'Semua',
                'value' => 'all',
                'flag' => $flag_all,
            ];
        }
        
        return MyHelper::checkGet($tab);
    }

    public function listReqApproval(Request $request){
        $post = $request->all();
        $user = $request->user();
        $id_employee = $user['id'];
        $id_outlet = $user['id_outlet'];
        $key_id = null;
        $id_detail = null;
        $category = null;

        $outlet = $request->user()->outlet()->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        $time_zone = [
            '7' => 'WIB',
            '8' => 'WITA',
            '9' => 'WIT',
        ];
        
        if(isset($post['id']) && !empty($post['id'])){
            $array_id = explode('-',$post['id']);
            $key_id = $array_id[0];
            $id_detail = $array_id[1];
        }

        if(isset($post['category']) && !empty($post['category'])){
            $category = $post['category'];
        }
        
        $roles = RolesFeature::where('id_role', $user['id_role'])->select('id_feature')->get()->toArray();
        $roles = array_pluck($roles, 'id_feature');
        $send = [];
        
        if($category=='attendance' || $category == 'all' || $key_id == 'attendance_pending' || $key_id == 'attendance_request'){
            if(in_array('497',$roles)){
                if(empty($key_id) || (isset($key_id) && $key_id=='attendance_pending')){
                    $a_pending = EmployeeAttendanceLog::join('employee_attendances', 'employee_attendances.id_employee_attendance', 'employee_attendance_logs.id_employee_attendance')->join('users','users.id','employee_attendances.id')->where('employee_attendances.id_outlet', $id_outlet)->where('employee_attendance_logs.status', 'Pending');
                    if($key_id == 'attendance_pending'){
                        $a_pending = $a_pending->where('employee_attendance_logs.id_employee_attendance_log', $id_detail);
                    }
                    $a_pending = $a_pending->select('employee_attendance_logs.*','users.name')->get()->toArray();
                    foreach($a_pending ?? [] as $val){
                       $data = [
                            'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                            'type' => 'Presensi',
                            'important' => 0,
                            'detail' => 'attendance_pending-'.$val['id_employee_attendance_log'],
                            'read' => $val['read'],
                            'data' => [
                                [
                                    'label' => 'Jenis Presensi',
                                    'value' => 'Pending'
                                ],
                                [
                                    'label' => 'Nama',
                                    'value' => $val['name']
                                ],
                                [
                                    'label' => 'Tanggal',
                                    'value' => MyHelper::dateFormatInd($val['datetime'], true, false, false)
                                ],
                                [
                                    'label' => 'Absensi',
                                    'value' => $val['type'] == 'clock_in' ? 'Clock In' : 'Clock Out'
                                ],
                                [
                                    'label' => 'Keterangan',
                                    'value' => $val['notes']
                                ],
                            ]
                        ];

                        if(isset($id_detail)){
                            $data['data'][] = [
                                'label' => 'Attachment Image',
                                'type' => 'Image',
                                'value' => [
                                    $val['photo_path'] ? env('STORAGE_URL_API').$val['photo_path'] : ''
                                ]
                            ];
                            $update_read = EmployeeAttendanceLog::where('id_employee_attendance_log',$val['id_employee_attendance_log'])->update(['read'=>1]);
                        }

                        $send[] = $data;
                    }
                }
            }

            if(in_array('500',$roles)){
                if(empty($key_id) || (isset($key_id) && $key_id=='attendance_request')){
                    $a_req = EmployeeAttendanceRequest::join('users','users.id','employee_attendance_requests.id')->where('users.id_outlet', $id_outlet)->where('status', 'Pending');
                    if($key_id == 'attendance_request'){
                        $a_req = $a_req->where('employee_attendance_requests.id_employee_attendance_request', $id_detail);
                    }
                    $a_req = $a_req->select('employee_attendance_requests.*','users.name')->get()->toArray();
                    foreach($a_req ?? [] as $val){
                       $data = [
                            'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                            'type' => 'Presensi',
                            'important' => 0,
                            'detail' => 'attendance_request-'.$val['id_employee_attendance_request'],
                            'read' => $val['read'],
                            'data' => [
                                [
                                    'label' => 'Jenis Presensi',
                                    'value' => 'Request'
                                ],
                                [
                                    'label' => 'Nama',
                                    'value' => $val['name']
                                ],
                                [
                                    'label' => 'Clock In',
                                    'value' => $val['clock_in'] ? date('d/m/Y H:i', strtotime($val['attendance_date'].' '.$val['clock_in'])) : '-'
                                ],
                                [
                                    'label' => 'Clock Out',
                                    'value' => $val['clock_out'] ? date('d/m/Y H:i', strtotime($val['attendance_date'].' '.$val['clock_out'])) : '-'
                                ],
                                [
                                    'label' => 'Keterangan',
                                    'value' => $val['notes']
                                ],
                            ]
                        ];
                        if(isset($id_detail)){
                            $update_read = EmployeeAttendanceRequest::where('id_employee_attendance_request',$val['id_employee_attendance_request'])->update(['read'=>1]);
                        }

                        $send[] = $data;
                    }
                }
            }
        }
        
        if($category=='attendance_outlet' || $category == 'all' || $key_id == 'attendance_outlet_pending' || $key_id == 'attendance_outlet_request'){
            if(in_array('503',$roles)){
                if(empty($key_id) || (isset($key_id) && $key_id=='attendance_outlet_pending')){
                    $a_pending = EmployeeOutletAttendanceLog::join('employee_outlet_attendances', 'employee_outlet_attendances.id_employee_outlet_attendance', 'employee_outlet_attendance_logs.id_employee_outlet_attendance')->join('users','users.id','employee_outlet_attendances.id')->where('users.id_outlet', $id_outlet)->where('employee_outlet_attendance_logs.status', 'Pending');
                    if($key_id == 'attendance_outlet_pending'){
                        $a_pending = $a_pending->where('employee_outlet_attendance_logs.id_employee_outlet_attendance_log', $id_detail);
                    }
                    $a_pending = $a_pending->select('employee_outlet_attendance_logs.*','users.name')->get()->toArray();
                    foreach($a_pending ?? [] as $val){
                       $data = [
                            'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                            'type' => 'Presensi Outlet',
                            'important' => 0,
                            'detail' => 'attendance_outlet_pending-'.$val['id_employee_outlet_attendance_log'],
                            'read' => $val['read'],
                            'data' => [
                                [
                                    'label' => 'Jenis Presensi',
                                    'value' => 'Pending'
                                ],
                                [
                                    'label' => 'Nama',
                                    'value' => $val['name']
                                ],
                                [
                                    'label' => 'Tanggal',
                                    'value' => MyHelper::dateFormatInd($val['datetime'], true, false, false)
                                ],
                                [
                                    'label' => 'Absensi',
                                    'value' => $val['type'] == 'clock_in' ? 'Clock In' : 'Clock Out'
                                ],
                                [
                                    'label' => 'Keterangan',
                                    'value' => $val['notes']
                                ],
                            ]
                        ];

                        if(isset($id_detail)){
                            $data['data'][] = [
                                'label' => 'Attachment Image',
                                'type' => 'Image',
                                'value' => [
                                    $val['photo_path'] ? env('STORAGE_URL_API').$val['photo_path'] : ''
                                ]
                            ];
                            $update_read = EmployeeOutletAttendanceLog::where('id_employee_outlet_attendance_log',$val['id_employee_outlet_attendance_log'])->update(['read'=>1]);
                        }
                        
                        $send[] = $data;
                    }
                }
               
            }
            if(in_array('506',$roles)){
                if(empty($key_id) || (isset($key_id) && $key_id=='attendance_outlet_request')){
                    $a_req = EmployeeOutletAttendanceRequest::join('users','users.id','employee_outlet_attendance_requests.id')->where('users.id_outlet', $id_outlet)->where('employee_outlet_attendance_requests.status', 'Pending');
                    if($key_id == 'attendance_outlet_request'){
                        $a_req = $a_req->where('employee_outlet_attendance_requests.id_employee_outlet_attendance_request', $id_detail);
                    }
                    $a_req = $a_req->select('employee_outlet_attendance_requests.*','users.name')->get()->toArray();
                    foreach($a_req ?? [] as $val){
                       $data = [
                            'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                            'type' => 'Presensi Outlet',
                            'important' => 0,
                            'detail' => 'attendance_outlet_request-'.$val['id_employee_outlet_attendance_request'],
                            'read' => $val['read'],
                            'data' => [
                                [
                                    'label' => 'Jenis Presensi',
                                    'value' => 'Request'
                                ],
                                [
                                    'label' => 'Nama',
                                    'value' => $val['name']
                                ],
                                [
                                    'label' => 'Clock In',
                                    'value' => $val['clock_in'] ? date('d/m/Y H:i', strtotime($val['attendance_date'].' '.$val['clock_in'])) : '-'
                                ],
                                [
                                    'label' => 'Clock Out',
                                    'value' => $val['clock_out'] ? date('d/m/Y H:i', strtotime($val['attendance_date'].' '.$val['clock_out'])) : '-'
                                ],
                                [
                                    'label' => 'Keterangan',
                                    'value' => $val['notes']
                                ],
                            ]
                        ];
                        if(isset($id_detail)){
                            $update_read = EmployeeOutletAttendanceRequest::where('id_employee_outlet_attendance_request',$val['id_employee_outlet_attendance_request'])->update(['read'=>1]);
                        }
                        $send[] = $data;
                    }
                }
            }
        }

        if(in_array('510',$roles) && ($category=='time_off' || $category == 'all' || $key_id == 'time_off')){
            if(in_array('529',$roles)){
                $time_off = EmployeeTimeOff::join('users','users.id','employee_time_off.id_employee')->where('employee_time_off.id_outlet',$id_outlet)->whereNull('employee_time_off.approve_by')->whereNull('employee_time_off.reject_at')->where('employee_time_off.status','Manager Approved');
            }else{
                $time_off = EmployeeTimeOff::join('users','users.id','employee_time_off.id_employee')->join('employees','employees.id_user','employee_time_off.id_employee')->where('employees.id_manager', $id_employee)->where('employee_time_off.id_outlet',$id_outlet)->whereNull('employee_time_off.approve_by')->whereNull('employee_time_off.reject_at')->where('employee_time_off.status','Pending');
            }
            if($key_id == 'time_off'){
                $time_off = $time_off->where('employee_time_off.id_employee_time_off', $id_detail);
            }
            $time_off = $time_off->select('employee_time_off.*','users.name')->get()->toArray();
            foreach($time_off ?? [] as $val){
               $data = [
                    'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                        'type' => 'Cuti',
                        'important' => 0,
                        'detail' => 'time_off-'.$val['id_employee_time_off'],
                        'read' => $val['read'],
                        'data' => [
                            [
                                'label' => 'Jenis Cuti',
                                'value' => $val['type']
                            ],
                            [
                                'label' => 'Nama',
                                'value' => $val['name']
                            ],
                            [
                                'label' => 'Tanggal',
                                'value' => MyHelper::dateFormatInd($val['start_date'], true, false, false).' - '.MyHelper::dateFormatInd($val['end_date'], true, false, false)
                            ],
                            [
                                'label' => 'Keterangan',
                                'value' => $val['notes']
                            ],
                        ]
                ];
                if(isset($id_detail)){
                    $att_image = [
                        'label' => 'Attachment Image',
                        'type' => 'Image',
                        'value' => [
                            ''
                        ]
                    ];
                    $att_file = [
                        'label' => 'Attachment File',
                        'type' => 'File',
                        'value' => [
                            ''
                        ]
                    ];

                    $attachment_time_off = EmployeeTimeOffImage::where('id_employee_time_off', $val['id_employee_time_off'])->get()->toArray();
                    $link_img = [];
                    $link_file = [];
                    foreach($attachment_time_off ?? [] as $att){
                        $ext = pathinfo($att['path'])['extension'];
                        if($ext == 'pdf'){
                            $link_file[] = $att['path'] ? env('STORAGE_URL_API').$att['path'] : '';
                        }elseif($ext == 'png' || $ext == 'jpeg' || $ext == 'jpg' || $ext == 'bmp'){
                            $link_img[] = $att['path'] ? env('STORAGE_URL_API').$att['path'] : '';
                        }
                    }
                    if(!empty($link_img)){
                        $att_image['value'] = $link_img;
                    }
                    if(!empty($link_file)){
                        $att_file['value'] = $link_file;
                    }

                    $data['data'][] = $att_image;
                    $data['data'][] = $att_file;

                    $update_read = EmployeeTimeOff::where('id_employee_time_off',$val['id_employee_time_off'])->update(['read'=>1]);
                }

                $send[] = $data;
            }
        }

        if(in_array('514',$roles) && ($category=='overtime' || $category == 'all' || $key_id == 'overtime')){
            if(in_array('529',$roles)){
                $overtime = EmployeeOvertime::join('users','users.id','employee_overtime.id_employee')->where('employee_overtime.id_outlet',$id_outlet)->whereNull('employee_overtime.approve_by')->whereNull('employee_overtime.reject_at')->where('employee_overtime.status','Manager Approved');
            }else{
                $overtime = EmployeeOvertime::join('users','users.id','employee_overtime.id_employee')->join('employees','employees.id_user','employee_overtime.id_employee')->where('employees.id_manager', $id_employee)->where('employee_overtime.id_outlet',$id_outlet)->whereNull('employee_overtime.approve_by')->whereNull('employee_overtime.reject_at')->where('employee_overtime.status','Pending');
            }
            if($key_id == 'overtime'){
                $overtime = $overtime->where('employee_overtime.id_employee_overtime', $id_detail);
            }
            $overtime = $overtime->select('employee_overtime.*','users.name')->get()->toArray();
            foreach($overtime ?? [] as $val){
               $data = [
                    'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                        'type' => 'Lembur',
                        'important' => 0,
                        'detail' => 'overtime-'.$val['id_employee_overtime'],
                        'read' => $val['read'],
                        'data' => [
                            [
                                'label' => 'Jenis Lembur',
                                'value' => $val['time'] == 'before' ? 'Sebelum Jam Kerja' : 'Setelah Jam Kerja'
                            ],
                            [
                                'label' => 'Nama',
                                'value' => $val['name']
                            ],
                            [
                                'label' => 'Tanggal',
                                'value' => MyHelper::dateFormatInd($val['date'], true, false, false)
                            ],
                            [
                                'label' => 'Keterangan',
                                'value' => $val['notes']
                            ],
                        ]
                ];
                
                if(isset($id_detail)){
                    $shift = $this->getShiftForOvertime($val);
                    $data['data'][] = [
                        'label' => 'Jam Lembur',
                        'value' => $shift['schedule_in'].' - '.$shift['schedule_out']
                    ];
                    $update_read = EmployeeOvertime::where('id_employee_overtime',$val['id_employee_overtime'])->update(['read'=>1]);
                }

                $send[] = $data;
            }
        }

        if(in_array('546',$roles) && ($category=='change_shift' || $category == 'all' || $key_id == 'change_shift')){
            $changeshift = EmployeeChangeShift::join('users','users.id','employee_change_shifts.id_user')
            ->where('users.id_outlet',$id_outlet)->where('employee_change_shifts.status','Pending');
            if($key_id == 'change_shift'){
                $changeshift = $changeshift->where('employee_change_shifts.id_employee_change_shift', $id_detail);
            }
            $changeshift = $changeshift->select('employee_change_shifts.*','users.name')->get()->toArray();
            foreach($changeshift ?? [] as $val){
                $schedule_date = EmployeeSchedule::join('employee_schedule_dates','employee_schedule_dates.id_employee_schedule','employee_schedules.id_employee_schedule')
                ->where('schedule_month',date('m',strtotime($val['change_shift_date'])))
                ->where('schedule_year', date('Y',strtotime($val['change_shift_date'])))
                ->Where('id',$val['id_user'])
                ->whereDate('date',date('Y-m-d',strtotime($val['change_shift_date'])))
                ->first();
                $office_hour = EmployeeOfficeHourShift::where('id_employee_office_hour',$schedule_date['id_office_hour_shift'])->get()->keyBy('shift_name');
                $new_shift = array_reduce($office_hour->toArray(), function ($found, $obj) use ($val) {
                    return $obj['id_employee_office_hour_shift'] == $val['id_employee_office_hour_shift'] ? $obj : $found;
                }, null);
                $data = [
                    'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                    'type' => 'Ganti Shift',
                    'important' => 0,
                    'detail' => 'change_shift-'.$val['id_employee_change_shift'],
                    'read' => $val['read'],
                    'data' => [
                        [
                            'label' => 'Nama',
                            'value' => $val['name']
                        ],
                        [
                            'label' => 'Tanggal',
                            'value' => MyHelper::dateFormatInd($val['change_shift_date'], true, false, false)
                        ],
                        [
                            'label' => 'Shift Lama',
                            'value' => $office_hour[$schedule_date['shift']]['shift_name'].' '.'('.MyHelper::adjustTimezone($office_hour[$schedule_date['shift']]['shift_start'], $timeZone, 'H:i', true).'-'.MyHelper::adjustTimezone($office_hour[$schedule_date['shift']]['shift_end'], $timeZone, 'H:i', true).' '.$time_zone[$timeZone].')'
                        ],
                        [
                            'label' => 'Shift Baru',
                            'value' => $new_shift['shift_name'].' '.'('.MyHelper::adjustTimezone($new_shift['shift_start'], $timeZone, 'H:i', true).'-'.MyHelper::adjustTimezone($new_shift['shift_end'], $timeZone, 'H:i', true).' '.$time_zone[$timeZone].')'
                        ],
                    ]
                ];
                
                if(isset($id_detail)){
                    $data['data'][] = [
                        'label' => 'Alasan',
                        'value' => $val['reason'],
                    ];
                    $update_read = EmployeeChangeShift::where('id_employee_change_shift',$val['id_employee_change_shift'])->update(['read'=>1]);
                }

                $send[] = $data;
            }
        }
        
        if(in_array('517',$roles) && ($category=='reimbursement' || $category == 'all' || $key_id == 'reimbursement')){
            $reim = EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')->join('product_icounts','product_icounts.id_product_icount','employee_reimbursements.id_product_icount')->where('users.id_outlet', $id_outlet)->where('employee_reimbursements.status', 'Pending');
            if($key_id == 'reimbursement'){
                $reim = $reim->where('employee_reimbursements.id_employee_reimbursement', $id_detail);
            }
            $reim = $reim->select('product_icounts.name as name_product','users.name', 'employee_reimbursements.*')->get()->toArray();
            foreach($reim ?? [] as $val){
               $data = [
                    'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                        'type' => 'Pengembalian Dana',
                        'important' => 0,
                        'detail' => 'reimbursement-'.$val['id_employee_reimbursement'],
                        'read' => $val['read'],
                        'data' => [
                            [
                                'label' => 'Product',
                                'value' => $val['name_product']
                            ],
                            [
                                'label' => 'Nama',
                                'value' => $val['name']
                            ],
                            [
                                'label' => 'Tanggal',
                                'value' => MyHelper::dateFormatInd($val['date_reimbursement'], true, false, false)
                            ],
                            [
                                'label' => 'Keterangan',
                                'value' => $val['notes']
                            ],
                        ]
                ];

                if(isset($id_detail)){
                    $att_image = [
                        'label' => 'Attachment Image',
                        'type' => 'Image',
                        'value' => [
                            ''
                        ]
                    ];
                    $att_file = [
                        'label' => 'Attachment File',
                        'type' => 'File',
                        'value' => [
                            ''
                        ]
                    ];

                    $link_img = [];
                    $link_file = [];
                    $ext = pathinfo($val['attachment'])['extension'];
                    if($ext == 'pdf'){
                        $link_file[] = $val['attachment'] ? env('STORAGE_URL_API').$val['attachment'] : '';
                    }elseif($ext == 'png' || $ext == 'jpeg' || $ext == 'jpg' || $ext == 'bmp'){
                        $link_img[] = $val['attachment'] ? env('STORAGE_URL_API').$val['attachment'] : '';
                    }
        
                    if(!empty($link_img)){
                        $att_image['value'] = $link_img;
                    }
                    if(!empty($link_file)){
                        $att_file['value'] = $link_file;
                    }

                    $data['data'][] = $att_image;
                    $data['data'][] = $att_file;
                    $update_read = EmployeeReimbursement::where('id_employee_reimbursement',$val['id_employee_reimbursement'])->update(['read'=>1]);
                }
                
                $send[] = $data;
            }
        }
         
        if(in_array('520',$roles) && ($category=='loan_assets' || $category == 'all' || $key_id == 'loan_assets')){
            $loan = AssetInventoryLog::join('users','users.id','asset_inventory_logs.id_user')
                        ->join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
                        ->join('asset_inventory_loans', 'asset_inventory_loans.id_asset_inventory_log', 'asset_inventory_logs.id_asset_inventory_log')
                        ->where('users.id_outlet', $id_outlet)
                        ->where('asset_inventory_logs.type_asset_inventory','Loan')
                        ->where('asset_inventory_logs.status_asset_inventory','Pending');
            if($key_id == 'loan_assets'){
                $loan = $loan->where('asset_inventory_logs.id_asset_inventory_log', $id_detail);
            }
            $loan = $loan->select('users.name','asset_inventory_logs.*','asset_inventorys.name_asset_inventory', 'asset_inventory_loans.notes as loan_notes', 'asset_inventory_loans.long', 'asset_inventory_loans.long_loan', 'asset_inventory_loans.attachment as attachment_loans')
                        ->get()->toArray(); 
            $longtime = [
                'Day' => 'Hari',
                'Month' => 'Bulan',
                'Year' => 'Tahun'
            ];
            foreach($loan ?? [] as $val){
               $data = [
                    'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                        'type' => 'Peminjaman Barang',
                        'important' => 0,
                        'detail' => 'loan_assets-'.$val['id_asset_inventory_log'],
                        'read' => $val['read'],
                        'data' => [
                            [
                                'label' => 'Barang',
                                'value' => $val['name_asset_inventory']
                            ],
                            [
                                'label' => 'Nama',
                                'value' => $val['name']
                            ],
                            [
                                'label' => 'Jumlah',
                                'value' => $val['qty_logs']
                            ],
                            [
                                'label' => 'Durasi',
                                'value' => $val['long'].' '.$longtime[$val['long_loan']]
                            ],
                            [
                                'label' => 'Keterangan',
                                'value' => $val['loan_notes']
                            ],
                        ]
                ];
                
                if(isset($id_detail)){
                    $att_image = [
                        'label' => 'Attachment Image',
                        'type' => 'Image',
                        'value' => [
                            ''
                        ]
                    ];
                    $att_file = [
                        'label' => 'Attachment File',
                        'type' => 'File',
                        'value' => [
                            ''
                        ]
                    ];

                    $link_img = [];
                    $link_file = [];
                    $ext = pathinfo($val['attachment_loans'])['extension'];
                    if($ext == 'pdf'){
                        $link_file[] = $val['attachment_loans'] ? env('STORAGE_URL_API').$val['attachment_loans'] : '';
                    }elseif($ext == 'png' || $ext == 'jpeg' || $ext == 'jpg' || $ext == 'bmp'){
                        $link_img[] = $val['attachment_loans'] ? env('STORAGE_URL_API').$val['attachment_loans'] : '';
                    }
        
                    if(!empty($link_img)){
                        $att_image['value'] = $link_img;
                    }
                    if(!empty($link_file)){
                        $att_file['value'] = $link_file;
                    }

                    $data['data'][] = $att_image;
                    $data['data'][] = $att_file;
                    $update_read = AssetInventoryLog::where('id_asset_inventory_log',$val['id_asset_inventory_log'])->update(['read'=>1]);
                }
                
                $send[] = $data;
            }
        }

        if(in_array('523',$roles) && ($category=='return_assets' || $category == 'all' || $key_id == 'return_assets')){
            $ret = AssetInventoryLog::join('users','users.id','asset_inventory_logs.id_user')
                        ->join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
                        ->join('asset_inventory_returns', 'asset_inventory_returns.id_asset_inventory_log', 'asset_inventory_logs.id_asset_inventory_log')
                        ->where('users.id_outlet', $id_outlet)
                        ->where('asset_inventory_logs.type_asset_inventory','Return')
                        ->where('asset_inventory_logs.status_asset_inventory','Pending');
            if($key_id == 'return_assets'){
                $ret = $ret->where('asset_inventory_logs.id_asset_inventory_log', $id_detail);
            }
            $ret = $ret->select('users.name','asset_inventory_logs.*','asset_inventorys.name_asset_inventory', 'asset_inventory_returns.notes as return_notes', 'asset_inventory_returns.date_return', 'asset_inventory_returns.attachment as attachment_return')
                        ->get()->toArray(); 
            foreach($ret ?? [] as $val){
               $data = [
                    'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                        'type' => 'Pengembalian Barang',
                        'important' => 0,
                        'detail' => 'return_assets-'.$val['id_asset_inventory_log'],
                        'read' => $val['read'],
                        'data' => [
                            [
                                'label' => 'Barang',
                                'value' => $val['name_asset_inventory']
                            ],
                            [
                                'label' => 'Nama',
                                'value' => $val['name']
                            ],
                            [
                                'label' => 'Tanggal Pengembalian',
                                'value' => MyHelper::dateFormatInd($val['date_return'], true, false, false)
                            ],
                            [
                                'label' => 'Keterangan',
                                'value' => $val['return_notes']
                            ],
                        ]
                ];
                
                if(isset($id_detail)){
                    $att_image = [
                        'label' => 'Attachment Image',
                        'type' => 'Image',
                        'value' => [
                            ''
                        ]
                    ];
                    $att_file = [
                        'label' => 'Attachment File',
                        'type' => 'File',
                        'value' => [
                            ''
                        ]
                    ];

                    $link_img = [];
                    $link_file = [];
                    $ext = pathinfo($val['attachment_return'])['extension'];
                    if($ext == 'pdf'){
                        $link_file[] = $val['attachment_return'] ? env('STORAGE_URL_API').$val['attachment_return'] : '';
                    }elseif($ext == 'png' || $ext == 'jpeg' || $ext == 'jpg' || $ext == 'bmp'){
                        $link_img[] = $val['attachment_return'] ? env('STORAGE_URL_API').$val['attachment_return'] : '';
                    }
        
                    if(!empty($link_img)){
                        $att_image['value'] = $link_img;
                    }
                    if(!empty($link_file)){
                        $att_file['value'] = $link_file;
                    }

                    $data['data'][] = $att_image;
                    $data['data'][] = $att_file;
                    $update_read = AssetInventoryLog::where('id_asset_inventory_log',$val['id_asset_inventory_log'])->update(['read'=>1]);
                }
                
                $send[] = $data;
            }
        }
        
        if(in_array('415',$roles) && ($category=='request_product' || $category == 'all'  || $key_id == 'request_product')){
            $req_product = RequestProduct::join('users','users.id','request_products.id_user_request')
                        ->leftJoin('request_product_details', 'request_product_details.id_request_product', 'request_products.id_request_product')
                        ->where('request_products.id_outlet',$id_outlet)
                        ->where('request_products.status','Pending');
            if($key_id == 'request_product'){
                $req_product = $req_product->where('request_products.id_request_product', $id_detail);
            }
            $req_product = $req_product->select('request_products.*', 'users.name', DB::raw("count(request_product_details.id_request_product_detail) as count"))
                        ->groupBy('request_products.id_request_product')->get()->toArray();
            foreach($req_product ?? [] as $val){
               $data = [
                        'request_at' => MyHelper::dateFormatInd($val['created_at'], true, false, false),
                        'type' => 'Permintaan Produk',
                        'important' => 0,
                        'detail' => 'request_product-'.$val['id_request_product'],
                        'read' => $val['read'],
                        'data' => [
                            [
                                'label' => 'Code',
                                'value' => $val['code']
                            ],
                            [
                                'label' => 'Nama',
                                'value' => $val['name']
                            ],
                            [
                                'label' => 'Tanggal Dibutuhkan',
                                'value' => MyHelper::dateFormatInd($val['requirement_date'], true, false, false)
                            ],
                            [
                                'label' => 'Jumlah Produk',
                                'value' => number_format($val['count'],0,",",".")
                            ],
                            [
                                'label' => 'Keterangan',
                                'value' => $val['note_request']
                            ],
                        ]
                ];

                if(isset($id_detail)){
                    $att_image = [
                        'label' => 'Attachment Image',
                        'type' => 'Image',
                        'value' => [
                            ''
                        ]
                    ];

                    $attachment_req_pro = RequestProductImage::where('id_request_product', $val['id_request_product'])->get()->toArray();
                    $link_img = [];
                    foreach($attachment_req_pro ?? [] as $att){
                        $ext = pathinfo($att['path'])['extension'];
                        if($ext == 'pdf'){
                            $link_file[] = $att['path'] ? env('STORAGE_URL_API').$att['path'] : '';
                        }elseif($ext == 'png' || $ext == 'jpeg' || $ext == 'jpg' || $ext == 'bmp'){
                            $link_img[] = $att['path'] ? env('STORAGE_URL_API').$att['path'] : '';
                        }
                    }
                    if(!empty($link_img)){
                        $att_image['value'] = $link_img;
                    }
                    
                    $product_detail = [
                        'label' => 'Detail',
                        'value' => []
                    ];
                    $detail_product = RequestProductDetail::join('product_icounts', 'product_icounts.id_product_icount', 'request_product_details.id_product_icount')->where('id_request_product', $val['id_request_product'])->select('product_icounts.name', 'request_product_details.*')->get()->toArray();
                    foreach($detail_product ?? [] as $detail_pro){
                        $product_detail['value'][] = [
                            'id_product_icount' => $detail_pro['id_product_icount'],
                            'name_product' => $detail_pro['name'],
                            'count' => $detail_pro['value'],
                            'unit' => $detail_pro['unit'],
                        ];
                    }
                    $data['data'][] = $product_detail;
                    $data['data'][] = $att_image;
                    $update_read = RequestProduct::where('id_request_product',$val['id_request_product'])->update(['read'=>1]);
                }

                $send[] = $data;
            }
        }

        if(isset($key_id) && isset($id_detail)){
            $send = $send[0];
        }

        return MyHelper::checkGet($send);
    }

    public function getShiftForOvertime($data){
        $data_outlet = Outlet::where('id_outlet', $data['id_outlet'])->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $data_outlet['id_city'])->first()['time_zone_utc']??null;
        $date = date('Y-m-d', strtotime($data['date']));
        $array_date = explode('-', $date);

        $cek_employee = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$data['id_employee'])->first();
        if(empty($cek_employee['office_hour_type'])){
            $setting_default = Setting::where('key', 'employee_office_hour_default')->first();
            if($setting_default){
                $cek_employee = EmployeeOfficeHour::where('id_employee_office_hour',$setting_default['value'])->first();
                if(empty($cek_employee)){
                    return response()->json([
                        'status'=>'fail',
                        'messages'=>['Jam kantor tidak ada ']
                    ]);
                }
            }
        }
        if($cek_employee['office_hour_type'] == 'Without Shift'){
            $schedule_date_without = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                ->join('users','users.id','employee_schedules.id')
                                ->where('users.id', $data['id_employee'])
                                ->where('employee_schedules.schedule_month', $array_date[1])
                                ->where('employee_schedules.schedule_year', $array_date[0])
                                ->whereDate('employee_schedule_dates.date', $date)
                                ->first();
            if($schedule_date_without){ 
                $send['schedule_in'] = date('H:i', strtotime($schedule_date_without['time_start']));
                $send['schedule_out'] = date('H:i', strtotime($schedule_date_without['time_end']));
            }else{
                $send['schedule_in'] = MyHelper::reverseAdjustTimezone(date('H:i', strtotime($cek_employee['office_hour_start'])), $timeZone, 'H:i');
                $send['schedule_out'] = MyHelper::reverseAdjustTimezone(date('H:i', strtotime($cek_employee['office_hour_end'])), $timeZone, 'H:i');
            }
        }else{
            $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                                    ->join('users','users.id','employee_schedules.id')
                                                    ->where('users.id', $data['id_employee'])
                                                    ->where('employee_schedules.schedule_month', $array_date[1])
                                                    ->where('employee_schedules.schedule_year', $array_date[0])
                                                    ->whereDate('employee_schedule_dates.date', $date)
                                                    ->first();

            $send['schedule_in'] = date('H:i', strtotime($schedule_date['time_start']));
            $send['schedule_out'] = date('H:i', strtotime($schedule_date['time_end']));
        }

        $time_off['schedule_in'] = $send['schedule_in'] ? MyHelper::adjustTimezone($send['schedule_in'], $timeZone, 'H:i') : null;
        $time_off['schedule_out'] = $send['schedule_out'] ? MyHelper::adjustTimezone($send['schedule_out'], $timeZone, 'H:i') : null;

        if($data['time']=='before'){
            $duration = strtotime($data['duration']);
            $start = strtotime($time_off['schedule_in']);
            $diff = $start - $duration;
            $hour = floor($diff / (60*60));
            $minute = floor(($diff - ($hour*60*60))/(60));
            $second = floor(($diff - ($hour*60*60))%(60));
            $new_time =  date('H:i', strtotime($hour.':'.$minute.':'.$second));
            $time_off['schedule_out'] = $time_off['schedule_in'];
            $time_off['schedule_in'] = $new_time;
        }else{
            $secs = strtotime($data['duration'])-strtotime("00:00:00");
            $new_time = date("H:i",strtotime($time_off['schedule_out'])+$secs);
            $time_off['schedule_in'] = $time_off['schedule_out'];
            $time_off['schedule_out'] = $new_time;
        }
        return $time_off;
    }

    public function approveReqApproval(Request $request){
        $request->validate([
            'status' => 'string|in:Approve,Reject',
        ]);
        $post = $request->all();
        $user = $request->user();
        $id_employee = $user['id'];
        $id_outlet = $user['id_outlet'];
        $key_id = null;
        $id_detail = null;

        $roles = RolesFeature::where('id_role', $user['id_role'])->select('id_feature')->get()->toArray();
        $roles = array_pluck($roles, 'id_feature');

        if(isset($post['id']) && !empty($post['id'])){
            $array_id = explode('-',$post['id']);
            $key_id = $array_id[0];
            $id_detail = $array_id[1];
        }

        if($key_id == 'attendance_pending'){
            $data_update = [
                'id_employee_attendance_log' => $id_detail,
                'approve_notes' => $post['approve_notes'],
                'status' => $post['status'],
                'user_update' => $user['name']
            ];
            $update = app('\Modules\Employee\Http\Controllers\ApiEmployeeAttendanceController')->updatePending(New Request($data_update));
        }

        if($key_id == 'attendance_request'){
            $data_update = [
                'id_employee_attendance_request' => $id_detail,
                'approve_notes' => $post['approve_notes'],
                'status' => $post['status'],
                'id' => $id_employee,
                'user_update' => $user['name']
            ];
            $update = app('\Modules\Employee\Http\Controllers\ApiEmployeeAttendanceController')->updateRequest(New Request($data_update));
        }

        if($key_id == 'attendance_outlet_pending'){
            $data_update = [
                'id_employee_outlet_attendance_log' => $id_detail,
                'approve_notes' => $post['approve_notes'],
                'status' => $post['status'],
                'user_update' => $user['name']
            ];
            $update = app('\Modules\Employee\Http\Controllers\ApiEmployeeAttendaceOutletController')->updatePending(New Request($data_update));
        }

        if($key_id == 'attendance_outlet_request'){
            $data_update = [
                'id_employee_outlet_attendance_request' => $id_detail,
                'approve_notes' => $post['approve_notes'],
                'status' => $post['status'],
                'id' => $id_employee,
                'user_update' => $user['name']
            ];
            $update = app('\Modules\Employee\Http\Controllers\ApiEmployeeAttendaceOutletController')->updateRequest(New Request($data_update));
        }

        if($key_id == 'time_off'){
            $data_update = [
                'id_employee_time_off' => $id_detail,
                'approve_notes' => $post['approve_notes'],
                'approve' => in_array('529',$roles) ? true : false,
                'id_approve' => $id_employee,
                'type' => in_array('529',$roles) ? 'HRGA Approved' : 'Manager Approved'
            ];
            if($post['status']=='Approve'){
                return $update = app('\Modules\Employee\Http\Controllers\ApiEmployeeTimeOffOvertimeController')->updateTimeOff(New Request($data_update));
            }elseif($post['status']=='Reject'){
                $update = app('\Modules\Employee\Http\Controllers\ApiEmployeeTimeOffOvertimeController')->deleteTimeOff(New Request($data_update));
            }
        }

        if($key_id == 'overtime'){
            $overtime = EmployeeOvertime::where('id_employee_overtime',$id_detail)->first();
            $shift = $this->getShiftForOvertime($overtime);
            $data_update = [
                'id_employee_overtime' => $id_detail,
                'approve_notes' => $post['approve_notes'],
                'approve' => in_array('529',$roles) ? true : false,
                'id_approve' => $id_employee,
                'schedule_in' => $shift['schedule_in'],
                'schedule_out' => $shift['schedule_out'],
                'type' => in_array('529',$roles) ? 'HRGA Approved' : 'Manager Approved'
            ];
            if($post['status']=='Approve'){
                $update = app('\Modules\Employee\Http\Controllers\ApiEmployeeTimeOffOvertimeController')->updateOvertime(New Request($data_update));
            }elseif($post['status']=='Reject'){
                $update = app('\Modules\Employee\Http\Controllers\ApiEmployeeTimeOffOvertimeController')->deleteOvertime(New Request($data_update));
            }
        }

        if($key_id == 'change_shift'){
            $overtime = EmployeeChangeShift::where('id_employee_change_shift',$id_detail)->first();
            // $shift = $this->getShiftForOvertime($overtime);
            $data_update = [
                'id_employee_change_shift' => $id_detail,
                'approve_notes' => $post['approve_notes'],
                'approve' => true,
                'id_approve' => $id_employee,
            ];
            if($post['status']=='Approve'){
                $update = app('\Modules\Employee\Http\Controllers\ApiEmployeeChangeShiftController')->updateChangeShift(New Request($data_update));
            }elseif($post['status']=='Reject'){
                return $update = app('\Modules\Employee\Http\Controllers\ApiEmployeeChangeShiftController')->deleteChangeShift(New Request($data_update));
            }
        }

        if($key_id == 'reimbursement'){
            if($post['status']=='Approve'){
                $status = 'Approved';
            }elseif($post['status']=='Reject'){
                $status = 'Rejected';
            }
            $data_update = [
                'id_employee_reimbursement' => $id_detail,
                'approve_notes' => $post['approve_notes'],
                'status' => $status,
                'id_user_approved' => $id_employee,
                'validator_reimbursement' => $user['name']
            ];
            $update = app('\Modules\Employee\Http\Controllers\ApiBeEmployeeReimbursementController')->approved_apps($data_update);
        }

        if($key_id == 'loan_assets'){
            if($post['status']=='Approve'){
                $status = 'Approved';
            }elseif($post['status']=='Reject'){
                $status = 'Rejected';
            }
            $data_update = [
                'id_asset_inventory_log' => $id_detail,
                'notes' => $post['approve_notes'],
                'status_asset_inventory' => $status,
                'id_user_approved' => $id_employee,
                'validator_reimbursement' => $user['name']
            ];
            $update = app('\Modules\Employee\Http\Controllers\ApiBeEmployeeAssetInventoryController')->approve_loan(New ApproveLoan($data_update));
           
        }

        if($key_id == 'return_assets'){
            if($post['status']=='Approve'){
                $status = 'Approved';
            }elseif($post['status']=='Reject'){
                $status = 'Rejected';
            }
            $data_update = [
                'id_asset_inventory_log' => $id_detail,
                'notes' => $post['approve_notes'],
                'status_asset_inventory' => $status,
                'id_user_approved' => $id_employee,
                'validator_reimbursement' => $user['name']
            ];
            $update = app('\Modules\Employee\Http\Controllers\ApiBeEmployeeAssetInventoryController')->approve_return(New ApproveReturn($data_update));
           
        }

        if($key_id == 'request_product'){
            $product_icount = [];
            $products = RequestProductDetail::where('id_request_product', $id_detail)->get()->toArray();
            foreach($products ?? [] as $no => $product){
                $product_icount[] = [
                    'filter' => $product['filter'],
                    'id_product_icount' => $product['id_product_icount'],
                    'unit' => $product['unit'],
                    'qty' => $product['value'],
                    'budget_code' =>$product['budget_code'],
                    'status' => $post['status']=='Approve' ? 'Approved' : 'Rejected'
                ];
            }
            
            $data_update = [
                'id_request_product' => $id_detail,
                'note_approve' => $post['approve_notes'],
                'id_user_approved' => $id_employee,
                'product_icount' => $product_icount,
                'from' => 'Product'
            ];
            $update = app('\Modules\Product\Http\Controllers\ApiRequestProductController')->update(New ApproveReturn($data_update));
           
        }

        return $update;
    }

    public function inboxRead(Request $request){
        $post = $request->all();
        if(!isset($post['id_inbox']) && empty($post['id_inbox'])){
            return ['status' => 'fail', 'messages' => ['Mohon untuk memilih salah satu pesan.']];
        }
        

        $update = EmployeeInbox::where('id_employee_inboxes',$post['id_inbox'])->update(['read'=>1]);
        if(!$update){
            return ['status' => 'fail', 'messages' => ['Gagal membaca pesan.']];
        }
        return ['status' => 'success'];
    }

}
