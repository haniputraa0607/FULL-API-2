<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Employee\Entities\EmployeeOfficeHour;
use Modules\Employee\Entities\EmployeeOfficeHourShift;
use App\Lib\MyHelper;

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

        if($create && $post['office_hour_type'] == 'Use Shift'){
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

        return response()->json(MyHelper::checkCreate($create));
    }

    public function officeHoursList(){
        $res = EmployeeOfficeHour::get()->toArray();
        return response()->json(MyHelper::checkGet($res));
    }

    public function officeHoursDetail(Request $request){
        $post = $request->all();

        if(!empty($post['id_employee_office_hour'])){
            $detail = EmployeeOfficeHour::where('id_employee_office_hour', $post['id_employee_office_hour'])->with('office_hour_shift')->first();

            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
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
}
