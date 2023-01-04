<?php

namespace Modules\Employee\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use DateInterval;
use DatePeriod;
use DateTime;
use DB;
use Illuminate\Database\Eloquent\Model;
use App\Lib\Icount;

class EmployeeIncome extends Model
{
    public $primaryKey  = 'id_employee_income';
    protected $table = 'employee_incomes';
    protected $fillable = [
        'id_user',
        'periode',
        'start_date',
        'end_date',
        'completed_at',
        'status',
        'amount',
        'notes',
        'value_detail',
    ];
    public function employee_income_details()
    {
        return $this->hasMany(EmployeeIncomeDetail::class, 'id_employee_income');
    }
     public static function calculateIncome($hs)
    {
        $total = 0;
        $pemasukan = array();
        $total_pemasukan = 0;
        $pengurangan = array();
        $total_pengurangan = 0;
        $employee = Employee::where('id_user',$hs->id)->select('id_employee')->first();
        $calculations         = json_decode(MyHelper::setting('delivery_income', 'value_text', '[]'), true) ?? [];
        $date = (int) MyHelper::setting('delivery_income', 'value');
        
        if (!$calculations) {
            throw new \Exception('No calculation for current periode. Check setting!');
        }
        $year = date('Y');
        if ($date >= date('d')) {
            $month = (int) date('m') - 1;
            if (!$month) {
                $month = 12;
                $year -= 1;
            }
        } else {
            $month = (int) date('m');
        }
        $exists = static::where('id_user', $hs->id)->whereDate('periode', "$year-$month-$date")->where('status', '<>', 'Draft')->exists();
        if ($exists) {
            throw new \Exception("Employee income for periode $month/$year already exists for $hs->id");
        }
        $lastDate = static::where('id_user', $hs->id)->orderBy('end_date', 'desc')->whereDate('end_date', '<', date('Y-m-d'))->where('status', '<>', 'Cancelled')->first();
        if ($lastDate) {
            $startDate = date('Y-m-d', strtotime($lastDate->end_date . '+1 days'));
        } else {
            $s = $calculations['start'];
            $startDate = date('Y-m-d', strtotime("$year-" . ($month - 1) . "-$s"));
            if (date('m', strtotime($startDate)) != ($month - 1)) {
                $startDate = date('Y-m-d', strtotime("$year-$month-01 -1 days"));
            }
        }
        $e = $calculations['end'];
        $endDate = date('Y-m-d', strtotime("$year-" . $month . "-$e"));
        if (date('m', strtotime($endDate)) != $month) {
            $endDate = date('Y-m-d', ("$year-" . ($month + 1) . "-01 -1 days"));
        }
        $hsIncome = static::updateOrCreate([
            'id_user' => $hs->id,
            'periode' => date('Y-m-d', strtotime("$year-$month-$date")),
        ], [
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'completed_at' => null,
            'status'       => 'Draft',
            'amount'       => 0,
        ]);
        if (!$hsIncome) {
            throw new \Exception('Failed create hs income data');
        }
        $total_attend   = 0;
        $total_late     = 0;
        $total_absen    = 0;
        $total_overtime = 0;
        $overtime       = array();
        $outlet = Outlet::where('id_outlet', $hs->id_outlet)->first();
        $id_outlets     = EmployeeAttendance::where('id', $hs->id)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        
        foreach ($id_outlets as $id_outlet) {
            $total_attend = EmployeeScheduleDate::leftJoin('employee_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('employee_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')
                    ->where('id', $hs->id)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('employee_attendances.attendance_date', [$startDate, $endDate])
                ->count();
            $total_late = EmployeeScheduleDate::leftJoin('employee_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('employee_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')
                    ->where('id', $hs->id)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->where('is_on_time', 0)
                ->whereBetween('employee_attendances.attendance_date', [$startDate, $endDate])
                ->count();
            $total_absen = EmployeeScheduleDate::leftJoin('employee_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('employee_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')
                    ->where('id', $hs->id)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNull('clock_in')
                ->whereBetween('employee_attendances.attendance_date', [$startDate, $endDate])
                ->count();
            $total_overtimes = EmployeeScheduleDate::leftJoin('employee_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('employee_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')
                    ->where('id', $hs->id)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('employee_attendances.attendance_date', [$startDate, $endDate])
                ->select('date')
                ->get();
            foreach ($total_overtimes as $value) {
                array_push($overtime, $value);
            }

        }
        $over = 0;
        $ove  = array();
        foreach (array_unique($overtime) as $value) {
            $overtimess = EmployeeOvertime::where('id_employee', $hs->id)
                ->wherenotnull('approve_at')
                ->wherenull('reject_at')
                ->wheredate('date', $value['date'])
                ->get();
            foreach ($overtimess as $va) {
                array_push($ove, $va['duration']);
            }
        }
        $h = 0;
        $m = 0;
        $d = 0;
        foreach ($ove as $value) {
            $va = explode(":", $value);
            $h += $va[0];
            $m += $va[1];
            $d += $va[2];
        }
        if ($d > 60) {
            $s = floor($d / 60);
            $m = $s + $m;
        }
        if ($m > 60) {
            $s = floor($m / 60);
            $h = $s + $h;
        }
        $total_overtime = $h;
        //basic_salary Pemasukkan
        $basic = Setting::where('key','basic_salary_employee')->first();
        $basic_salary = $basic['value'];
        $role = EmployeeRoleBasicSalary::where(array('id_role'=>$hs->id_role))->first();
        if(isset($role)){
            $basic_salary = $role['value'];
        }
        $hsIncome->employee_income_details()->updateOrCreate([
                        'source'    => "Basic Salary",
                        'reference' => $role->id_employee_role_basic_salary??null,
                    ],
                        [
                            'id_outlet' =>$hs->id_outlet,
                            'name_income' =>"Basic Salary",
                            'amount'    => $basic_salary,
                            'type'    => "Incentive"
                        ]);
        $total = $total+$basic_salary;
        $total_pemasukan = $total_pemasukan+$basic_salary;
        $basic = array(
            'name'=>"Basic Salary",
            'amount'=>$basic_salary
        );
        array_push($pemasukan,$basic);
        //fixed incentive
        $fixed = self::calculateFixedIncentive($hs, $startDate, $endDate,$outlet);
        foreach ($fixed as $value) {
            if ($value['status'] == 'incentive') {
                $sta = "Incentive";
            } else {
                $sta = "Salary Cut";
            }
            if ($value['status'] == 'incentive') {
                if($value['value']>0){
                    $hsIncome->employee_income_details()->updateOrCreate([
                'source'    => "Fixed Incentive",
                'reference' => $value['id_employee_role_default_fixed_incentive'],
            ],
                [
                    'name_income' => $value['name'],
                    'id_outlet' => $value['id_outlet'],
                    'amount'    => $value['value'],
                    'type'      => $sta
                ]);
                $total = $total + $value['value'];
                $total_pemasukan = $total_pemasukan+$value['value'];
                $basic = array(
                        'name'=>$value['name'],
                        'amount'=>$value['value']
                    );
                array_push($pemasukan,$basic);
                }
            } else {
                if($value['value']>0){
                     $hsIncome->employee_income_details()->updateOrCreate([
                'source'    => "Fixed Incentive",
                'reference' => $value['id_employee_role_default_fixed_incentive'],
            ],
                [
                    'name_income' => $value['name'],
                    'id_outlet' => $value['id_outlet'],
                    'amount'    => $value['value'],
                    'type'      => $sta
                ]);
                
                 $total_pengurangan = $total_pengurangan+$value['value'];
                $total = $total - $value['value'];
                     $basic = array(
                        'name'=>$value['name'],
                        'amount'=>$value['value']
                    );
                 array_push($pengurangan,$basic);
                }
            }

        }
        
        //overtime
        $id_outlets = EmployeeAttendance::where('id', $hs->id)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        $overtime = array();
        foreach ($id_outlets as $id_outlet) {
            $total_overtimes = EmployeeScheduleDate::leftJoin('employee_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('employee_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')
                    ->where('id', $hs->id)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->select('attendance_date')
                ->get();
            foreach ($total_overtimes as $value) {
                array_push($overtime, $value);
            }

        }
        $over = 0;
        $ove  = array();
        foreach (array_unique($overtime) as $value) {
            $overtimess = EmployeeOverTime::where('id_employee', $hs->id)
                ->wherenotnull('approve_at')
                ->wherenull('reject_at')
                ->wheredate('date', $value['date'])
                ->get();
            foreach ($overtimess as $va) {
                array_push($ove, array(
                    'duration'                => $va['duration'],
                    'id_outlet'               => $va['id_outlet'],
                    'id_employee_overtime' => $va['id_employee_overtime'],
                ));
            }
        }
        $nominal_overtime = 0;
        $nominal_overtimes = 0;
        foreach ($ove as $value) {
            $nominal_overtimes = 1;
            $va        = explode(":", $value['duration']);
            $nominal   = 0;
            $h         = $va[0];
            $incentive = EmployeeRoleOvertimeDefault::leftJoin('employee_role_overtimes', function ($join) use ($hs) {
                $join->on('employee_role_overtimes.id_employee_role_default_overtimes', 'employee_role_default_overtimes.id_employee_role_default_overtimes')
                    ->where('id_role', $hs->id_role);
            })
                ->select('employee_role_default_overtimes.hours',
                    DB::raw('
                                       CASE WHEN
                                       employee_role_overtimes.value IS NOT NULL THEN employee_role_overtimes.value ELSE employee_role_default_overtimes.value
                                       END as value
                                    '),
                )->orderby('hours', 'DESC')->get();
            foreach ($incentive as $valu) {
                if ($valu['hours'] <= (int) $h) {
                    $nominal = $valu['value'];
                    break;
                }
                $nominal = $valu['value'];
            }
            $hsIncome->employee_income_details()->updateOrCreate([
                'source'    => "Overtime",
                'reference' => $value['id_hairstylist_overtime'],
            ],
                [
                    'name_income' => "Overtime",
                    'id_outlet' => $value['id_outlet'],
                    'amount'    => $nominal,
                    'type'      => "Incentive"
                ]);
           
            $nominal_overtime = $total+ $nominal;
        }
        if($nominal_overtimes == 1){
                $basic = array(
                        'name'=>"Overtime",
                        'amount'=>$nominal_overtime
                    );
                array_push($pemasukan,$basic);
                $total += $total + $nominal_overtime;
                $total_pemasukan += $total_pemasukan + $nominal_overtime;
        }
        //Incentive
        $incen = EmployeeRoleIncentiveDefault::all();
        foreach ($incen as $va){
             $code      = $va['id_employee_role_default_incentive'];
                $incentive = EmployeeRoleIncentiveDefault::leftJoin('employee_role_incentives', function ($join) use ($hs) {
                    $join->on('employee_role_incentives.id_employee_role_default_incentive', 'employee_role_default_incentives.id_employee_role_default_incentive')
                        ->where('id_role', $hs->id_role);
                })->where('employee_role_default_incentives.id_employee_role_default_incentive', $code)
                    ->select('employee_role_default_incentives.id_employee_role_default_incentive','employee_role_default_incentives.name', 'employee_role_default_incentives.code',
                        DB::raw('
                                       CASE WHEN
                                       employee_role_incentives.value IS NOT NULL THEN employee_role_incentives.value ELSE employee_role_default_incentives.value
                                       END as value
                                    '),
                        DB::raw('
                                       CASE WHEN
                                       employee_role_incentives.formula IS NOT NULL THEN employee_role_incentives.formula ELSE employee_role_default_incentives.formula
                                       END as formula
                                    ')
                    )->first();
                if (!$incentive) {
                    continue;
                }
                $formula = str_replace('value', $incentive->value, $incentive->formula);

                $id_outlets = EmployeeAttendance::where('id', $hs->id)->get()->pluck('id_outlet');
                $amount     = 0;
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend'   => $total_attend,
                            'total_late'     => $total_late,
                            'total_absen'    => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                        $hsIncome->update(['notes' => $e->getMessage()]);
                    }
                    if($amount!=0){
                    $hsIncome->employee_income_details()->updateOrCreate([
                        'source'    => "Incentive",
                        'reference' => $incentive->id_employee_role_default_incentive,
                    ],
                        [
                            'name_income' => $incentive->name,
                            'id_outlet' => $id_outlet,
                            'amount'    => $amount,
                            'type'      => "Incentive"
                        ]);
                   
                    }
                }
                $total = $total + $amount;
                $total_pemasukan = $total_pemasukan + $amount;
                if($amount!=0){
                 $basic = array(
                        'name'=>$incentive->name,
                        'amount'=>$amount
                    );
                array_push($pemasukan,$basic);
                }
        }
        //salary cut
        $salary = EmployeeRoleSalaryCutDefault::all();
        foreach ($salary as $va){
        // start_with_calculation
                $code      = $va['id_employee_role_default_salary_cut'];
                $salary_cut = EmployeeRoleSalaryCutDefault::leftJoin('employee_role_salary_cuts', function ($join) use ($hs) {
                    $join->on('employee_role_salary_cuts.id_employee_role_default_salary_cut', 'employee_role_default_salary_cuts.id_employee_role_default_salary_cut')
                        ->where('id_role', $hs->id_role);
                })->where('employee_role_default_salary_cuts.id_employee_role_default_salary_cut', $code)
                    ->select('employee_role_default_salary_cuts.id_employee_role_default_salary_cut', 'employee_role_default_salary_cuts.name', 'employee_role_default_salary_cuts.code',
                        DB::raw('
                                       CASE WHEN
                                       employee_role_salary_cuts.value IS NOT NULL THEN employee_role_salary_cuts.value ELSE employee_role_default_salary_cuts.value
                                       END as value
                                    '),
                        DB::raw('
                                       CASE WHEN
                                       employee_role_salary_cuts.formula IS NOT NULL THEN employee_role_salary_cuts.formula ELSE employee_role_default_salary_cuts.formula
                                       END as formula
                                    '))
                    ->first();
                if (!$salary_cut) {
                    continue;
                }

                $formula    = str_replace('value', $salary_cut->value, $salary_cut->formula);
                $amount     = 0;
                $id_outlets = EmployeeAttendance::where('id', $hs->id)->get()->pluck('id_outlet');
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend'   => $total_attend,
                            'total_late'     => $total_late,
                            'total_absen'    => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                        $hsIncome->update(['notes' => $e->getMessage()]);
                    }
                    
                    if($amount!=0){
                       $hsIncome->employee_income_details()->updateOrCreate([
                        'source'    => "Salary Cuts",
                        'reference' => $salary_cut->id_employee_role_default_salary_cut,
                    ],
                        [
                            'name_income' => $salary_cut->name,
                            'id_outlet' => $id_outlet,
                            'amount'    => $amount,
                            'type'    => "Salary Cut",
                        ]);
                       
                    }
                    

                }
                $total = $total - $amount;
                $total_pengurangan = $total_pengurangan + $amount;
                if($amount!=0){
                $basic = array(
                            'name'=>$salary_cut->name,
                            'amount'=>$amount
                        );
                    array_push($pengurangan,$basic);
                }
         }
        $loan = self::calculateLoan($hs, $startDate, $endDate);
        foreach ($loan as $value) {
            $total = $total - $value['value'];
            if ($total >= 0) {
                 $total_pengurangan = $total_pengurangan + $value['value'];
                    $hsIncome->employee_income_details()->updateOrCreate([
                        'source'    => "Employee Loan",
                        'reference' => $value['id_employee_loan_return'],
                        ],
                        [
                            'name_income' => $value['name'],
                            'id_outlet' => $value['id_outlet'],
                            'amount'    => $value['value'],
                            'type'      => 'Salary Cut'
                        ]);
                     $basic = array(
                            'name'=>$value['name'],
                            'amount'=>$value['value']
                        );
                    array_push($pengurangan,$basic);
                    $loan_return = EmployeeLoanReturn::where('id_employee_loan_return', $value['id_employee_loan_return'])
                        ->update([
                            'status_return' => "Success",
                            'return_date'      => date('Y-m-d H:i:s'),
                        ]);
            } else {
                $total = $total + $value['value'];
                break;
            }
        }
        $value_detail = array(
            'total_attend'=>$total_attend,
            'total_absen'=>$total_absen,
            'total_late'=>$total_late,
            'total_overtime'=>$total_overtime,
            'pemasukan'=>$pemasukan,
            'total_pemasukan'=>$total_pemasukan,
            'pengurangan'=>$pengurangan,
            'total_pengurangan'=>$total_pengurangan,
            'total_pendapatan'=>$total_pemasukan-$total_pengurangan,
        );
        $hsIncome->update([
            'status' => 'Pending',
            'amount' => $total,
            'value_detail'=>json_encode($value_detail)
        ]);

        return $hsIncome;
    }
    public static function calculateFixedIncentive($hs, $startDate, $endDate, $outlet)
    {
        $array   = array();
        $tanggal = (int) MyHelper::setting('delivery_income', 'value') ?? 25;
        // Variable that store the date interval
        // of period 1 day
        $interval = new DateInterval('P1D');

        $realEnd = new DateTime($endDate);
        $realEnd->add($interval);

        $period     = new DatePeriod(new DateTime($startDate), $interval, $realEnd);
        $total_date = 1;
        // Use loop to store date into array
//        foreach ($period as $date) {
//            $angka = $date->format('d');
//            if ($angka == $tanggal) {
//                $total_date++;
//            }
//        }
        $date_now         = date('Y-m-d');
        $years_of_service = 0;
        $date3            = date_create(date('Y-m-d', strtotime($hs->start_date)));
        $date4            = date_create($date_now);
        $diff             = date_diff($date3, $date4);
        $years_of_service = $diff->y * 12 + $diff->m;
        $outlet           = $outlet;
        if (!$outlet) {
            return $array;
        }
        $outlet_age = 0;
        $date3      = date_create(date('Y-m-d', strtotime($outlet->start_date)));
        $date4      = date_create($date_now);
        $diff       = date_diff($date3, $date4);
        $outlet_age = $diff->y * 12 + $diff->m;
        $overtime   = EmployeeRoleFixedIncentiveDefault::with(['detail'])->get();
        
        foreach ($overtime as $value) {
            foreach ($value['detail'] as $va) {
                $insen               = EmployeeRoleFixedIncentive::where(array('id_employee_role_default_fixed_incentive_detail' => $va['id_employee_role_default_fixed_incentive_detail'], 'id_role' => $hs->id_role))->first();
                $va['default_value'] = $va['value'];
                $va['default']       = 0;
                if ($insen) {
                    $va['value']   = $insen->value;
                    $va['default'] = 1;
                }

            }
        }
        foreach ($overtime as $va) {
            $harga = 0;
            if (isset($va['detail'])) {
                if ($va['type'] == "Multiple") {
                    if ($va['formula'] == 'outlet_age') {
                        $h = $outlet_age;
                    } elseif ($va['formula'] == 'years_of_service') {
                        $h = $years_of_service;
                    } else {
                        break;
                    }
                    foreach ($va['detail'] as $valu) {
                        if ($valu['range'] <= (int) $h) {
                            if ($valu['default'] == 1) {
                                $harga = $valu['value'] * $total_date;
                            } else {
                                $harga = $valu['default_value'] * $total_date;
                            }
                            break;
                        }
                    }
                } else {

                    if ($va['detail']['0']['default'] == 1) {
                        $harga = $va['detail']['0']['value'] * $total_date;
                    } else {
                        $harga = $va['detail']['0']['default_value'] * $total_date;
                    }
                }
            }
            $array[] = array(
                "id_employee_role_default_fixed_incentive" => $va['id_employee_role_default_fixed_incentive'],
                "name"                                         => $va['name_fixed_incentive'],
                "value"                                        => $harga,
                'status'                                       => $va['status'],
                'id_outlet'                                    => $outlet['id_outlet'],
            );
        }
        return $array;
    }
    public static function calculateLoan($hs, $startDate, $endDate)
    {
        $array = array();
        $loan  = EmployeeLoan::where('id_user', $hs->id)
            ->join('employee_category_loans', 'employee_category_loans.id_employee_category_loan', 'employee_loans.id_employee_category_loan')
            ->join('employee_loan_returns', function ($join) use ($startDate, $endDate) {
                $join->on('employee_loan_returns.id_employee_loan', 'employee_loans.id_employee_loan')
                    ->where('employee_loan_returns.return_date', '<=', $endDate)
                    ->where('employee_loan_returns.status_return', 'Pending');
            })
            ->get();
          
        foreach ($loan as $value) {
                $array[] = array(
                    "name"                       => $value['name_category_loan'],
                    "value"                      => $value['amount_return'],
                    "id_outlet"                  => $hs->id_outlet,
                    "id_employee_loan_return" => $value['id_employee_loan_return'],
                );
        }
        return $array;
    }
}