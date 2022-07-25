<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Lib\MyHelper;
use App\Http\Models\TransactionProduct;
use DB;
use App\Http\Models\Transaction;
use App\Http\Models\Outlet;
use DatePeriod;
use DateInterval;
use DateTime;
use App\Http\Models\Setting;
use Modules\Transaction\Entities\TransactionProductService;
class HairstylistIncome extends Model
{
    public $primaryKey = 'id_hairstylist_income';
    protected $fillable = [
        'id_user_hair_stylist',
        'type',
        'periode',
        'start_date',
        'end_date',
        'completed_at',
        'status',
        'amount',
        'notes',
    ];

    public function hairstylist_income_details()
    {
        return $this->hasMany(HairstylistIncomeDetail::class, 'id_hairstylist_income');
    }

    public static function calculateIncome(UserHairStylist $hs, $type = 'end')
    {
        $total = 0;
        if ($type == 'middle') {
            $date = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value');
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        } else {
            $type = 'end';
            $date = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value');
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        }
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
        $exists = static::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->whereDate('periode', "$year-$month-$date")->where('type', $type)->where('status', '<>', 'Draft')->exists();
        if ($exists) {
            throw new \Exception("Hairstylist income for periode $type $month/$year already exists for $hs->id_user_hair_stylist");
        }

        $lastDate = static::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->orderBy('end_date', 'desc')->whereDate('end_date', '<', date('Y-m-d'))->where('status', '<>', 'Cancelled')->first();
        if ($lastDate) {
            $startDate = date('Y-m-d', strtotime($lastDate->end_date . '+1 days'));
        } else {
            $startDate = date('Y-m-d', strtotime("$year-" . ($month - 1) . "-$date +1 days"));
            if (date('m', strtotime($startDate)) != ($month - 1)) {
                $startDate = date('Y-m-d', strtotime("$year-$month-01 -1 days"));
            }
        }
        $endDate = date('Y-m-d', strtotime("$year-" . $month . "-$date"));
        if (date('m', strtotime($endDate)) != $month) {
            $endDate = date('Y-m-d', ("$year-" . ($month + 1) . "-01 -1 days"));
        }
        $hsIncome = static::updateOrCreate([
            'id_user_hair_stylist' => $hs->id_user_hair_stylist,
            'type' => $type,
            'periode' => date('Y-m-d', strtotime("$year-$month-$date")),
        ],[
            'start_date' => $startDate,
            'end_date' => $endDate,
            'completed_at' => null,
            'status' => 'Draft',
            'amount' => 0,
        ]);

        if (!$hsIncome) {
            throw new \Exception('Failed create hs income data');
        }
        $total_attend = 0;
        $total_late = 0;
        $total_absen = 0;
        $total_overtime = 0;
        $overtime = array();
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->where('is_on_time', 0)
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,$va['duration']);
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
                if($d>60){
                  $s = floor($d / 60);
                  $m = $s + $m;
                }
                if($m>60){
                  $s = floor($m / 60);
                  $h = $s + $h;
                }
             $total_overtime = $h;
           
