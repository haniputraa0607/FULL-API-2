<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Employee\Entities\EmployeeOfficeHour;
use Modules\Employee\Entities\EmployeeOfficeHourAssign;
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
        $res = EmployeeOfficeHour::with('office_hour_shift')->get()->toArray();
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
        if($update && $post['office_hour_type'] == 'Use Shift'){
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

    public function assignOfficeHoursCreate(Request $request){
        $post = $request->all();
        $post['created_by'] = $request->user()->id;
        $create = EmployeeOfficeHourAssign::create($post);
        return response()->json(MyHelper::checkCreate($create));
    }

    public function assignOfficeHoursList(Request $request){
        $res = EmployeeOfficeHourAssign::join('departments', 'departments.id_department', 'employee_office_hour_assign.id_department')
                ->join('job_levels', 'job_levels.id_job_level', 'employee_office_hour_assign.id_job_level')
                ->join('employee_office_hours', 'employee_office_hours.id_employee_office_hour', 'employee_office_hour_assign.id_employee_office_hour')
                ->leftJoin('users as admin_create', 'admin_create.id', 'employee_office_hour_assign.created_by')
                ->leftJoin('users as admin_update', 'admin_update.id', 'employee_office_hour_assign.updated_by')
                ->select('employee_office_hour_assign.*', 'departments.department_name', 'job_levels.job_level_name', 'employee_office_hours.office_hour_name', 'employee_office_hours.office_hour_type',
                    'admin_create.name as admin_create_name', 'admin_update.name as admin_update_name')
                ->get()->toArray();
        return response()->json(MyHelper::checkGet($res));
    }

    public function assignOfficeHoursDetail(Request $request){
        $res = EmployeeOfficeHourAssign::join('departments', 'departments.id_department', 'employee_office_hour_assign.id_department')
            ->join('job_levels', 'job_levels.id_job_level', 'employee_office_hour_assign.id_job_level')
            ->join('employee_office_hours', 'employee_office_hours.id_employee_office_hour', 'employee_office_hour_assign.id_employee_office_hour')
            ->leftJoin('users as admin_create', 'admin_create.id', 'employee_office_hour_assign.created_by')
            ->leftJoin('users as admin_update', 'admin_update.id', 'employee_office_hour_assign.updated_by')
            ->select('employee_office_hour_assign.*', 'departments.department_name', 'job_levels.job_level_name', 'employee_office_hours.office_hour_name', 'employee_office_hours.office_hour_type',
                'admin_create.name as admin_create_name', 'admin_update.name as admin_update_name')
            ->first();
        return response()->json(MyHelper::checkGet($res));
    }

    public function assignOfficeHoursUpdate(Request $request){
        $post = $request->all();
        if(empty($post['id_employee_office_hour_assign'])){
            return response()->json(['status'   => 'fail', 'messages' => ['ID can not be empty']]);
        }

        $post['updated_by'] = $request->user()->id;
        $update = EmployeeOfficeHourAssign::where('id_employee_office_hour_assign', $post['id_employee_office_hour_assign'])->update($post);
        return response()->json(MyHelper::checkUpdate($update));
    }


    public function assignOfficeHoursDelete(Request $request){
        $post = $request->all();
        if(empty($post['id_employee_office_hour_assign'])){
            return response()->json(['status'   => 'fail', 'messages' => ['ID can not be empty']]);
        }

        $delete = EmployeeOfficeHourAssign::where('id_employee_office_hour_assign', $post['id_employee_office_hour_assign'])->delete();
        return response()->json(MyHelper::checkDelete($delete));
    }
}
