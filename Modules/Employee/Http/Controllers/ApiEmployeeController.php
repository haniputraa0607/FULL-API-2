<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Employee\Entities\EmployeeOfficeHour;
use Modules\Employee\Entities\EmployeeOfficeHourAssign;
use Modules\Employee\Entities\EmployeeOfficeHourShift;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;
use App\Http\Models\User;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Holiday;
use Modules\Employee\Entities\EmployeeSchedule;
use Modules\Employee\Entities\EmployeeScheduleDate;
use DB;

class ApiEmployeeController extends Controller
{
    public function officeHoursCreate(Request $request){
        $post = $request->all();

        if($post['office_hour_type'] == 'Use Shift' && empty($post['shift'])){
            return response()->json(['status'   => 'fail', 'messages' => ['Data shift can not be empty']]);
        }

        $data = [
            'office_hour_name' => $post['office_hour_name'],
            'office_hour_type' => $post['office_hour_type'],
            'office_hour_start' => ($post['office_hour_type'] == 'Without Shift' ? date('H:i:s', strtotime($post['office_hour_start'])) : NULL),
            'office_hour_end' => ($post['office_hour_type'] == 'Without Shift' ? date('H:i:s', strtotime($post['office_hour_end'])) : NULL)
        ];

        $create = EmployeeOfficeHour::create($data);

        if($create){
            if($post['office_hour_type'] == 'Use Shift'){
                $insertShift = [];
                foreach ($post['shift'] as $data){
                    $insertShift[] = [
                        'id_employee_office_hour' => $create['id_employee_office_hour'],
                        'shift_name' => $data['name'],
                        'shift_start' => date('H:i:s', strtotime($data['start'])),
                        'shift_end'  => date('H:i:s', strtotime($data['end'])),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                if(!empty($insertShift)){
                    EmployeeOfficeHourShift::insert($insertShift);
                }
            }

            if(!empty($post['employee_office_hour_default'])){
                $create = Setting::updateOrCreate(['key' => 'employee_office_hour_default'], ['value' => $create['id_employee_office_hour']]);
            }
        }

        return response()->json(MyHelper::checkCreate($create));
    }

    public function officeHoursDefault(){
        $value = Setting::where('key', 'employee_office_hour_default')->first()['value']??NULL;
        return response()->json(MyHelper::checkGet($value));
    }

    public function officeHoursList(){
        $res = EmployeeOfficeHour::with('office_hour_shift')->get()->toArray();
        return response()->json(MyHelper::checkGet($res));
    }

    public function officeHoursDetail(Request $request){
        $post = $request->all();

        if(!empty($post['id_employee_office_hour'])){
            $detail = EmployeeOfficeHour::where('id_employee_office_hour', $post['id_employee_office_hour'])->with('office_hour_shift')->first();

            if($detail){
                $detail['employee_office_hour_default'] = Setting::where('key', 'employee_office_hour_default')->first()['value']??NULL;
            }
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function officeHoursUpdate(Request $request){
        $post = $request->all();

        if(empty($post['id_employee_office_hour'])){
            return response()->json(['status'   => 'fail', 'messages' => ['ID can not be empty']]);
        }

        if($post['office_hour_type'] == 'Use Shift' && empty($post['shift'])){
            return response()->json(['status'   => 'fail', 'messages' => ['Data shift can not be empty']]);
        }

        $data = [
            'office_hour_name' => $post['office_hour_name'],
            'office_hour_type' => $post['office_hour_type'],
            'office_hour_start' => ($post['office_hour_type'] == 'Without Shift' ? date('H:i:s', strtotime($post['office_hour_start'])) : NULL),
            'office_hour_end' => ($post['office_hour_type'] == 'Without Shift' ? date('H:i:s', strtotime($post['office_hour_end'])) : NULL)
        ];

        $update = EmployeeOfficeHour::where('id_employee_office_hour', $post['id_employee_office_hour'])->update($data);

        EmployeeOfficeHourShift::where('id_employee_office_hour', $post['id_employee_office_hour'])->delete();
        if($update){

            if($post['office_hour_type'] == 'Use Shift'){
                $insertShift = [];
                foreach ($post['shift'] as $data){
                    $insertShift[] = [
                        'id_employee_office_hour' => $post['id_employee_office_hour'],
                        'shift_name' => $data['name'],
                        'shift_start' => date('H:i:s', strtotime($data['start'])),
                        'shift_end'  => date('H:i:s', strtotime($data['end'])),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                if(!empty($insertShift)){
                    EmployeeOfficeHourShift::insert($insertShift);
                }
            }

            $checkSetting = Setting::where('key', 'employee_office_hour_default')->first()['value']??null;
            if(!empty($post['employee_office_hour_default'])){
                $default = $post['id_employee_office_hour'];
                $update = Setting::updateOrCreate(['key' => 'employee_office_hour_default'], ['value' => $default]);
            }elseif(empty($post['employee_office_hour_default']) && $checkSetting == $post['id_employee_office_hour']){
                $default = null;
                $update = Setting::updateOrCreate(['key' => 'employee_office_hour_default'], ['value' => $default]);
            }
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function officeHoursDelete(Request $request){
        $post = $request->all();

        if(!empty($post['id_employee_office_hour'])){
            $check = EmployeeOfficeHour::where('id_employee_office_hour', $post['id_employee_office_hour'])->first();

            if(empty($check)){
                return response()->json(['status' => 'fail', 'messages' => ['Data office hours not found']]);
            }

            $delete = EmployeeOfficeHour::where('id_employee_office_hour', $check['id_employee_office_hour'])->delete();

            if($delete && $check['office_hour_type'] == 'Use Shift'){
                $delete = EmployeeOfficeHourShift::where('id_employee_office_hour', $check['id_employee_office_hour'])->delete();
            }

            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function officeHoursAssign(Request $request){
        $post = $request->all();

        if(empty($post)){
            $role =  Role::select('id_role', 'role_name', 'id_employee_office_hour')->get()->toArray();
            return response()->json(MyHelper::checkGet($role));
        }else{
            if(empty($post['data'])){
                return response()->json(['status' => 'fail', 'messages' => ['Data can not be empty']]);
            }

            foreach ($post['data'] as $val){
                Role::where('id_role', $val['id_role'])->update(['id_employee_office_hour' => ($val['id_employee_office_hour'] == 'default' ? NULL: $val['id_employee_office_hour'])]);
            }
            return response()->json(['status' => 'success']);
        }
    }
}
