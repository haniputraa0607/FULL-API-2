<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;

use App\Lib\MyHelper;
use DB;
use DateTime;
use DateTimeZone;
use PharIo\Manifest\EmailTest;
use Auth;
use Modules\Employee\Entities\EmployeeIncome;
use Modules\Employee\Entities\EmployeeIncomeDetail;
use Config;
use Modules\Employee\Http\Requests\Income\Payslip;
use Modules\Employee\Http\Requests\Income\Password;

class ApiIncome extends Controller
{

    public function cron_end() {
       $log = MyHelper::logCron('Cron Income Employee');
        try {
        $hs = User::where('level','admin')
                ->join('employees','employees.id_user','users.id')
                ->where('employees.status','active')
                ->wherenotnull('start_date')
                ->wherenotnull('id_outlet')
                ->wherenotnull('id_role')
                ->select('id_user','id_outlet','id_role','start_date')
                ->get();
        $type = 'end';
        foreach ($hs as $value) {
           $income = $this->schedule_income($value['id_user']);
        }
        $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
             return response()->json($e->getMessage());
        }
    }
    public function schedule_income($id) {
       $b = new EmployeeIncome();
       $hs = User::where('id',$id)->join('employees','employees.id_user','users.id')
                ->where('employees.status','active')
                ->wherenotnull('start_date')
                ->wherenotnull('id_outlet')
                ->wherenotnull('id_role')
                ->select('id','id_outlet','id_role','start_date')->first();
       
       $bro = $b->calculateIncome($hs);
       return $bro;
    }
    public function payslip(Payslip $request){
        $awal = date('Y-m-01', strtotime($request->month));
        $akhir = date('Y-m-t', strtotime($request->month));;
        $income = EmployeeIncome::where('id_user',Auth::user()->id)->whereBetween('periode',[$awal,$akhir])->first();
        if($income){
            $array = json_decode($income->value_detail);
        }else{
            $array = 'Payslip not found in periode '.date('M Y', strtotime($request->month));
        }
        return response()->json(MyHelper::checkGet($array));
    }
    public function password(Password $request){
        $array = 'Password match';
        return response()->json(MyHelper::checkGet($array));
    }
}