        foreach  ($calculations as $calculation) {
            if ($calculation == 'product_commission') {
                $trxs = TransactionProduct::where(array('transaction_product_services.id_user_hair_stylist'=>$hs->id_user_hair_stylist))
                        ->join('transactions','transactions.id_transaction','transaction_products.id_transaction')   
                        ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                        ->join('transaction_breakdowns', function($join) use ($startDate, $endDate) {
                            $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                                ->whereNotNull('transaction_products.transaction_product_completed_at')
                                ->whereBetween('transaction_product_completed_at',[$startDate,$endDate]);
                        })
                        ->where('transaction_product_services.service_status', 'Completed')
                        ->wherenotnull('transaction_product_services.completed_at')
                        ->where('transaction_breakdowns.type', 'fee_hs')
                        ->select('transaction_products.id_transaction', 'transaction_products.id_transaction_product', 'transaction_breakdowns.*')
                        ->get();
                $amount = 0;
                foreach  ($trxs as $item) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => $calculation,
                        'reference' => $item->id_transaction_product,
                    ],
                    [
                        'id_outlet' => $item->transaction->id_outlet,
                        'amount' => $item->value,
                    ]);
                    $amount += $item->value;
                }
                 $total = $total+$amount;
            } elseif (strpos($calculation, 'incentive_') === 0) { // start_with_calculation
                $code = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function($join) use ($hs) {
                                $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                            })->where('hairstylist_group_default_insentifs.code', $code)
                            ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs','hairstylist_group_default_insentifs.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.value IS NOT NULL THEN hairstylist_group_insentifs.value ELSE hairstylist_group_default_insentifs.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.formula IS NOT NULL THEN hairstylist_group_insentifs.formula ELSE hairstylist_group_default_insentifs.formula
                                       END as formula
                                    ')
                                )->first();
                if (!$incentive) {
                    continue;
                }
                $formula = str_replace('value', $incentive->value, $incentive->formula);

                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                $amount = 0;
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                        $hsIncome->update(['notes' => $e->getMessage()]);
                    }

                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => $calculation,
                        'reference' => $incentive->id_hairstylist_group_default_insentifs,
                    ],
                    [
                        'id_outlet' => $id_outlet,
                        'amount' => $amount,
                    ]);
                }
                $total = $total+$amount;
            } elseif (strpos($calculation, 'salary_cut_') === 0) { // start_with_calculation
                $code = str_replace('salary_cut_', '', $calculation);
                $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function($join) use ($hs) {
                    $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                })->where('hairstylist_group_default_potongans.code', $code)
                         ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans','hairstylist_group_default_potongans.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.value IS NOT NULL THEN hairstylist_group_potongans.value ELSE hairstylist_group_default_potongans.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.formula IS NOT NULL THEN hairstylist_group_potongans.formula ELSE hairstylist_group_default_potongans.formula
                                       END as formula
                                    '))
                        ->first();
                if (!$salary_cut) {
                    continue;
                }
                
                $formula = str_replace('value', $salary_cut->value, $salary_cut->formula);
                $amount = 0;
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                        $hsIncome->update(['notes' => $e->getMessage()]);
                    }

                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => $calculation,
                        'reference' => $salary_cut->id_hairstylist_group_default_potongans,
                    ],
                    [
                        'id_outlet' => $id_outlet,
                        'amount' => $amount,
                    ]);
                  
                }
                  $total = $total-$amount;
            }
        }
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,array(
                            'duration'=>$va['duration'],
                            'id_outlet'=>$va['id_outlet'],
                            'id_hairstylist_overtime'=>$va['id_hairstylist_overtime']
                        ));
                    }
                }
                foreach ($ove as $value) {
                    $va = explode(":", $value['duration']);
                    $nominal = 0;
                    $h = $va[0];
                    $incentive = HairstylistGroupOvertimeDefault::leftJoin('hairstylist_group_overtimes', function($join) use ($hs) {
                                $join->on('hairstylist_group_overtimes.id_hairstylist_group_default_overtimes', 'hairstylist_group_default_overtimes.id_hairstylist_group_default_overtimes')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                            })
                            ->select('hairstylist_group_default_overtimes.hours',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_overtimes.value IS NOT NULL THEN hairstylist_group_overtimes.value ELSE hairstylist_group_default_overtimes.value
                                       END as value
                                    '),
                                )->orderby('hours','DESC')->get();
                    foreach ($incentive as $valu) {
                        if($valu['hours']<=(int)$h){
                            $nominal = $valu['value'];
                            break;
                        }
                        $nominal = $valu['value'];
                    }
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => "Overtime",
                        'reference' => $value['id_hairstylist_overtime'],
                    ],
                    [
                        'id_outlet' => $value['id_outlet'],
                        'amount' => $nominal,
                    ]);
                    $total = $total+$nominal;
                }
        $fixed = self::calculateFixedIncentive($hs, $startDate, $endDate);
        foreach ($fixed as $value) {
            $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => "Fixed Incentive",
                        'reference' => $value['id_hairstylist_group_default_fixed_incentive'],
                    ],
                    [
                        'id_outlet' => $value['id_outlet'],
                        'amount' => $value['value'],
                    ]);
            if($value['status']=='incentive'){
                $total = $total+$value['value'];
            }else{
                $total = $total-$value['value'];
            }
            
        }
        $loan = self::calculateLoan($hs, $startDate, $endDate);
        foreach ($loan as $value) {
            $total = $total-$value['value'];
            if($total>=0){
            $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => "Hairstylist Loan",
                        'reference' => $value['id_hairstylist_loan_return'],
                    ],
                    [
                        'id_outlet' => $value['id_outlet'],
                        'amount' => $value['value'],
                    ]);
            $loan_return = HairstylistLoanReturn::where('id_hairstylist_loan_return',$value['id_hairstylist_loan_return'])
                            ->update([
                                'status_return'=>"Success",
                                'date_pay'=>date('Y-m-d H:i:s'),
                            ]);
            }else{
                $total = $total+$value['value'];
                break;
            }
        }
        $hsIncome->update([
            'status' => 'Pending',
            'amount' => $total,
        ]);

        return $hsIncome;
    }
    public static function calculateIncomeExport(UserHairStylist $hs, $startDate,$endDate)
    {
        $total = 0;
        $array = array();
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid,$calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend = 0;
        $total_late = 0;
        $total_absen = 0;
        $total_overtime = 0;
        $overtime = array();
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->where('is_on_time', 0)
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,$va['duration']);
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
                if($d>60){
                  $s = floor($d / 60);
                  $m = $s + $m;
                }
                if($m>60){
                  $s = floor($m / 60);
                  $h = $s + $h;
                }
             $total_overtime = $h;
            
        foreach  ($calculations as $calculation) {
            if (strpos($calculation, 'incentive_') === 0) { // start_with_calculation
                $code = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function($join) use ($hs) {
                                $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                            })->where('hairstylist_group_default_insentifs.code', $code)
                            ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs','hairstylist_group_default_insentifs.name','hairstylist_group_default_insentifs.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.value IS NOT NULL THEN hairstylist_group_insentifs.value ELSE hairstylist_group_default_insentifs.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.formula IS NOT NULL THEN hairstylist_group_insentifs.formula ELSE hairstylist_group_default_insentifs.formula
                                       END as formula
                                    ')
                                )->first();
                if (!$incentive) {
                    continue;
                }
                $formula = str_replace('value', $incentive->value, $incentive->formula);
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                $amount = 0;
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }
                }
                $total = $total+$amount;
               $array[] = array(
                    "name"=> $incentive->name,
                    "value"=> $amount,
                );
            } elseif (strpos($calculation, 'salary_cut_') === 0) { // start_with_calculation
                $code = str_replace('salary_cut_', '', $calculation);
                $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function($join) use ($hs) {
                    $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                })->where('hairstylist_group_default_potongans.code', $code)
                         ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans','hairstylist_group_default_potongans.name','hairstylist_group_default_potongans.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.value IS NOT NULL THEN hairstylist_group_potongans.value ELSE hairstylist_group_default_potongans.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.formula IS NOT NULL THEN hairstylist_group_potongans.formula ELSE hairstylist_group_default_potongans.formula
                                       END as formula
                                    '))
                        ->first();
                if (!$salary_cut) {
                    continue;
                }
                
                $formula = str_replace('value', $salary_cut->value, $salary_cut->formula);
                $amount = 0;
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                foreach ($id_outlets as $id_outlet) {
                   
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            '$total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }
                  
                }
                $total = $total-$amount;
                  $array[] = array(
                    "name"=> $salary_cut->name,
                    "value"=> $amount,
                );
            }
        }
        return $array;
    }
    public static function calculateIncomeProductCode(UserHairStylist $hs, $startDate,$endDate)
    {
        $total = 0;
        $array = array();
       $trxs = TransactionProduct::where(array('transaction_product_services.id_user_hair_stylist'=>$hs->id_user_hair_stylist))
            ->join('transactions','transactions.id_transaction','transaction_products.id_transaction')   
            ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
            ->join('transaction_breakdowns', function($join) use ($startDate, $endDate) {
                $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                    ->whereNotNull('transaction_products.transaction_product_completed_at')
                    ->whereBetween('transaction_product_completed_at',[$startDate,$endDate]);
            })
            ->where('transaction_product_services.service_status', 'Completed')
            ->wherenotnull('transaction_product_services.completed_at')
            ->wherenull('transaction_products.reject_at')
            ->wherenotnull('transaction_products.transaction_product_completed_at')
            ->where('transaction_breakdowns.type', 'fee_hs')
            ->select('transaction_breakdowns.value')
            ->get();
              
        foreach ($trxs as $value) {
            $total = $total+$value->value;
        }
        $array[] = array(
            "name"=> "Total commission",
            "value"=> $total
        );
        return $array;
    }
    public static function calculateTambahanJam(UserHairStylist $hs, $startDate,$endDate)
    {
        $total = 0;
        $array = array();
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid,$calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend = 0;
        $total_late = 0;
        $total_absen = 0;
        $total_overtime = 0;
        $overtime = array();
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->where('is_on_time', 0)
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
							->select('duration')
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,$va['duration']);
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
                if($d>60){
                  $s = floor($d / 60);
                  $m = $s + $m;
                }
                if($m>60){
                  $s = floor($m / 60);
                  $h = $s + $h;
                }
             $total_overtime = $h;
        
          $array = array(
                 array(
                    "name"=>"tambahan jam",
                    "value"=>$total_overtime
                 ),
              
                 array(
                    "name"=>"potongan telat",
                    "value"=>$total_late
                 ),
             );
        return $array;
    }
    public static function calculateIncomeTotal(UserHairStylist $hs, $startDate,$endDate)
    {
        $total = 0;
        $array = array();
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid,$calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend = 0;
        $total_late = 0;
        $total_absen = 0;
        $total_overtime = 0;
        $overtime = array();
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->where('is_on_time', 0)
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
							->select('duration')
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,$va['duration']);
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
                if($d>60){
                  $s = floor($d / 60);
                  $m = $s + $m;
                }
                if($m>60){
                  $s = floor($m / 60);
                  $h = $s + $h;
                }
             $total_overtime = $h;
            
        foreach  ($calculations as $calculation) {
           if (strpos($calculation, 'incentive_') === 0) { // start_with_calculation
                $code = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function($join) use ($hs) {
                                $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                            })->where('hairstylist_group_default_insentifs.code', $code)
                            ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs','hairstylist_group_default_insentifs.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.value IS NOT NULL THEN hairstylist_group_insentifs.value ELSE hairstylist_group_default_insentifs.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_insentifs.formula IS NOT NULL THEN hairstylist_group_insentifs.formula ELSE hairstylist_group_default_insentifs.formula
                                       END as formula
                                    ')
                                )->first();
                if (!$incentive) {
                    continue;
                }
                $formula = str_replace('value', $incentive->value, $incentive->formula);
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                $amount = 0;
                foreach ($id_outlets as $id_outlet) {
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            'total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }
                }
                $total = $total+$amount;
               
            } elseif (strpos($calculation, 'salary_cut_') === 0) { // start_with_calculation
                $code = str_replace('salary_cut_', '', $calculation);
                $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function($join) use ($hs) {
                    $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                })->where('hairstylist_group_default_potongans.code', $code)
                         ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans','hairstylist_group_default_potongans.code',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.value IS NOT NULL THEN hairstylist_group_potongans.value ELSE hairstylist_group_default_potongans.value
                                       END as value
                                    '),
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_potongans.formula IS NOT NULL THEN hairstylist_group_potongans.formula ELSE hairstylist_group_default_potongans.formula
                                       END as formula
                                    '))
                        ->first();
                if (!$salary_cut) {
                    continue;
                }
                
                $formula = str_replace('value', $salary_cut->value, $salary_cut->formula);
                $amount = 0;
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                foreach ($id_outlets as $id_outlet) {
                   
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absen' => $total_absen,
                            '$total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }
                  
                }
                $total = $total-$amount;
                
            }
        }
        $lembur = self::calculateIncomeProductCode($hs, $startDate, $endDate);
        foreach ($lembur as $value) {
            $total += $value['value'];
        }
        $lembur = self::calculateIncomeOvertime($hs, $startDate, $endDate);
        foreach ($lembur as $value) {
            $total += $value['value'];
        }
        $fixed = self::calculateFixedIncentive($hs, $startDate, $endDate);
        foreach ($fixed as $value) {
            if($value['status']=='incentive'){
                $total = $total+$value['value'];
            }else{
                $total = $total-$value['value'];
            }
            
        }
        $loan = self::calculateSalaryCuts($hs, $startDate, $endDate);
        foreach ($loan as $va) {
                $total = $total-$value['value'];
        }
        $location = Outlet::where('id_outlet',$hs->id_outlet)->join('locations','locations.id_location','outlets.id_location')->first();
        $diff = date_diff(date_create(date('Y-m-d')), date_create(date('Y-m-d',strtotime($location->start_date))));
        $proteksi = Setting::where('key','proteksi_hs')->first()['value_text']??[];
        if($proteksi){
            $overtime = json_decode($proteksi,true);
        }else{
            $overtime = array(
                    'range'=>0,
                    'value'=>0
                );    
        }
        $group = HairstylistGroupProteksi::where(array('id_hairstylist_group'=>$hs->id_hairstylist_group))->first();
        $overtime['default_value']    = 0;
        if(isset($group['value'])){
            $overtime['value'] = $group['value'];
        }
        if($diff->m >= $overtime['range']){
                $keterangan = "Non Proteksi";
            }else{
                $keterangan = "Proteksi";
                $total = $overtime['value'];
            }
          $array = array(
              array(
                    "name"=> "Total imbal jasa",
                    "value"=> $total
                    
                ),
              array(
                    "name"=> "Keterangan",
                    "value"=> $keterangan
                    
                ),
             );
        return $array;
    }
    public static function calculateIncomeGross(UserHairStylist $hs, $startDate,$endDate)
    {
        $total = 0;
        $array = array();
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid,$calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend = 0;
        $overtime = array();
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                }
       $outlet_services = Transaction::where(array('transaction_product_services.id_user_hair_stylist'=>$hs->id_user_hair_stylist))
                       ->whereBetween('transactions.transaction_date',[$startDate,$endDate])
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->where('transactions.reject_at', NULL)
                       ->where('transaction_product_services.service_status', 'Completed')
                       ->wherenotnull('transaction_product_services.completed_at')
                       ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                       ->select(
                        DB::raw('
                                 SUM(
                                 CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_product_services.completed_at IS NOT NULL AND transactions.transaction_payment_status = "Completed" AND transaction_product_services.service_status = "Completed" AND transactions.reject_at IS NULL THEN transactions.transaction_gross 
                                         ELSE 0 END
                                 ) as revenue
                                 ')
                       )
                       ->first();
        $array[] = array(
                    "name"=> "hari masuk",
                    "value"=> $total_attend,
                );
        $array[] = array(
                    "name"=> "total gross sale",
                    "value"=> $outlet_services->revenue??0,
                );
        return $array;
    }
    public static function calculateIncomeOvertime(UserHairStylist $hs, $startDate,$endDate)
    {
        $total = 0;
        $array = array();
        $total_attend = 0;
        $total_late = 0;
        $total_absen = 0;
        $total_overtime = 0;
        $overtime = array();
        $set_over = Setting::where('key','overtime_hs')->first()['value']??0;
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
                    $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNotNull('clock_in')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->select('date')
                        ->get();
                    foreach ($total_overtimes as $value) {
                        array_push($overtime,$value);
                    }
                  
                }
                $over = 0;
                $ove = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                            ->wherenotnull('approve_at')
                            ->wherenull('reject_at')
                            ->wheredate('date',$value['date'])
                            ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove,$va['duration']);
                    }
                }
                foreach ($ove as $value) {
                    $va = explode(":", $value);
                    $nominal = 0;
                    $h = $va[0];
                    $m = $va[1];
                    if($m >= $set_over){
                        $h = $h+1;
                    }
                    $incentive = HairstylistGroupOvertimeDefault::leftJoin('hairstylist_group_overtimes', function($join) use ($hs) {
                                $join->on('hairstylist_group_overtimes.id_hairstylist_group_default_overtimes', 'hairstylist_group_default_overtimes.id_hairstylist_group_default_overtimes')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                            })
                            ->select('hairstylist_group_default_overtimes.hours',
                                DB::raw('
                                       CASE WHEN
                                       hairstylist_group_overtimes.value IS NOT NULL THEN hairstylist_group_overtimes.value ELSE hairstylist_group_default_overtimes.value
                                       END as value
                                    '),
                                )->orderby('hours','DESC')->get();
                    foreach ($incentive as $valu) {
                        if($valu['hours']<=(int)$h){
                            $nominal = $valu['value'];
                            break;
                        }
                        $nominal = $valu['value'];
                    }
                    $total = $total+$nominal;
                }
        $array[] = array(
                    "name"=> "Overtime",
                    "value"=> $total
                    
                );
        return $array;
    }
    public static function calculateFixedIncentive(UserHairStylist $hs, $startDate,$endDate)
    {
      $array = array();
      $tanggal = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value')??25;
        // Variable that store the date interval
        // of period 1 day
        $interval = new DateInterval('P1D');

        $realEnd = new DateTime($endDate);
        $realEnd->add($interval);

        $period = new DatePeriod(new DateTime($startDate), $interval, $realEnd);
        $total_date = 0;
        // Use loop to store date into array
        foreach($period as $date) {                 
            $angka = $date->format('d'); 
            if($angka == $tanggal){
                $total_date++;
            }
        }
        $date_now = date('Y-m-d');
        $years_of_service = 0;
        $date3=date_create(date('Y-m-d', strtotime($hs->join_date)));
        $date4=date_create($date_now);
        $diff=date_diff($date3,$date4);
        $years_of_service = $diff->y*12+$diff->m;
        $outlet = Outlet::join('locations','locations.id_location','outlets.id_location')->where(array('id_outlet'=>$hs->id_outlet))->first();
        if(!$outlet){
            return $array;
        }
        $outlet_age = 0;
        $date3=date_create(date('Y-m-d', strtotime($outlet->start_date)));
        $date4=date_create($date_now);
        $diff=date_diff($date3,$date4);
        $outlet_age = $diff->y*12+$diff->m;
        $overtime = HairstylistGroupFixedIncentiveDefault::with(['detail'])->get();
         foreach ($overtime as $value) {
             foreach ($value['detail'] as $va) {
               $insen = HairstylistGroupFixedIncentive::where(array('id_hairstylist_group_default_fixed_incentive_detail'=>$va['id_hairstylist_group_default_fixed_incentive_detail'],'id_hairstylist_group'=>$hs->id_hairstylist_group))->first();
                 $va['default_value'] = $va['value'];
                 $va['default']    = 0;
                 if($insen){
                    $va['value']      = $insen->value; 
                    $va['default']    = 1;
                 }

             }
         }
         foreach ($overtime as $va) {
             $harga = 0;
             if(isset($va['detail'])){
                 if($va['type']=="Multiple"){
                     if($va['formula']=='outlet_age'){
                         $h = $outlet_age;
                     }elseif($va['formula']=='years_of_service'){
                         $h = $years_of_service;
                     }else{
                         break;
                     }
                     foreach ($va['detail'] as $valu) {
                        if($valu['range']<=(int)$h){
                            if($valu['default'] == 1){
                            $harga = $valu['value']*$total_date;
                            }else{
                            $harga = $valu['default_value']*$total_date;
                            }
                            break;
                        }
                    }
                 }else{
                     
                     if($va['detail']['0']['default']== 1){
                         $harga = $va['detail']['0']['value']*$total_date;
                     }else{
                         $harga = $va['detail']['0']['default_value']*$total_date;
                     }
                 }
             }
             $array[] = array(
                    "id_hairstylist_group_default_fixed_incentive"=> $va['id_hairstylist_group_default_fixed_incentive'],
                    "name"=> $va['name_fixed_incentive'],
                    "value"=> $harga,
                    'status'=>$va['status'],
                    'id_outlet'=>$outlet['id_outlet']
                );
         }
        
        return $array;
    }
    public static function calculateSalaryCuts(UserHairStylist $hs, $startDate,$endDate)
    {
        $array = array();
        $loan = HairstylistLoan::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                ->join('hairstylist_category_loans','hairstylist_category_loans.id_hairstylist_category_loan','hairstylist_loans.id_hairstylist_category_loan')
                ->join('hairstylist_loan_returns', function($join) use ($startDate,$endDate) {
                            $join->on('hairstylist_loan_returns.id_hairstylist_loan','hairstylist_loans.id_hairstylist_loan')
                                ->whereBetween('hairstylist_loan_returns.date_pay',[$startDate,$endDate])
                                ->where('hairstylist_loan_returns.status_return','Success');    
                            })
                ->where('status_loan','Success')
                ->select('hairstylist_category_loans.name_category_loan',
                        DB::raw('
                               SUM(
                                 CASE WHEN hairstylist_loan_returns.status_return = "Success" AND hairstylist_loan_returns.date_pay IS NOT NULL THEN hairstylist_loan_returns.amount_return 
                                         ELSE 0 END
                                 ) as value
                            '),
                        )
                ->groupby('hairstylist_category_loans.id_hairstylist_category_loan')
                ->get();
        foreach ($loan as $value) {
           $array[] = array(
                    "name"=> $value['name_category_loan'],
                    "value"=> $value['value']
                    
                );
        }
        
        return $array;
    }
    public static function calculateLoan(UserHairStylist $hs, $startDate,$endDate)
    {
        $array = array();
        $loan = HairstylistLoan::where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                ->join('hairstylist_category_loans','hairstylist_category_loans.id_hairstylist_category_loan','hairstylist_loans.id_hairstylist_category_loan')
                ->join('hairstylist_loan_returns', function($join) use ($startDate,$endDate) {
                            $join->on('hairstylist_loan_returns.id_hairstylist_loan','hairstylist_loans.id_hairstylist_loan')
                                ->where('hairstylist_loan_returns.return_date','<=',$endDate)
                                ->where('hairstylist_loan_returns.status_return','Pending');    
                            })
                ->where('status_loan','Success')
                ->get();
        foreach ($loan as $value) {
           $array[] = array(
                    "name"=> $value['name_category_loan'],
                    "value"=> $value['amount_return'],
                    "id_outlet"=>$hs->id_outlet,
                    "id_hairstylist_loan_return"=>$value['id_hairstylist_loan_return']
                );
        }
        return $array;
    }
}