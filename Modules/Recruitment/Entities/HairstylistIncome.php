<?php

namespace Modules\Recruitment\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Lib\MyHelper;
use DateInterval;
use DatePeriod;
use DateTime;
use DB;
use Illuminate\Database\Eloquent\Model;
use App\Lib\Icount;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\Recruitment\Entities\HairstylistLoanIcount;
use Modules\Transaction\Entities\TransactionBreakdown;

class HairstylistIncome extends Model
{
    public $primaryKey  = 'id_hairstylist_income';
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
        'value_detail',
    ];

    public function hairstylist_income_details()
    {
        return $this->hasMany(HairstylistIncomeDetail::class, 'id_hairstylist_income');
    }
    public static function calculateIncome(UserHairStylist $hs, $type = 'end',$queue)
    {
        $total = 0;
        if ($type == 'middle') {
            $date         = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value');
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? null;
        } else {
            $type         = 'end';
            $date         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value');
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? null;
        }
        if (!$calculations && $type =='middle') {
            throw new \Exception('No calculation for current periode. Check setting!');
        }
        $year = $queue['year'];
        if ($date >= date('d')) {
            $month = (int) $queue['month'] - 1;
            if (!$month) {
                $month = 12;
                $year -= 1;
            }
        } else {
            $month = (int) $queue['month'];
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
        $starts = date('Y-m-d', strtotime($endDate. "-1 months +1 days"));
        $jadwal = HairstylistSchedule::where(array(
            'id_user_hair_stylist'=>$hs->id_user_hair_stylist,
            'schedule_month'=>$month,
            'schedule_year'=>$year
        ))->first();
        if(!$jadwal){
            return false;
        }
        $hsIncome = static::updateOrCreate([
            'id_user_hair_stylist' => $hs->id_user_hair_stylist,
            'type'                 => $type,
            'periode'              => date('Y-m-d', strtotime("$year-$month-$date")),
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
        if ($type == 'middle') {
            $dates         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value')??0;
            $calcu = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
            if($dates>0){
                $dates = date('Y-m-'.$dates, strtotime($endDate));
                $dates = date('Y-m-d', strtotime($dates.'-1 months +1 days'));
            }
        } else {
            $dates         = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value')??0;
            $calcu = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
            if($dates>0){
                $dates = date('Y-m-'.$dates, strtotime($endDate));
                $dates = date('Y-m-d', strtotime($dates.'+1 days'));
            }
        }
        $call = array(); 
        foreach ($calculations as $calculation) {
            if (!$calcu) {
                $call[] = array(
                    'calculation'=>$calculation, 
                    'start_date'=>$starts, 
                    'end_date'=>$endDate, 
                );
            }
            if (in_array($calculation, $calcu)){
                $call[] = array(
                    'calculation'=>$calculation, 
                    'start_date'=>$dates, 
                    'end_date'=>$endDate, 
                );
                }else{
                    $call[] = array(
                        'calculation'=>$calculation, 
                        'start_date'=>$starts, 
                        'end_date'=>$endDate, 
                    );
                }
        }
        $incomeDefault = HairstylistGroupFixedIncentiveDefault::with(['detail'])->get();
        $total_attend   = 0;
        $total_late     = 0;
        $total_absen    = 0;
        $total_overtime = 0;
        $overtime       = array();
        $outlet         = Outlet::where('id_outlet', $hs->id_outlet)->first();
        $id_outlets     = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        
        $minOvertimeMinutes = MyHelper::setting('overtime_hs', 'value', 45);
        $overtimes = HairstylistOverTime::wherenotnull('approve_at')
            ->wherenull('reject_at')
            ->where('not_schedule',0)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->select('duration', 'id_user_hair_stylist', 'id_outlet', \DB::raw('DATE(date) as datex'))
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) use ($minOvertimeMinutes) {
                $newItem = $item->groupBy('id_outlet');
                return $newItem->map(function($item2) use ($minOvertimeMinutes) {
                    $newItem2 = $item2->groupBy('datex');
                    return $newItem2->map(function ($item3) use ($minOvertimeMinutes) {
                        $duration = 0; // in second
                        foreach ($item3 as $i) {
                            $duration += strtotime($i['duration']) - strtotime('00:00:00');
                        }
                        $sisa = $duration % 3600; // sisa detik
                        $overtime = floor($duration / 3600); // jam overtime awal
                        $overtime += ($sisa / 60) >= $minOvertimeMinutes ? 1 : 0;
                        return (int) $overtime;
                    });
                });
            });
        $outlets = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
           ->whereDate('hairstylist_schedule_dates.date', '>=', $startDate)
            ->whereDate('hairstylist_schedule_dates.date', '<=', $endDate)
            ->selectRaw('id_outlet, id_user_hair_stylist')
            ->where('hairstylist_schedules.id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get();
        $list_attendance = array();
        $incomes = array();
        $salary_cuts = array();
        $total_incomes = 0;
        $total_salary_cuts = 0;
        foreach ($outlets as $vas) {
            $list_income = array();
            $list_salary_cut = array();
            $price_salary_cut = 0;
            $price_income = 0;
            $hair = $vas['id_user_hair_stylist'];
            $hairst = UserHairStylist::where('id_user_hair_stylist',$vas['id_user_hair_stylist'])->select('id_hairstylist_group')->first();
            $outl = $vas['id_outlet'];
            $outlet_name = Outlet::where('id_outlet',$vas['id_outlet'])->select('outlet_name')->first();
            $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                        ->whereNotNull('clock_in')
                        ->where('id_outlet',$outl)
                        ->where('id_user_hair_stylist',$hair)
                        ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
                        ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
                        ->selectRaw('count(*) as total')
                        ->first()['total']??'0';
            $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                            ->whereNotNull('clock_in')
                            ->where('is_on_time', 0)
                            ->where('id_outlet',$outl)
                            ->where('id_user_hair_stylist',$hair)
                            ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
                            ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
                            ->selectRaw('count(*) as total')
                            ->first()['total'] ?? '0';
            $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                            ->whereNull('clock_in')
                            ->where('id_outlet',$outl)
                            ->where('id_user_hair_stylist',$hair)
                            ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
                            ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
                            ->selectRaw('count(*) as total')
                            ->first()['total'] ?? '0';
            $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->whereNotNull('clock_in')
                                ->where('is_overtime',1)
                                ->where('id_outlet',$outl)
                                ->where('id_user_hair_stylist',$hair)
                                ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
                                ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
                                ->select(DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as dates'))
                                ->get()?? '0';
            $list_attendance[] = array(
                'header_title' => "Outlet",
                'header_content' => $outlet_name->outlet_name,
                'footer_title' => null,
                'footer_content' => null,
                'contents'=>array(
                    array(
                       'title'=>"Hari Masuk",
                       'content' => $total_attend,
                    ),
                    array(
                       'title'=>"Total Terlambat",
                       'content' => $total_late,
                    ),
                    array(
                       'title'=>"Tidak Masuk",
                       'content' => $total_absen,
                    ),
                    array(
                       'title'=>"Tambahan Jam",
                       'content' => $total_overtimes,
                    ),
                )
            ); 
            foreach ($call as $value) {
                $start_date = $value['start_date'];
                $end_date = $value['end_date'];
                $calculation = $value['calculation'];
                $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                        ->whereNotNull('clock_in')
                        ->where('id_outlet',$outl)
                        ->where('id_user_hair_stylist',$hair)
                        ->whereDate('hairstylist_attendances.attendance_date', '>=', $start_date)
                        ->whereDate('hairstylist_attendances.attendance_date', '<=', $end_date)
                        ->selectRaw('count(*) as total')
                        ->first()['total']??'0';
            $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                            ->whereNotNull('clock_in')
                            ->where('is_on_time', 0)
                            ->where('id_outlet',$outl)
                            ->where('id_user_hair_stylist',$hair)
                            ->whereDate('hairstylist_attendances.attendance_date', '>=', $start_date)
                            ->whereDate('hairstylist_attendances.attendance_date', '<=', $end_date)
                            ->selectRaw('count(*) as total')
                            ->first()['total'] ?? '0';
            $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                            ->whereNull('clock_in')
                            ->where('id_outlet',$outl)
                            ->where('id_user_hair_stylist',$hair)
                            ->whereDate('hairstylist_attendances.attendance_date', '>=', $start_date)
                            ->whereDate('hairstylist_attendances.attendance_date', '<=', $end_date)
                            ->selectRaw('count(*) as total')
                            ->first()['total'] ?? '0';
            $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->whereNotNull('clock_in')
                                ->where('is_overtime',1)
                                ->where('id_outlet',$outl)
                                ->where('id_user_hair_stylist',$hair)
                                ->whereDate('hairstylist_attendances.attendance_date', '>=', $start_date)
                                ->whereDate('hairstylist_attendances.attendance_date', '<=', $end_date)
                                ->select(DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as dates'))
                                ->get()?? '0';
                if ($calculation == 'product_commission') {
                    $mulai = $start_date;
                    $pc_mid = 0;
                    $pc_end = 0;
                    $product_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
                     foreach ($product_mid as $c) {
                         if ($c == 'product_commission') {
                             $pc_mid = 1;
                         }
                     }
                    $product_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
                    foreach ($product_end as $c) {
                         if ($c == 'product_commission') {
                             $pc_end = 1;
                         }
                     }
                      if ($type == 'middle') {
                          if($pc_end==0){
                           $mulai = $start_date = date('Y-m-d', strtotime($start_date . '+1 days'.'-1 months'));
                          } 
                        } else {
                           if($pc_mid==0){
                            $mulai = $start_date = date('Y-m-d', strtotime($start_date . '+1 days'.'-1 months'));
                          }  
                        }
                   $trxs = TransactionProduct::where(array('transaction_product_services.id_user_hair_stylist' => $vas['id_user_hair_stylist']))
                        ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
                        ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                        ->join('transaction_breakdowns', function ($join) use ($mulai, $end_date) {
                            $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                                ->whereNotNull('transaction_products.transaction_product_completed_at')
                                ->whereBetween('transaction_product_completed_at', [$mulai, $end_date]);
                        })
                        ->where('transactions.id_outlet', $vas['id_outlet'])
                        ->where('transaction_product_services.service_status', 'Completed')
                        ->wherenotnull('transaction_product_services.completed_at')
                        ->where('transaction_breakdowns.type', 'fee_hs')
                        ->select('transaction_products.id_transaction', 'transaction_products.id_transaction_product', 'transaction_breakdowns.*')
                        ->get();
                    $amount = 0;
                    foreach ($trxs as $item) {
                        $amount += $item->value;
                    }
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => $calculation,
                        ],
                            [
                                'id_outlet'    => $vas['id_outlet'],
                                'amount'    => $amount,
                                'type'        => "Incentive",
                                'name_income' => 'Product Comission',
                                'value_detail'=>json_encode(array(
                                    'start_date'=>$mulai,
                                    'start_date'=>$end_date,
                                    'total_attend'   => $total_attend,
                                    'total_late'     => $total_late,
                                    'total_absen'    => $total_absen,
                                    'total_overtime' => $total_overtime,
                                    'data'=>$trxs
                                )),
                            ]);
                    $list_income[] = array(
                        'list'=>"Product Commission",
                        'content'=>$amount
                    );
                    $total_incomes = $total_incomes + $amount;
                    $price_income = $price_income + $amount;
                    $total = $total + $amount;
                } elseif (strpos($calculation, 'incentive_') === 0) {
                    // start_with_calculation
                    $code      = str_replace('incentive_', '', $calculation);
                    $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function ($join) use ($hairst) {
                        $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                            ->where('id_hairstylist_group', $hairst->id_hairstylist_group);
                    })->where('hairstylist_group_default_insentifs.code', $code)
                        ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.code',
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
                    $formula = str_replace('value', $incentive->value, $incentive->formula);
                    $amount     = 0;
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

                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => $calculation,
                            'reference' => $incentive->id_hairstylist_group_default_insentifs,
                        ],
                            [
                                'id_outlet' => $outl,
                                'amount'    => $amount,
                                'type'        => "Incentive",
                                'name_income' => ucfirst(str_replace('_', ' ', $code)),
                                'value_detail'=> json_encode(array(
                                    'start_date'=>$mulai,
                                    'start_date'=>$end_date,
                                    'total_attend'   => $total_attend,
                                    'total_late'     => $total_late,
                                    'total_absen'    => $total_absen,
                                    'total_overtime' => $total_overtime,
                                    'data'=>json_encode($incentive)
                                )),
                            ]);
                        $list_income[] = array(
                                'list'=>ucfirst(str_replace('_', ' ', $code)),
                                'content'=>$amount
                            );
                        $total_incomes = $total_incomes + $amount;
                        $price_income = $price_income + $amount;
                    $total = $total + $amount;
                } elseif (strpos($calculation, 'salary_cut_') === 0) {
                    // start_with_calculation
                    $code       = str_replace('salary_cut_', '', $calculation);
                    $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function ($join) use ($hairst) {
                        $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                            ->where('id_hairstylist_group', $hairst->id_hairstylist_group);
                    })->where('hairstylist_group_default_potongans.code', $code)
                        ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.code',
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

                    $formula    = str_replace('value', $salary_cut->value, $salary_cut->formula);
                    $amount     = 0;
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

                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => $calculation,
                            'reference' => $salary_cut->id_hairstylist_group_default_potongans,
                        ],
                            [
                                'id_outlet' => $outl,
                                'amount'    => $amount,
                                'type'        => "Salary Cut",
                                'name_income' => ucfirst(str_replace('_', ' ', $code)),
                                'value_detail'=> json_encode(array(
                                    'start_date'=>$mulai,
                                    'start_date'=>$end_date,
                                    'total_attend'   => $total_attend,
                                    'total_late'     => $total_late,
                                    'total_absen'    => $total_absen,
                                    'total_overtime' => $total_overtime,
                                    'data'=>json_encode($salary_cut)
                                )),
                            ]);
                        $list_salary_cut[] = array(
                                'list'=>ucfirst(str_replace('_', ' ', $code)),
                                'content'=>$amount
                            );
                    $total_salary_cuts = $total_salary_cuts + $amount;
                    $price_salary_cut = $price_salary_cut + $amount;
                    $total = $total - $amount;
                }
            }
            if ($type == 'end') {
           $startDate = date('Y-m-d', strtotime("$year-" . ($month - 1) . "-$date +1 days"));
           $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hair, $outl) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hair)
                    ->where('id_outlet', $outl);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->select('date')
                ->get();
            foreach ($total_overtimes as $value) {
                array_push($overtime, $value);
            }
                $over = 0;
                $ove  = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hair)
                        ->wherenotnull('approve_at')
                        ->wherenull('reject_at')
                        ->where('not_schedule',0)
                        ->wheredate('date', $value['date'])
                        ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove, array(
                            'duration'                => $va['duration'],
                            'id_outlet'               => $va['id_outlet'],
                            'id_hairstylist_overtime' => $va['id_hairstylist_overtime'],
                        ));
                    }
                }
                $to_overtime = 0;
                foreach ($ove as $value) {
                        $va        = explode(":", $value['duration']);
                        $nominal   = 0;
                        $h         = $va[0];
                        $id_hairstylist_group_default_overtimes = 0;
                        $incentive = HairstylistGroupOvertimeDefault::leftJoin('hairstylist_group_overtimes', function ($join) use ($hairst) {
                            $join->on('hairstylist_group_overtimes.id_hairstylist_group_default_overtimes', 'hairstylist_group_default_overtimes.id_hairstylist_group_default_overtimes')
                                ->where('id_hairstylist_group', $hairst->id_hairstylist_group);
                        })
                            ->select('hairstylist_group_default_overtimes.id_hairstylist_group_default_overtimes','hairstylist_group_default_overtimes.hours',
                                DB::raw('
                                                   CASE WHEN
                                                   hairstylist_group_overtimes.value IS NOT NULL THEN hairstylist_group_overtimes.value ELSE hairstylist_group_default_overtimes.value
                                                   END as value
                                                '),
                            )->orderby('hours', 'DESC')->get();
                        foreach ($incentive as $valu) {
                            if ($valu['hours'] <= (int) $h) {
                                $nominal = $valu['value'];
                                $id_hairstylist_group_default_overtimes = $valu['id_hairstylist_group_default_overtimes'];
                                break;
                            }
                            $nominal = $valu['value'];
                            $id_hairstylist_group_default_overtimes = $valu['id_hairstylist_group_default_overtimes'];
                        }
                        if($id_hairstylist_group_default_overtimes){
                           $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Overtime",
                                'reference' => $id_hairstylist_group_default_overtimes,
                            ],
                                [
                                    'id_outlet' => $value['id_outlet'],
                                    'amount'    => $nominal,
                                ]); 
                        }
                        
                        $total = $total + $nominal;
                        $total_incomes = $total_incomes + $nominal;
                        $price_income = $price_income + $nominal;
                        $to_overtime = $to_overtime + $nominal;
                    }
                if($to_overtime>0){
                    $list_income[] = array(
                            'list'=>"Overtime",
                            'content'=>$amount
                        );
                }
                //Fixed Incentive
                if($hs->id_outlet == $outl&&count($incomeDefault)>0){
                $fixed = self::calculateFixedIncentive($hs, $startDate, $endDate,$outl,$incomeDefault);
                foreach ($fixed as $value) {
                    if ($value['status'] == 'incentive') {
                        $total = $total + $value['value'];
                        $total_incomes = $total_incomes + $value['value'];
                        $price_income = $price_income + $value['value'];
                        $typess = "Incentive";
                        $list_income[] = array(
                            'list'=>$value['name'],
                            'content'=> $value['value']
                        );
                    } else {
                        $total = $total - $value['value'];
                        $total_salary_cuts = $total_salary_cuts + $value['value'];
                        $price_salary_cut = $price_salary_cut + $value['value'];
                        $typess = "Salary Cut";
                        $list_salary_cut[] = array(
                            'list'=>$value['name'],
                            'content'=> $value['value']
                        );
                    }
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Fixed Incentive",
                        'reference' => $value['id_hairstylist_group_default_fixed_incentive'],
                    ],
                        [
                            'id_outlet' => $value['id_outlet'],
                            'amount'    => $value['value'],
                            'type'        => $typess,
                            'name_income' => $value['name'],
                            'value_detail'=> json_encode($value),
                        ]);
                    }
                }       
                //Proteksi Attendance
              if($hs->id_outlet == $outl){
             $proteksi = self::calculateGenerateIncomeProteksi($hs, $startDate, $endDate);
                foreach ($proteksi['proteksi'] as $value) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Proteksi Attendace",
                                'reference' => $value['id_hairstylist_group_default_proteksi_attendance'],
                            ],
                            [
                                'id_outlet'   => $outlet,
                                'amount'      => $value['value'],
                                'type'        => "Incentive",
                                'name_income' => "Proteksi Attendance",
                                'value_detail'=> json_encode($value),
                            ]);
                    $total_incomes = $total_incomes + $value['value']??0;
                    $price_income = $price_income + $value['value']??0;
                    $total = $total + $value['value']??0;
                    $list_income[] = array(
                            'list'=>'Proteksi Attendance',
                            'content'=> $value['value']
                        );
                    } 
                foreach ($proteksi['overtime'] as $value) {
                    $value;
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Overtime Not Schedule",
                                'reference' => $value['id'],
                            ],
                            [
                                'id_outlet'   => $outlet,
                                'amount'      => $value['value'],
                                'type'        => "Incentive",
                                'name_income' => "Overtime Not Schedule",
                                'value_detail'=> json_encode($value),
                            ]);
                    $total_incomes = $total_incomes + $value['value']['value']??0;
                    $price_income = $price_income + $value['value']['value']??0;
                    $total = $total + $value['value']['value']??0;
                    $list_income[] = array(
                            'list'=>"Overtime Not Schedule",
                            'content'=> $value['value']['value']??0
                        );
                    } 
                    
                } 
                  //Lateness
               $late = self::calculateGenerateIncomeLateness($hs, $startDate, $endDate, $outl);
                $price_late = 0;
                foreach ($late as $value) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Lateness Hairstylist",
                        'reference' => $value['id_hairstylist_group_default_late'],
                    ],
                    [
                        'id_outlet'   => $outl,
                        'amount'      => $value['value'],
                        'type'        => "Salary Cut",
                        'name_income' => "Lateness Hairstylist",
                        'value_detail'=> json_encode($value),
                    ]);
                    $price_late = $price_late + $value['value'];
                    $total = $total - $value['value'];
                } 
                if($price_late){
                    $list_salary_cut[] = array(
                                'list'=>'Keterlambatan',
                                'content'=>$price_late
                            );
                    $total_salary_cuts = $total_salary_cuts + $price_late;
                    $price_salary_cut = $price_salary_cut + $price_late;
                };
                    
        
                //loan
                $loan = self::calculateLoan($hs, $startDate, $endDate);
                foreach ($loan as $value) {
                    $total = $total - $value['value'];
                    $total_salary_cuts = $total_salary_cuts + $value['value'];
                    $price_salary_cut = $price_salary_cut + $value['value'];
                    if ($total >= 0) {
                        if(isset($value['type'])){
                        $icount = Icount::SalesPayment($value, $value['company'], null, null);
                        if($icount['response']['Status']=='1' && $icount['response']['Message']=='success'){
                            $icount = $icount['response']['Data'][0];
                            $loanicount = HairstylistLoanIcount::create([
                             'SalesPaymentID'=> $icount['SalesPaymentID'],
                             'SalesInvoiceID'=> $value['SalesInvoiceID'],
                             'BusinessPartnerID'=> $icount['BusinessPartnerID'],
                             'CompanyID'=> $icount['CompanyID'],
                             'BranchID'=> $icount['BranchID'],
                             'VoucherNo'=> $icount['VoucherNo'],
                             'id_hairstylist_loan_return'=> $value['id_hairstylist_loan_return'],
                             'value_detail'=> json_encode($icount),
                         ]);
                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => "Hairstylist Loan",
                            'reference' => $value['id_hairstylist_loan_return'],
                        ],
                            [
                                'id_outlet' => $value['id_outlet'],
                                'amount'    => $value['value'],
                                'type'        => "Salary Cut",
                                'name_income' => $value['name'],
                                'value_detail'=> json_encode($value),
                            ]);
                        $list_salary_cut[] = array(
                                'list'=>$value['name'],
                                'content'=>$value['value']
                            );
                        $total_salary_cuts = $total_salary_cuts + $value['value'];
                        $price_salary_cut = $price_salary_cut + $value['value'];
                        $loan_return = HairstylistLoanReturn::where('id_hairstylist_loan_return', $value['id_hairstylist_loan_return'])
                            ->update([
                                'status_return' => "Success",
                                'date_pay'      => date('Y-m-d H:i:s'),
                            ]);
                        }else{
                            $total = $total + $value['value'];
                            $total_salary_cuts = $total_salary_cuts - $value['value'];
                            $price_salary_cut = $price_salary_cut - $value['value'];
                        }
                        }else{
                            $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Hairstylist Loan",
                                'reference' => $value['id_hairstylist_loan_return'],
                            ],
                                [
                                    'id_outlet' => $value['id_outlet'],
                                    'amount'    => $value['value'],
                                    'type'        => "Salary Cut",
                                    'name_income' => $value['name'],
                                    'value_detail'=> json_encode($value),
                                ]);
                            $loan_return = HairstylistLoanReturn::where('id_hairstylist_loan_return', $value['id_hairstylist_loan_return'])
                                ->update([
                                    'status_return' => "Success",
                                    'date_pay'      => date('Y-m-d H:i:s'),
                                ]);
                            $list_salary_cut[] = array(
                                'list'=>$value['name'],
                                'content'=>$value['value']
                            );
                            
                        }
                    } else {
                        $total = $total + $value['value'];
                        $total_salary_cuts = $total_salary_cuts - $value['value'];
                        $price_salary_cut = $price_salary_cut - $value['value'];
                        break;
                    }
                }
                }
              
            $incomes[] = array(
                'header_title' => "Outlet",
                'header_content' => $outlet_name->outlet_name,
                'footer_title' => "Total",
                'footer_content' => $price_income,
                'contents'=>$list_income
            ); 
            $salary_cuts[] = array(
                'header_title' => "Outlet",
                'header_content' => $outlet_name->outlet_name,
                'footer_title' => "Total",
                'footer_content' => $price_salary_cut,
                'contents'=>$list_salary_cut
            ); 
        }
        if ($type == 'middle') {
        $response_income = array(
            'name' => 'Tengah Bulan',
            'icon' => 'half',
            'footer' => array(
                'title_title' => 'Penerimaan Tengah Bulan',
                'title_content' => $total_incomes,
                'subtitle_title' => 'Ditransfer',
                'subtitle_content' => date('d M Y', strtotime("$year-$month-$date")),
            ),
            'list'=>$incomes
            );
        $response_salary_cut = array(
            'name' => 'Tengah Bulan',
            'icon' => 'half',
            'footer' => array(
                'title_title' => 'Total Potongan',
                'title_content' => $total_salary_cuts,
                'subtitle_title' => null,
                'subtitle_content' => null,
            ),
            'list'=>$salary_cuts
            );
        $attendances = array(
            'name' => 'Tengah Bulan',
            'icon' => 'half',
            'footer' => null,
            'list'=>$list_attendance
            );
        }else{
         $attendances = array(
            'name' => 'Akhir Bulan',
            'icon' => 'end',
            'footer' => null,
            'list'=>$list_attendance
            );   
         $response_income = array(
            'name' => 'Akhir Bulan',
            'icon' => 'end',
            'footer' => array(
                'title_title' => 'Penerimaan Akhir Bulan',
                'title_content' => $total_incomes,
                'subtitle_title' => 'Ditransfer',
                'subtitle_content' => date('d M Y', strtotime("$year-$month-$date")),
            ),
            'list'=>$incomes
            );
         $response_salary_cut = array(
            'name' => 'Akhir Bulan',
            'icon' => 'end',
            'footer' => array(
                'title_title' => 'Total Potongan',
                'title_content' => $total_salary_cuts,
                'subtitle_title' => null,
                'subtitle_content' => null,
            ),
            'list'=>$salary_cuts
            );
        }
        $hairstylist_bank = UserHairStylist::leftjoin('bank_accounts','bank_accounts.id_bank_account','user_hair_stylist.id_bank_account')
                ->leftjoin('bank_name','bank_name.id_bank_name','bank_accounts.id_bank_name')
                ->select(
                        'id_user_hair_stylist',
                        'id_outlet',
                        'id_hairstylist_group',
                        'bank_accounts.id_bank_account',
                        'beneficiary_name',
                        'beneficiary_account',
                        'bank_name'
                        )
                ->where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                ->first();
        $footer_title = 'Total diterima bulan ini setelah potongan';
        if ($type == 'end') {
         $proteksion = self::calculateGenerateIncomeProtec($hs, $startDate, $endDate);
          if($total< $proteksion['total_income']??0){
                $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Proteksi",
                        'reference' => $proteksion['name'],
                    ],
                    [
                        'id_outlet'   => $hs->id_outlet,
                        'amount'      => $proteksion['total_income'],
                        'type'        => "Incentive",
                        'name_income' => $proteksion['name'],
                        'value_detail'=> json_encode($proteksion),
                    ]);
                    $total = $proteksion['total_income'];
                    $footer_title = 'Total diterima bulan ini mendapat '.$proteksion['name'];
            }
        }
      $response = array(
            'month' => date('Y-m-d', strtotime("$year-$month-$date")),
            'type' => $type,
            'bank_name' => $hairstylist_bank->bank_name??null,
            'account_number' => $hairstylist_bank->beneficiary_account??null,
            'account_name' => $hairstylist_bank->beneficiary_name??null,
            'footer' => array(
                'footer_title' => $footer_title,
                'footer_content' => $total,
            ),
            'incomes'=>$response_income,
            'attendances'=>$attendances,
            'salary_cuts'=>$response_salary_cut,
        );
       $hsIncome->update([
            'status' => 'Pending',
            'amount' => $total,
            'value_detail'=> json_encode($response)
        ]);
        return $hsIncome;
    }
    public static function calculateIncome2(UserHairStylist $hs, $type = 'end')
    {
        $total = 0;
        if ($type == 'middle') {
            $date         = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value');
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        } else {
            $type         = 'end';
            $date         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value');
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
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
        $jadwal = HairstylistSchedule::where(array(
            'id_user_hair_stylist'=>$hs->id_user_hair_stylist,
            'schedule_month'=>$month,
            'schedule_year'=>$year
        ))->first();

        $hsIncome = static::updateOrCreate([
            'id_user_hair_stylist' => $hs->id_user_hair_stylist,
            'type'                 => $type,
            'periode'              => date('Y-m-d', strtotime("$year-$month-$date")),
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
        $incomeDefault = HairstylistGroupFixedIncentiveDefault::with(['detail'])->get();
        $total_attend   = 0;
        $total_late     = 0;
        $total_absen    = 0;
        $total_overtime = 0;
        $overtime       = array();
        $outlet         = Outlet::where('id_outlet', $hs->id_outlet)->first();
        $id_outlets     = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');

        $all_attends = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) {
                return $item->keyBy('id_outlet');
            });

       $all_lates = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->where('is_on_time', 0)
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) {
                return $item->keyBy('id_outlet');
            });

        $all_absens = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) {
                return $item->keyBy('id_outlet');
            });

        $all_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->where('is_overtime',1)
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
            ->select('date','id_outlet', 'id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) {
                return $item->groupBy('id_outlet');
            });
        
        $minOvertimeMinutes = MyHelper::setting('overtime_hs', 'value', 45);
        $overtimes = HairstylistOverTime::wherenotnull('approve_at')
            ->wherenull('reject_at')
            ->where('not_schedule',0)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->select('duration', 'id_user_hair_stylist', 'id_outlet', \DB::raw('DATE(date) as datex'))
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) use ($minOvertimeMinutes) {
                $newItem = $item->groupBy('id_outlet');
                return $newItem->map(function($item2) use ($minOvertimeMinutes) {
                    $newItem2 = $item2->groupBy('datex');
                    return $newItem2->map(function ($item3) use ($minOvertimeMinutes) {
                        $duration = 0; // in second
                        foreach ($item3 as $i) {
                            $duration += strtotime($i['duration']) - strtotime('00:00:00');
                        }
                        $sisa = $duration % 3600; // sisa detik
                        $overtime = floor($duration / 3600); // jam overtime awal
                        $overtime += ($sisa / 60) >= $minOvertimeMinutes ? 1 : 0;
                        return (int) $overtime;
                    });
                });
            });
        $outlets = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
           ->whereDate('hairstylist_schedule_dates.date', '>=', $startDate)
            ->whereDate('hairstylist_schedule_dates.date', '<=', $endDate)
            ->selectRaw('id_outlet, id_user_hair_stylist')
            ->where('hairstylist_schedules.id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get();
        $list_attendance = array();
        $incomes = array();
        $salary_cuts = array();
        $total_incomes = 0;
        $total_salary_cuts = 0;
        foreach ($outlets as $vas) {
            $list_income = array();
            $list_salary_cut = array();
            $price_salary_cut = 0;
            $price_income = 0;
            $hair = $vas['id_user_hair_stylist'];
            $hairst = UserHairStylist::where('id_user_hair_stylist',$vas['id_user_hair_stylist'])->select('id_hairstylist_group')->first();
            $outl = $vas['id_outlet'];
           $outlet_name = Outlet::where('id_outlet',$vas['id_outlet'])->select('outlet_name')->first();
            $total_attend = $all_attends[$hair][$outl]['total'] ?? '0';
            $total_late = $all_lates[$hair][$outl]['total'] ?? '0';
            $total_absen = $all_absens[$hair][$outl]['total'] ?? '0';
            $total_overtimes = $all_overtimes[$hair][$outl] ?? '0';
            $list_attendance[] = array(
                'header_title' => "Outlet",
                'header_content' => $outlet_name->outlet_name,
                'footer_title' => null,
                'footer_content' => null,
                'contents'=>array(
                    array(
                       'title'=>"Hari Masuk",
                       'content' => $total_attend,
                    ),
                    array(
                       'title'=>"Total Terlambat",
                       'content' => $total_late,
                    ),
                    array(
                       'title'=>"Tidak Masuk",
                       'content' => $total_absen,
                    ),
                    array(
                       'title'=>"Tambahan Jam",
                       'content' => $total_overtimes,
                    ),
                )
            ); 
            foreach ($calculations as $calculation) {
                if ($calculation == 'product_commission') {
                    $mulai = $startDate;
                    $pc_mid = 0;
                    $pc_end = 0;
                    $product_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
                     foreach ($product_mid as $c) {
                         if ($c == 'product_commission') {
                             $pc_mid = 1;
                         }
                     }
                    $product_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
                    foreach ($product_end as $c) {
                         if ($c == 'product_commission') {
                             $pc_end = 1;
                         }
                     }
                      if ($type == 'middle') {
                          if($pc_end==0){
                           $mulai = $startDate = date('Y-m-d', strtotime($startDate . '+1 days'.'-1 months'));
                          } 
                        } else {
                           if($pc_mid==0){
                            $mulai = $startDate = date('Y-m-d', strtotime($startDate . '+1 days'.'-1 months'));
                          }  
                        }
                   $trxs = TransactionProduct::where(array('transaction_product_services.id_user_hair_stylist' => $vas['id_user_hair_stylist']))
                        ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
                        ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                        ->join('transaction_breakdowns', function ($join) use ($mulai, $endDate) {
                            $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                                ->whereNotNull('transaction_products.transaction_product_completed_at')
                                ->whereBetween('transaction_product_completed_at', [$mulai, $endDate]);
                        })
                        ->where('transactions.id_outlet', $vas['id_outlet'])
                        ->where('transaction_product_services.service_status', 'Completed')
                        ->wherenotnull('transaction_product_services.completed_at')
                        ->where('transaction_breakdowns.type', 'fee_hs')
                        ->select('transaction_products.id_transaction', 'transaction_products.id_transaction_product', 'transaction_breakdowns.*')
                        ->get();
                    $amount = 0;
                    foreach ($trxs as $item) {
                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => $calculation,
                            'reference' => $item->id_transaction_product,
                        ],
                            [
                                'id_outlet' => $item->transaction->id_outlet,
                                'amount'    => $item->value,
                                'type'        => "Incentive",
                                'name_income' => 'Product Comission',
                                'value_detail'=>json_encode(array(
                                    'start_date'=>$mulai,
                                    'start_date'=>$endDate,
                                    'data'=>$item
                                )),
                            ]);
                        
                        $amount += $item->value;
                    }
                    $list_income[] = array(
                        'list'=>"Product Commission",
                        'content'=>$amount
                    );
                    $total_incomes = $total_incomes + $amount;
                    $price_income = $price_income + $amount;
                    $total = $total + $amount;
                } elseif (strpos($calculation, 'incentive_') === 0) {
                    // start_with_calculation
                    $code      = str_replace('incentive_', '', $calculation);
                    $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function ($join) use ($hairst) {
                        $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                            ->where('id_hairstylist_group', $hairst->id_hairstylist_group);
                    })->where('hairstylist_group_default_insentifs.code', $code)
                        ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.code',
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
                    $formula = str_replace('value', $incentive->value, $incentive->formula);
                    $amount     = 0;
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

                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => $calculation,
                            'reference' => $incentive->id_hairstylist_group_default_insentifs,
                        ],
                            [
                                'id_outlet' => $outl,
                                'amount'    => $amount,
                                'type'        => "Incentive",
                                'name_income' => ucfirst(str_replace('_', ' ', $code)),
                                'value_detail'=> json_encode($incentive),
                            ]);
                        $list_income[] = array(
                                'list'=>ucfirst(str_replace('_', ' ', $code)),
                                'content'=>$amount
                            );
                        $total_incomes = $total_incomes + $amount;
                        $price_income = $price_income + $amount;
                    $total = $total + $amount;
                } elseif (strpos($calculation, 'salary_cut_') === 0) {
                    // start_with_calculation
                    $code       = str_replace('salary_cut_', '', $calculation);
                    $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function ($join) use ($hairst) {
                        $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                            ->where('id_hairstylist_group', $hairst->id_hairstylist_group);
                    })->where('hairstylist_group_default_potongans.code', $code)
                        ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.code',
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

                    $formula    = str_replace('value', $salary_cut->value, $salary_cut->formula);
                    $amount     = 0;
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

                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => $calculation,
                            'reference' => $salary_cut->id_hairstylist_group_default_potongans,
                        ],
                            [
                                'id_outlet' => $outl,
                                'amount'    => $amount,
                                'type'        => "Salary Cut",
                                'name_income' => ucfirst(str_replace('_', ' ', $code)),
                                'value_detail'=> json_encode($salary_cut),
                            ]);
                        $list_salary_cut[] = array(
                                'list'=>ucfirst(str_replace('_', ' ', $code)),
                                'content'=>$amount
                            );
                    $total_salary_cuts = $total_salary_cuts + $amount;
                    $price_salary_cut = $price_salary_cut + $amount;
                    $total = $total - $amount;
                }
            }
            
            if ($type == 'end') {
           $startDate = date('Y-m-d', strtotime("$year-" . ($month - 1) . "-$date +1 days"));
            $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hair, $outl) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hair)
                    ->where('id_outlet', $outl);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->select('date')
                ->get();
            foreach ($total_overtimes as $value) {
                array_push($overtime, $value);
            }
                $over = 0;
                $ove  = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hair)
                        ->wherenotnull('approve_at')
                        ->wherenull('reject_at')
                        ->where('not_schedule',0)
                        ->wheredate('date', $value['date'])
                        ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove, array(
                            'duration'                => $va['duration'],
                            'id_outlet'               => $va['id_outlet'],
                            'id_hairstylist_overtime' => $va['id_hairstylist_overtime'],
                        ));
                    }
                }
                $to_overtime = 0;
                foreach ($ove as $value) {
                        $va        = explode(":", $value['duration']);
                        $nominal   = 0;
                        $h         = $va[0];
                        $incentive = HairstylistGroupOvertimeDefault::leftJoin('hairstylist_group_overtimes', function ($join) use ($hairst) {
                            $join->on('hairstylist_group_overtimes.id_hairstylist_group_default_overtimes', 'hairstylist_group_default_overtimes.id_hairstylist_group_default_overtimes')
                                ->where('id_hairstylist_group', $hairst->id_hairstylist_group);
                        })
                            ->select('hairstylist_group_default_overtimes.id_hairstylist_group_default_overtimes','hairstylist_group_default_overtimes.hours',
                                DB::raw('
                                                   CASE WHEN
                                                   hairstylist_group_overtimes.value IS NOT NULL THEN hairstylist_group_overtimes.value ELSE hairstylist_group_default_overtimes.value
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
                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => "Overtime",
                            'reference' => $value['id_hairstylist_group_default_overtimes'],
                        ],
                            [
                                'id_outlet' => $value['id_outlet'],
                                'amount'    => $nominal,
                            ]);
                        $total = $total + $nominal;
                        $total_incomes = $total_incomes + $nominal;
                        $price_income = $price_income + $nominal;
                        $to_overtime = $to_overtime + $nominal;
                    }
                if($to_overtime>0){
                    $list_income[] = array(
                            'list'=>"Overtime",
                            'content'=>$amount
                        );
                }
                //Fixed Incentive
                if($hs->id_outlet == $outl){
                $fixed = self::calculateFixedIncentive($hs, $startDate, $endDate,$incomeDefault);
                foreach ($fixed as $value) {
                    if ($value['status'] == 'incentive') {
                        $total = $total + $value['value'];
                        $total_incomes = $total_incomes + $value['value'];
                        $price_income = $price_income + $value['value'];
                        $typess = "Incentive";
                        $list_income[] = array(
                            'list'=>$value['name'],
                            'content'=> $value['value']
                        );
                    } else {
                        $total = $total - $value['value'];
                        $total_salary_cuts = $total_salary_cuts + $value['value'];
                        $price_salary_cut = $price_salary_cut + $value['value'];
                        $typess = "Salary Cut";
                        $list_salary_cut[] = array(
                            'list'=>$value['name'],
                            'content'=> $value['value']
                        );
                    }
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Fixed Incentive",
                        'reference' => $value['id_hairstylist_group_default_fixed_incentive'],
                    ],
                        [
                            'id_outlet' => $value['id_outlet'],
                            'amount'    => $value['value'],
                            'type'        => $typess,
                            'name_income' => $value['name'],
                            'value_detail'=> json_encode($value),
                        ]);
                    }        
                }       
               
                //Proteksi Attendance
              if($hs->id_outlet == $outl){
              $proteksi = self::calculateGenerateIncomeProteksi($hs, $startDate, $endDate);
                foreach ($proteksi['proteksi'] as $value) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Proteksi Attendace",
                                'reference' => $value['id_hairstylist_group_default_proteksi_attendance'],
                            ],
                            [
                                'id_outlet'   => $outlet,
                                'amount'      => $value['value'],
                                'type'        => "Incentive",
                                'name_income' => "Proteksi Attendance",
                                'value_detail'=> json_encode($value),
                            ]);
                    $total_incomes = $total_incomes + $value['value'];
                    $price_income = $price_income + $value['value'];
                    $total = $total + $value['value'];
                    $list_income[] = array(
                            'list'=>'Proteksi Attendance',
                            'content'=> $value['value']
                        );
                    } 
                foreach ($proteksi['overtime'] as $value) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Overtime Not Schedule",
                                'reference' => $value['"Overtime Not Schedule",'],
                            ],
                            [
                                'id_outlet'   => $outlet,
                                'amount'      => $value['value'],
                                'type'        => "Incentive",
                                'name_income' => "Overtime Not Schedule",
                                'value_detail'=> json_encode($value),
                            ]);
                    $total_incomes = $total_incomes + $value['value'];
                    $price_income = $price_income + $value['value'];
                    $total = $total + $value['value'];
                    $list_income[] = array(
                            'list'=>"Overtime Not Schedule",
                            'content'=> $value['value']
                        );
                    } 
                } 
            
                

                  //Lateness
               $late = self::calculateGenerateIncomeLateness($hs, $startDate, $endDate, $outl);
                $price_late = 0;
                foreach ($late as $value) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Lateness Hairstylist",
                        'reference' => $value['id_hairstylist_group_default_late'],
                    ],
                    [
                        'id_outlet'   => $outl,
                        'amount'      => $value['value'],
                        'type'        => "Salary Cut",
                        'name_income' => "Lateness Hairstylist",
                        'value_detail'=> json_encode($value),
                    ]);
                    $price_late = $price_late + $value['value'];
                    $total = $total - $value['value'];
                } 
                if($price_late){
                    $list_salary_cut[] = array(
                                'list'=>'Keterlambatan',
                                'content'=>$price_late
                            );
                    $total_salary_cuts = $total_salary_cuts + $price_late;
                    $price_salary_cut = $price_salary_cut + $price_late;
                };
                    
        
                //loan
                $loan = self::calculateLoan($hs, $startDate, $endDate);
                foreach ($loan as $value) {
                    $total = $total - $value['value'];
                    $total_salary_cuts = $total_salary_cuts + $value['value'];
                    $price_salary_cut = $price_salary_cut + $value['value'];
                    if ($total >= 0) {
                        if(isset($value['type'])){
                        $icount = Icount::SalesPayment($value, $value['company'], null, null);
                        if($icount['response']['Status']=='1' && $icount['response']['Message']=='success'){
                            $icount = $icount['response']['Data'][0];
                            $loanicount = HairstylistLoanIcount::create([
                             'SalesPaymentID'=> $icount['SalesPaymentID'],
                             'SalesInvoiceID'=> $value['SalesInvoiceID'],
                             'BusinessPartnerID'=> $icount['BusinessPartnerID'],
                             'CompanyID'=> $icount['CompanyID'],
                             'BranchID'=> $icount['BranchID'],
                             'VoucherNo'=> $icount['VoucherNo'],
                             'id_hairstylist_loan_return'=> $value['id_hairstylist_loan_return'],
                             'value_detail'=> json_encode($icount),
                         ]);
                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => "Hairstylist Loan",
                            'reference' => $value['id_hairstylist_loan_return'],
                        ],
                            [
                                'id_outlet' => $value['id_outlet'],
                                'amount'    => $value['value'],
                                'type'        => "Salary Cut",
                                'name_income' => $value['name'],
                                'value_detail'=> json_encode($value),
                            ]);
                        $list_salary_cut[] = array(
                                'list'=>$value['name'],
                                'content'=>$value['value']
                            );
                        $total_salary_cuts = $total_salary_cuts + $value['value'];
                        $price_salary_cut = $price_salary_cut + $value['value'];
                        $loan_return = HairstylistLoanReturn::where('id_hairstylist_loan_return', $value['id_hairstylist_loan_return'])
                            ->update([
                                'status_return' => "Success",
                                'date_pay'      => date('Y-m-d H:i:s'),
                            ]);
                        }else{
                            $total = $total + $value['value'];
                            $total_salary_cuts = $total_salary_cuts - $value['value'];
                            $price_salary_cut = $price_salary_cut - $value['value'];
                        }
                        }else{
                            $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Hairstylist Loan",
                                'reference' => $value['id_hairstylist_loan_return'],
                            ],
                                [
                                    'id_outlet' => $value['id_outlet'],
                                    'amount'    => $value['value'],
                                    'type'        => "Salary Cut",
                                    'name_income' => $value['name'],
                                    'value_detail'=> json_encode($value),
                                ]);
                            $loan_return = HairstylistLoanReturn::where('id_hairstylist_loan_return', $value['id_hairstylist_loan_return'])
                                ->update([
                                    'status_return' => "Success",
                                    'date_pay'      => date('Y-m-d H:i:s'),
                                ]);
                            $list_salary_cut[] = array(
                                'list'=>$value['name'],
                                'content'=>$value['value']
                            );
                            
                        }
                    } else {
                        $total = $total + $value['value'];
                        $total_salary_cuts = $total_salary_cuts - $value['value'];
                        $price_salary_cut = $price_salary_cut - $value['value'];
                        break;
                    }
                }
                }
              
            $incomes[] = array(
                'header_title' => "Outlet",
                'header_content' => $outlet_name->outlet_name,
                'footer_title' => "Total",
                'footer_content' => $price_income,
                'contents'=>$list_income
            ); 
            $salary_cuts[] = array(
                'header_title' => "Outlet",
                'header_content' => $outlet_name->outlet_name,
                'footer_title' => "Total",
                'footer_content' => $price_salary_cut,
                'contents'=>$list_salary_cut
            ); 
        }
        if ($type == 'middle') {
        $response_income = array(
            'name' => 'Tengah Bulan',
            'icon' => 'half',
            'footer' => array(
                'title_title' => 'Penerimaan Tengah Bulan',
                'title_content' => $total_incomes,
                'subtitle_title' => 'Ditransfer',
                'subtitle_content' => date('d M Y', strtotime("$year-$month-$date")),
            ),
            'list'=>$incomes
            );
        $response_salary_cut = array(
            'name' => 'Tengah Bulan',
            'icon' => 'half',
            'footer' => array(
                'title_title' => 'Total Potongan',
                'title_content' => $total_salary_cuts,
                'subtitle_title' => null,
                'subtitle_content' => null,
            ),
            'list'=>$salary_cuts
            );
        $attendances = array(
            'name' => 'Tengah Bulan',
            'icon' => 'half',
            'footer' => null,
            'list'=>$list_attendance
            );
        }else{
         $attendances = array(
            'name' => 'Akhir Bulan',
            'icon' => 'end',
            'footer' => null,
            'list'=>$list_attendance
            );   
         $response_income = array(
            'name' => 'Akhir Bulan',
            'icon' => 'end',
            'footer' => array(
                'title_title' => 'Penerimaan Akhir Bulan',
                'title_content' => $total_incomes,
                'subtitle_title' => 'Ditransfer',
                'subtitle_content' => date('d M Y', strtotime("$year-$month-$date")),
            ),
            'list'=>$incomes
            );
         $response_salary_cut = array(
            'name' => 'Akhir Bulan',
            'icon' => 'end',
            'footer' => array(
                'title_title' => 'Total Potongan',
                'title_content' => $total_salary_cuts,
                'subtitle_title' => null,
                'subtitle_content' => null,
            ),
            'list'=>$salary_cuts
            );
        }
        $hairstylist_bank = UserHairStylist::leftjoin('bank_accounts','bank_accounts.id_bank_account','user_hair_stylist.id_bank_account')
                ->leftjoin('bank_name','bank_name.id_bank_name','bank_accounts.id_bank_name')
                ->select(
                        'id_user_hair_stylist',
                        'id_outlet',
                        'id_hairstylist_group',
                        'bank_accounts.id_bank_account',
                        'beneficiary_name',
                        'beneficiary_account',
                        'bank_name'
                        )
                ->where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                ->first();
        $footer_title = 'Total diterima bulan ini setelah potongan';
        if ($type == 'end') {
          $proteksion = self::calculateGenerateIncomeProtec($hs, $startDate, $endDate);
            if($total<$proteksion['value']){
                $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Proteksi",
                        'reference' => $proteksion['name'],
                    ],
                    [
                        'id_outlet'   => $hs->id_outlet,
                        'amount'      => $proteksion['value'],
                        'type'        => "Incentive",
                        'name_income' => $proteksion['name'],
                        'value_detail'=> json_encode($proteksion),
                    ]);
                    $total = $proteksion['value'];
                    $footer_title = 'Total diterima bulan ini mendapat '.$proteksion['name'];
            }
        }
      $response = array(
            'month' => date('Y-m-d', strtotime("$year-$month-$date")),
            'type' => $type,
            'bank_name' => $hairstylist_bank->bank_name??null,
            'account_number' => $hairstylist_bank->beneficiary_account??null,
            'account_name' => $hairstylist_bank->beneficiary_name??null,
            'footer' => array(
                'footer_title' => $footer_title,
                'footer_content' => $total,
            ),
            'incomes'=>$response_income,
            'attendances'=>$attendances,
            'salary_cuts'=>$response_salary_cut,
        );
       $hsIncome->update([
            'status' => 'Pending',
            'amount' => $total,
            'value_detail'=> json_encode($response)
        ]);
        return $hsIncome;
    }
    public static function calculateIncomeExport(UserHairStylist $hs, $startDate, $endDate, $id_outlets, $all_attends, $all_lates, $all_absens, $all_overtimes)
    {
        $total           = 0;
        $array           = array();
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid, $calculation_end));
        $total_attend   = 0;
        $total_late     = 0;
        $total_absen    = 0;
        $total_overtime = 0;
        $overtime       = array();
        foreach ($id_outlets as $id_outlet) {
            $total_attend = $all_attends[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? 0;
            $total_late = $all_lates[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? 0;
            $total_absen = $all_absens[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? 0;
            $total_overtimes = $all_overtimes[$hs->id_user_hair_stylist][$id_outlet] ?? [];
            foreach ($total_overtimes as $value) {
                array_push($overtime, $value);
            }

        }
        $over = 0;
        $ove  = array();
        foreach (array_unique($overtime) as $value) {
            $overtimess = HairstylistOverTime::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
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

        foreach ($calculations as $calculation) {
            if (strpos($calculation, 'incentive_') === 0) {
                // start_with_calculation
                $code      = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function ($join) use ($hs) {
                    $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                })->where('hairstylist_group_default_insentifs.code', $code)
                    ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.name', 'hairstylist_group_default_insentifs.code',
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
                $formula    = str_replace('value', $incentive->value, $incentive->formula);
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
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
                    }
                }
                $total   = $total + $amount;
                $array[] = array(
                    "name"  => $incentive->name,
                    "value" => $amount,
                );
            } elseif (strpos($calculation, 'salary_cut_') === 0) {
                // start_with_calculation
                $code       = str_replace('salary_cut_', '', $calculation);
                $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function ($join) use ($hs) {
                    $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                })->where('hairstylist_group_default_potongans.code', $code)
                    ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.name', 'hairstylist_group_default_potongans.code',
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

                $formula    = str_replace('value', $salary_cut->value, $salary_cut->formula);
                $amount     = 0;
                foreach ($id_outlets as $id_outlet) {

                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend'    => $total_attend,
                            'total_late'      => $total_late,
                            'total_absen'     => $total_absen,
                            '$total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }

                }
                $total   = $total - $amount;
                $array[] = array(
                    "name"  => $salary_cut->name,
                    "value" => $amount,
                );
            }
        }
        return $array;
    }
    public static function calculateIncomeProductCode(UserHairStylist $hs, $startDate, $endDate)
    {
        $total = 0;
        $array = array();
//        $trxs  = TransactionProduct::where(array('transaction_product_services.id_user_hair_stylist' => $hs->id_user_hair_stylist))
//            ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
//            ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
//            ->join('transaction_breakdowns', function ($join) use ($startDate, $endDate) {
//                $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
//                    ->whereNotNull('transaction_products.transaction_product_completed_at')
//                    ->whereBetween('transaction_product_completed_at', [$startDate, $endDate]);
//            })
//            ->where('transaction_product_services.service_status', 'Completed')
//            ->wherenotnull('transaction_product_services.completed_at')
//            ->wherenull('transaction_products.reject_at')
//            ->wherenotnull('transaction_products.transaction_product_completed_at')
//            ->where('transaction_breakdowns.type', 'fee_hs')
//            ->select('transaction_breakdowns.value')
//            ->get();
        $trxs  = Transaction::where(array('transaction_products.id_user_hair_stylist' => $hs->id_user_hair_stylist))
            ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
            ->join('transaction_breakdowns', function ($join) use ($startDate, $endDate) {
                $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                    ->whereNotNull('transaction_products.transaction_product_completed_at')
                    ->whereBetween('transaction_product_completed_at', [$startDate, $endDate]);
            })
            ->wherenull('transaction_products.reject_at')
            ->wherenotnull('transaction_products.transaction_product_completed_at')
            ->where('transaction_breakdowns.type', 'fee_hs')
            ->select('transaction_breakdowns.value')
            ->get();
        foreach ($trxs as $value) {
            $total = $total + $value->value;
        }
        $array[] = array(
            "name"  => "Total commission",
            "value" => $total,
        );
        return $array;
    }
    public static function calculateTambahanJam(UserHairStylist $hs, $startDate, $endDate)
    {
        $total           = 0;
        $array           = array();
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid, $calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend   = 0;
        $total_late     = 0;
        $total_absen    = 0;
        $total_overtime = 0;
        $overtime       = array();
        $id_outlets     = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
            $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->count();
            $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->where('is_on_time', 0)
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->count();
            $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->count();
            $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->select('date')
                ->get();
            foreach ($total_overtimes as $value) {
                array_push($overtime, $value);
            }

        }
        $over = 0;
        $ove  = array();
        foreach (array_unique($overtime) as $value) {
            $overtimess = HairstylistOverTime::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                ->wherenotnull('approve_at')
                ->wherenull('reject_at')
                ->wheredate('date', $value['date'])
                ->select('duration')
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

        $array = array(
            array(
                "name"  => "tambahan jam",
                "value" => $total_overtime,
            ),

            array(
                "name"  => "potongan telat",
                "value" => $total_late,
            ),
        );
        return $array;
    }
    public static function calculateIncomeTotal(UserHairStylist $hs, $startDate, $endDate)
    {
        $total           = 0;
        $array           = array();
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid, $calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend   = 0;
        $total_late     = 0;
        $total_absen    = 0;
        $total_overtime = 0;
        $overtime       = array();
        $id_outlets     = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
            $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->count();
            $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->where('is_on_time', 0)
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->count();
            $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->count();
            $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->select('date')
                ->get();
            foreach ($total_overtimes as $value) {
                array_push($overtime, $value);
            }

        }
        $over = 0;
        $ove  = array();
        foreach (array_unique($overtime) as $value) {
            $overtimess = HairstylistOverTime::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                ->wherenotnull('approve_at')
                ->wherenull('reject_at')
                ->wheredate('date', $value['date'])
                ->select('duration')
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

        foreach ($calculations as $calculation) {
            if (strpos($calculation, 'incentive_') === 0) {
                // start_with_calculation
                $code      = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function ($join) use ($hs) {
                    $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                })->where('hairstylist_group_default_insentifs.code', $code)
                    ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.code',
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
                $formula    = str_replace('value', $incentive->value, $incentive->formula);
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
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
                    }
                }
                $total = $total + $amount;

            } elseif (strpos($calculation, 'salary_cut_') === 0) {
                // start_with_calculation
                $code       = str_replace('salary_cut_', '', $calculation);
                $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function ($join) use ($hs) {
                    $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                })->where('hairstylist_group_default_potongans.code', $code)
                    ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.code',
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

                $formula    = str_replace('value', $salary_cut->value, $salary_cut->formula);
                $amount     = 0;
                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                foreach ($id_outlets as $id_outlet) {

                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend'    => $total_attend,
                            'total_late'      => $total_late,
                            'total_absen'     => $total_absen,
                            '$total_overtime' => $total_overtime,
                        ]);
                    } catch (\Exception $e) {
                        $amount = 0;
                    }

                }
                $total = $total - $amount;

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
            if ($value['status'] == 'incentive') {
                $total = $total + $value['value'];
            } else {
                $total = $total - $value['value'];
            }

        }
        $loan = self::calculateSalaryCuts($hs, $startDate, $endDate);
        foreach ($loan as $va) {
            $total = $total - $value['value'];
        }
        $location = Outlet::where('id_outlet', $hs->id_outlet)->join('locations', 'locations.id_location', 'outlets.id_location')->first();
        $diff     = date_diff(date_create(date('Y-m-d')), date_create(date('Y-m-d', strtotime($location->start_date))));
        $proteksi = Setting::where('key', 'proteksi_hs')->first()['value_text'] ?? [];
        if ($proteksi) {
            $overtime = json_decode($proteksi, true);
        } else {
            $overtime = array(
                'range' => 0,
                'value' => 0,
            );
        }
        $group                     = HairstylistGroupProteksi::where(array('id_hairstylist_group' => $hs->id_hairstylist_group))->first();
        $overtime['default_value'] = 0;
        if (isset($group['value'])) {
            $overtime['value'] = $group['value'];
        }
        if ($diff->m >= $overtime['range']) {
            $keterangan = "Non Proteksi";
        } else {
            $keterangan = "Proteksi";
            $total      = $overtime['value'];
        }
        $array = array(
            array(
                "name"  => "Total imbal jasa",
                "value" => $total,

            ),
            array(
                "name"  => "Keterangan",
                "value" => $keterangan,

            ),
        );
        return $array;
    }
    public static function calculateIncomeGross(UserHairStylist $hs, $startDate, $endDate)
    {
        $total           = 0;
        $array           = array();
        $calculation_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
        $calculation_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
        $calculations    = array_unique(array_merge($calculation_mid, $calculation_end));
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
        $total_attend = 0;
        $overtime     = array();
        $id_outlets   = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        foreach ($id_outlets as $id_outlet) {
            $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->count();
        }
        $outlet_services = Transaction::where(array('transaction_product_services.id_user_hair_stylist' => $hs->id_user_hair_stylist))
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->where('transactions.transaction_payment_status', 'Completed')
            ->where('transactions.reject_at', null)
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
            "name"  => "hari masuk",
            "value" => $total_attend,
        );
        $array[] = array(
            "name"  => "total gross sale",
            "value" => $outlet_services->revenue ?? 0,
        );
        return $array;
    }
    public static function calculateIncomeOvertime(UserHairStylist $hs, $startDate, $endDate, $id_outlets, $all_overtimes)
    {
        $total          = 0;
        $array          = array();
        $total_attend   = 0;
        $total_late     = 0;
        $total_absen    = 0;
        $total_overtime = 0;
        $overtime       = array();
        $set_over       = Setting::where('key', 'overtime_hs')->first()['value'] ?? 0;
        foreach ($id_outlets as $id_outlet) {
            $total_overtimes = $all_overtimes[$hs->id_user_hair_stylist][$id_outlet] ?? [];
            foreach ($total_overtimes as $value) {
                array_push($overtime, $value);
            }

        }
        $over = 0;
        $ove  = array();
        foreach (array_unique($overtime) as $value) {
           $overtimess = HairstylistOverTime::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                ->wherenotnull('approve_at')
                ->wherenull('reject_at')
                ->where('not_schedule',0)
                ->wheredate('date', $value['date'])
                ->select('duration')
                ->get();
            foreach ($overtimess as $va) {
                array_push($ove, $va['duration']);
            }
        }
        foreach ($ove as $value) {
            $va      = explode(":", $value);
            $nominal = 0;
            $h       = $va[0];
            $m       = $va[1];
            if ($m >= $set_over) {
                $h = $h + 1;
            }
            $incentive = HairstylistGroupOvertimeDefault::leftJoin('hairstylist_group_overtimes', function ($join) use ($hs) {
                $join->on('hairstylist_group_overtimes.id_hairstylist_group_default_overtimes', 'hairstylist_group_default_overtimes.id_hairstylist_group_default_overtimes')
                    ->where('id_hairstylist_group', $hs->id_hairstylist_group);
            })
                ->select('hairstylist_group_default_overtimes.hours',
                    DB::raw('
                                       CASE WHEN
                                       hairstylist_group_overtimes.value IS NOT NULL THEN hairstylist_group_overtimes.value ELSE hairstylist_group_default_overtimes.value
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
            $total = $total + $nominal;
        }
        $array[] = array(
            "name"  => "Overtime Schedule",
            "value" => $total,

        );
        return $array;
    }
    public static function calculateIncomeOvertimeDay(UserHairStylist $hs, $startDate, $endDate, $id_outlets, $overtimes_day)
    {
        $total          = 0;
        $overtime = count($overtimes_day[$hs->id_user_hair_stylist]??[]);
        $nominal = 0;
       $incentive = HairstylistGroupOvertimeDayDefault::leftJoin('hairstylist_group_overtime_days', function ($join) use ($hs) {
                $join->on('hairstylist_group_overtime_days.id_hairstylist_group_default_overtime_day', 'hairstylist_group_default_overtime_days.id_hairstylist_group_default_overtime_day')
                    ->where('id_hairstylist_group', $hs->id_hairstylist_group);
            })
                ->select('hairstylist_group_default_overtime_days.days',
                    DB::raw('
                                       CASE WHEN
                                       hairstylist_group_overtime_days.value IS NOT NULL THEN hairstylist_group_overtime_days.value ELSE hairstylist_group_default_overtime_days.value
                                       END as value
                                    '),
                )->orderby('days', 'DESC')->get();
            foreach ($incentive as $valu) {
                if ($valu['days'] <= (int) $overtime) {
                    $nominal = $valu['value'];
                    break;
                }
            }
        $array[] = array(
            "name"  => "Overtime Not Schedule",
            "value" => $nominal,

        );
        return $array;
        
    }
    public static function calculateGenerateIncomeOvertimeDay(UserHairStylist $hs, $startDate, $endDate, $id_outlets, $overtimes_day)
    {
        $total          = 0;
//        $overtime = count($overtimes_day[$hs->id_user_hair_stylist]??[]);
        $nominal = 0;
        $data = array();
        $id = null;
       $incentive = HairstylistGroupOvertimeDayDefault::leftJoin('hairstylist_group_overtime_days', function ($join) use ($hs) {
                $join->on('hairstylist_group_overtime_days.id_hairstylist_group_default_overtime_day', 'hairstylist_group_default_overtime_days.id_hairstylist_group_default_overtime_day')
                    ->where('id_hairstylist_group', $hs->id_hairstylist_group);
            })
                ->select('hairstylist_group_default_overtime_days.id_hairstylist_group_default_overtime_day','hairstylist_group_default_overtime_days.days',
                    DB::raw('
                                       CASE WHEN
                                       hairstylist_group_overtime_days.value IS NOT NULL THEN hairstylist_group_overtime_days.value ELSE hairstylist_group_default_overtime_days.value
                                       END as value
                                    '),
                )->orderby('days', 'DESC')->get();
            foreach ($incentive as $valu) {
                if ($valu['days'] <= (int) $overtimes_day) {
                    $nominal = $valu['value'];
                    $id = $valu['id_hairstylist_group_default_overtime_day'];
                    $data = $valu;
                    break;
                }
            }
        $array = array(
            "name"              => "Overtime Not Schedule",
            'id'                => $id,
            "value"             => $nominal,
            'data'              => $data,
            'total_overtime'    => $overtimes_day
        );
        return $array;
        
    }
    public static function calculateIncomeLateness(UserHairStylist $hs, $startDate, $endDate, $id_outlet)
    {
        $total          = 0;
        $nominal = 0;
        $data = array();
        $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->where('is_on_time', 0)
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->get();
        foreach ($total_late as $value) {
            $clock_in_requirement = date('Y-m-d H:i:s', strtotime($value['attendance_date'].' '.$value['clock_in_requirement'].'+'.$value['clock_in_tolerance'].' minutes'));
            $clock_in = date('Y-m-d H:i:s', strtotime($value['attendance_date'].' '.$value['clock_in']));
            $clock_out_requirement = date('Y-m-d H:i:s', strtotime($value['attendance_date'].' '.$value['clock_out_requirement']));
            $clock_out = date('Y-m-d H:i:s', strtotime($value['attendance_date'].' '.$value['clock_out']));
            $date3            = date_create($clock_in_requirement);
            $date4            = date_create($clock_in);
            $diff             = date_diff($date3, $date4);
            $date5            = date_create($clock_out_requirement);
			$date6            = date_create($clock_out);
            $diffs             = date_diff($date5, $date6);
            $minute = 0;
            if(strtotime($clock_in_requirement) < strtotime($clock_in)){
                $s = $diff->h * 60 + $diff->i;
                $minute = $minute + $s;
            }
            if(strtotime($clock_out_requirement) > strtotime($clock_out)){
                $s = $diffs->h * 60 + $diffs->i;
                $minute = $minute + $s;
            }
            
          $incentive = HairstylistGroupLateDefault::leftJoin('hairstylist_group_lates', function ($join) use ($hs) {
                $join->on('hairstylist_group_lates.id_hairstylist_group_default_late', 'hairstylist_group_default_lates.id_hairstylist_group_default_late')
                    ->where('id_hairstylist_group', $hs->id_hairstylist_group);
            })
                ->select('hairstylist_group_default_lates.range',
                    DB::raw('
                                       CASE WHEN
                                       hairstylist_group_lates.value IS NOT NULL THEN hairstylist_group_lates.value ELSE hairstylist_group_default_lates.value
                                       END as value
                                    '),
                )->orderby('range', 'DESC')->get();
            $nominals = 0;
            foreach ($incentive as $valu) {
                if ($valu['range'] <= (int) $minute) {
                    $nominals = $valu['value'];
//                     $data[] = array(
//                        'attendance_date'=>$value['attendance_date'],
//                        'clock_in_requirement'=>$clock_in_requirement,
//                        'clock_out_requirement'=>$clock_out_requirement,
//                        'clock_in'=>$clock_in,
//                        'clock_out'=>$clock_out,
//                        'minute'=>$minute,
//                        'value'=>$valu
//                    );
                    break;
                }
            }
            $nominal = $nominal+$nominals;
        }
        $array[] = array(
            "name"  => "Lateness Hairstylist",
            "value" => $nominal,

        );
        return $array;
        
    }
    public static function calculateGenerateIncomeLateness(UserHairStylist $hs, $startDate, $endDate, $id_outlet)
    {
        $total          = 0;
        $nominal = 0;
        $array = array();
        $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->where('is_on_time', 0)
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->get();
        foreach ($total_late as $value) {
           $clock_in_requirement = date('Y-m-d H:i:s', strtotime($value['attendance_date'].' '.$value['clock_in_requirement'].'+'.$value['clock_in_tolerance'].' minutes'));
            $clock_in = date('Y-m-d H:i:s', strtotime($value['attendance_date'].' '.$value['clock_in']));
            $clock_out_requirement = date('Y-m-d H:i:s', strtotime($value['attendance_date'].' '.$value['clock_out_requirement']));
            $clock_out = date('Y-m-d H:i:s', strtotime($value['attendance_date'].' '.$value['clock_out']));
            $date3            = date_create($clock_in_requirement);
            $date4            = date_create($clock_in);
            $diff             = date_diff($date3, $date4);
            $date5            = date_create($clock_out_requirement);
	    $date6            = date_create($clock_out);
            $diffs             = date_diff($date5, $date6);
            $minute = 0;
            if(strtotime($clock_in_requirement) < strtotime($clock_in)){
                $s = $diff->h * 60 + $diff->i;
                $minute = $minute + $s;
                
            }
            if(strtotime($clock_out_requirement) > strtotime($clock_out)){
               $s = $diffs->h * 60 + $diffs->i;
                $minute = $minute + $s;
            }
        $incentive = HairstylistGroupLateDefault::leftJoin('hairstylist_group_lates', function ($join) use ($hs) {
                $join->on('hairstylist_group_lates.id_hairstylist_group_default_late', 'hairstylist_group_default_lates.id_hairstylist_group_default_late')
                    ->where('id_hairstylist_group', $hs->id_hairstylist_group);
            })
                ->select('hairstylist_group_default_lates.id_hairstylist_group_default_late','hairstylist_group_default_lates.range',
                    DB::raw('
                                       CASE WHEN
                                       hairstylist_group_lates.value IS NOT NULL THEN hairstylist_group_lates.value ELSE hairstylist_group_default_lates.value
                                       END as value
                                    '),
                )->orderby('range', 'DESC')->get();
            $nominals = 0;
            foreach ($incentive as $valu) {
                if ($valu['range'] <= (int) $minute) {
                    $nominals = $valu['value'];
                    $data = array(
                                'id_hairstylist_group_default_late'=>$valu['id_hairstylist_group_default_late'],
                                'id_hairstylist_schedule_date'=>$value['id_hairstylist_schedule_date'],
                                'attendance_date'=>$value['attendance_date'],
                                'clock_in_requirement'=>$clock_in_requirement,
                                'clock_out_requirement'=>$clock_out_requirement,
                                'clock_in'=>$clock_in,
                                'clock_out'=>$clock_out,
                                'minute'=>$minute,
                                'range'=>$valu['range'],
                                'nominal'=>$nominals
                            );
                    $array[] = array(
                        'id_hairstylist_group_default_late'=>$valu['id_hairstylist_group_default_late'],
                        'id_hairstylist_schedule_date'=>$value['id_hairstylist_schedule_date'],
                        "name"  => "Lateness Hairstylist",
                        "value" => $nominals,
//                        "data" => $data,
                    );
                    break;
                }
            }
        }
        return $array;
        
    }
    public static function calculateIncomeProteksi(UserHairStylist $hs, $startDate, $endDate, $id_outlet)
    {
          
          $date_end         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value')??null;
          $date_start         = (int)$date_end+1;
          $start_date =  date('Y-m-'.$date_start, strtotime($startDate));
          $end_date = date('Y-m-'.$date_end, strtotime($start_date.'+1 months'));
          $starts = $startDate;
          $ends = $endDate;
          if(!$date_end){
              return array();
          }
          $ar = array();
          $s = 2;
          for($i=1;$i<$s;$i){
              if($starts>=$start_date){
               $e = date('Y-m-'.$date_end, strtotime($starts.'+1 months'));
              }else{
               $e = date('Y-m-'.$date_end, strtotime($starts));  
              }
              if($e >=$endDate){
                  $e = $endDate;
                  $ar[]= array(
                  'start'=>$starts,
                  'end'=>$e,
                  'periode'=>date('m', strtotime($e))
                );
                  break;
              }
              $ar[]= array(
                  'start'=>$starts,
                  'end'=>$e,
                  'periode'=>date('m', strtotime($e))
              );
              $starts = date('Y-m-d', strtotime($e.'+1 days'));
          }
        $total          = 0;
        $nominal = 0;
        $nominal_overtime = 0;
        $data = array();
        foreach ($ar as $value) {
        $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $value['start'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $value['end'])
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
			->where('id_outlet', $id_outlet)
			->count();
         $total_timeoff = HairStylistTimeOff::whereNotNull('approve_at')
            ->whereNull('reject_at')
            ->whereDate('date', '>=', $value['start'])
            ->whereDate('date', '<=', $value['end'])
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->count();
          //terlambat
        $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->where('is_on_time', 0)
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $value['start'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $value['end'])
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
			->where('id_outlet', $id_outlet)
            ->count();
        //absensi
        $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $value['start'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $value['end'])
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
			->where('id_outlet', $id_outlet)
            ->count();
            $incentive = HairstylistGroupProteksiAttendanceDefault::leftJoin('hairstylist_group_proteksi_attendances', function ($join) use ($hs) {
                $join->on('hairstylist_group_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance', 'hairstylist_group_default_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance')
                    ->where('id_hairstylist_group', $hs->id_hairstylist_group);
            })
                 ->where('month', $value['periode'])
                ->select('hairstylist_group_default_proteksi_attendances.month',
                    DB::raw('
                        CASE WHEN
                        hairstylist_group_proteksi_attendances.value IS NOT NULL THEN hairstylist_group_proteksi_attendances.value ELSE hairstylist_group_default_proteksi_attendances.value
                        END as value
                     '),
                DB::raw('
                    CASE WHEN
                    hairstylist_group_proteksi_attendances.amount IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount ELSE hairstylist_group_default_proteksi_attendances.amount
                    END as amount
                 '),
                DB::raw('
                    CASE WHEN
                    hairstylist_group_proteksi_attendances.amount_proteksi IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount_proteksi ELSE hairstylist_group_default_proteksi_attendances.amount_proteksi
                    END as amount_proteksi
                 '),
                DB::raw('
                    CASE WHEN
                    hairstylist_group_proteksi_attendances.amount_day IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount_day ELSE hairstylist_group_default_proteksi_attendances.amount_day
                    END as amount_day
                 '),
                )->first();
            $nominals = 0;
            $total_attend = $total_attend+$total_timeoff;
            if($total_attend>0){
                if($total_attend>=$incentive->value){
                    $nominals = $incentive->amount_proteksi;
                    $proteksi_outlet = array();
                    $proteksi = Setting::where('key','proteksi_hs')->first();
                    if($proteksi){
                            $outlet = Outlet::join('locations','locations.id_location','outlets.id_location')->where('id_outlet',$hs->id_outlet)->first();
                            if(isset($outlet->start_date)){
                                $proteksi_outlet = json_decode($proteksi['value_text']??[],true);
                                $group = HairstylistGroupProteksi::where(array('id_hairstylist_group'=>$hs->id_hairstylist_group))->first();
                                if(isset($group['value'])){
                                        $proteksi_outlet['value'] = $group['value'];
                                }
                                $date3      = date_create(date('Y-m-d', strtotime($outlet->start_date)));
                                $date4      = date_create($ends);
                                $diff       = date_diff($date3, $date4);
                                $outlet_age = $diff->y * 12 + $diff->m;
                                if($outlet_age < $proteksi_outlet['range']){
                                        if($proteksi_outlet['value']>$nominals){
                                                $nominals = $proteksi_outlet['value'];
                                        }
                                } 
                            }
                    }
                    if($total_timeoff>0||$total_late>0||$total_absen>0){
                        $nominals = $total_attend * $incentive->amount_day;
                        if($incentive->amount??0 > $nominals){
                            $nominals = $incentive->amount;
                        }
//                        $nominals = $total_attend * $incentive->amount_day;
                    }
                    $incentives = HairstylistGroupOvertimeDayDefault::leftJoin('hairstylist_group_overtime_days', function ($join) use ($hs) {
                $join->on('hairstylist_group_overtime_days.id_hairstylist_group_default_overtime_day', 'hairstylist_group_default_overtime_days.id_hairstylist_group_default_overtime_day')
                    ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                })
                ->select('hairstylist_group_default_overtime_days.days',
                    DB::raw('
                                       CASE WHEN
                                       hairstylist_group_overtime_days.value IS NOT NULL THEN hairstylist_group_overtime_days.value ELSE hairstylist_group_default_overtime_days.value
                                       END as value
                                    '),
                )->orderby('days', 'DESC')->get();
                $overtime = $total_attend - $incentive->value;
                if($overtime>0){
                    foreach ($incentives as $valu) {
                    if ($valu['days'] <= (int) $overtime) {
                        $nominal_overtime = $nominal_overtime+$valu['value'];
                        break;
                        }
                     }
                }
                
                }else{
                    $nominals = $total_attend * $incentive->amount_day; 
                }
            }
            $nominal = $nominal+$nominals;
//            $data[] = array(
//                'start'=>$value['start'],
//                'end'=>$value['end'],
//                'periode'=>$value['periode'],
//                'month'=>$incentive->month,
//                'value'=>$incentive->value,
//                'amount'=>$incentive->amount,
//                'amount_day'=>$incentive->amount_day,
//                'total_attend'=>$total_attend,
//                '$total_timeoff'=>$total_timeoff,
//                '$total_late'=>$total_late,
//                '$total_absen'=>$total_absen,
//                'nominals'=>$nominals,
//            );
        }
//        return $data;
        $array[] = array(
            "name"  => "Attendance",
            "value" => $nominal,

        );
        $array[] = array(
            "name"  => "Overtime Not Schedule",
            "value" => $nominal_overtime,

        );
        return $array;
        
    }
     public static function calculateGenerateIncomeProteksi(UserHairStylist $hs, $startDate, $endDate)
    {
          $date_end         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value')??null;
          $date_start         = (int)$date_end+1;
          $start_date =  date('Y-m-'.$date_start, strtotime($startDate));
          $end_date = date('Y-m-'.$date_end, strtotime($start_date.'+1 months'));
          $starts = $startDate;
          $ends = $endDate;
          if(!$date_end){
              return array();
          }
          $ar = array();
          $s = 2;
          for($i=1;$i<$s;$i){
              if($starts>=$start_date){
               $e = date('Y-m-'.$date_end, strtotime($starts.'+1 months'));
              }else{
               $e = date('Y-m-'.$date_end, strtotime($starts));  
              }
              if($e >=$endDate){
                  $e = $endDate;
                  $ar[]= array(
                  'start'=>$starts,
                  'end'=>$e,
                  'periode'=>date('m', strtotime($e))
                );
                  break;
              }
              $ar[]= array(
                  'start'=>$starts,
                  'end'=>$e,
                  'periode'=>date('m', strtotime($e))
              );
              $starts = date('Y-m-d', strtotime($e.'+1 days'));
          }
        $total          = 0;
        $nominal = 0;
        $data = array();
        $array = array();
        $overtime = array();
        $protec = array();
        foreach ($ar as $value) {
            $id_proteksi = 0;
            $total_proteksi = 0;
            $nama_proteksi = "No Protection";
        $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $value['start'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $value['end'])
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->count();
        $total_timeoff = HairStylistTimeOff::whereNotNull('approve_at')
            ->whereNull('reject_at')
            ->whereDate('date', '>=', $value['start'])
            ->whereDate('date', '<=', $value['end'])
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->count();
        //terlambat
        $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->where('is_on_time', 0)
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $value['start'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $value['end'])
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->count();
        //absensi
        $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $value['start'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $value['end'])
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->count();
          $incentive = HairstylistGroupProteksiAttendanceDefault::leftJoin('hairstylist_group_proteksi_attendances', function ($join) use ($hs) {
                $join->on('hairstylist_group_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance', 'hairstylist_group_default_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance')
                    ->where('id_hairstylist_group', $hs->id_hairstylist_group);
            })
                 ->where('month', $value['periode'])
                ->select('hairstylist_group_default_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance','hairstylist_group_default_proteksi_attendances.month',
                    DB::raw('
                        CASE WHEN
                        hairstylist_group_proteksi_attendances.value IS NOT NULL THEN hairstylist_group_proteksi_attendances.value ELSE hairstylist_group_default_proteksi_attendances.value
                        END as value
                     '),
                DB::raw('
                    CASE WHEN
                    hairstylist_group_proteksi_attendances.amount IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount ELSE hairstylist_group_default_proteksi_attendances.amount
                    END as amount
                 '),
                DB::raw('
                    CASE WHEN
                    hairstylist_group_proteksi_attendances.amount_proteksi IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount_proteksi ELSE hairstylist_group_default_proteksi_attendances.amount_proteksi
                    END as amount_proteksi
                 '),
                DB::raw('
                    CASE WHEN
                    hairstylist_group_proteksi_attendances.amount_day IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount_day ELSE hairstylist_group_default_proteksi_attendances.amount_day
                    END as amount_day
                 '),
                )->first();
            $nominals = 0;
             //proteksi
                $proteksi_outlet = array();
                $proteksi = Setting::where('key','proteksi_hs')->first();
                if($proteksi){
                $outlet = Outlet::where('id_outlet',$hs->id_outlet)->first();
                $outlet = Outlet::join('locations','locations.id_location','outlets.id_location')->where('id_outlet',$hs->id_outlet)->first();
                if(isset($outlet->start_date)){
                $nominals = $incentive->amount_proteksi;
                $proteksi_outlet = json_decode($proteksi['value_text'],true);
                $group = HairstylistGroupProteksi::where(array('id_hairstylist_group'=>$hs->id_hairstylist_group))->first();
                if(isset($group['value'])){
                    $proteksi_outlet['value'] = $group['value'];
                }
                $date3      = date_create(date('Y-m-d', strtotime($outlet->start_date)));
                $date4      = date_create($value['end']);
                $diff       = date_diff($date3, $date4);
                $outlet_age = $diff->y * 12 + $diff->m;
                    if($outlet_age < $proteksi_outlet['range']){
                        $id_proteksi =  $proteksi->id_setting;
                        $total_proteksi = $proteksi_outlet['value'];
                        $nama_proteksi = "Protection Outlet";
                    }
                }
                }
                
            $total_attend = $total_attend+$total_timeoff;
            if($total_attend>0){
                if($total_attend>=$incentive->value){
                    if($total_timeoff>0||$total_late>0||$total_absen>0){
                        $nominals = $incentive->amount;    
                        if($incentive->amount > $total_proteksi){
                            $id_proteksi = $incentive->id_hairstylist_group_default_proteksi_attendance;
                            $total_proteksi = $incentive->amount;
                            $nama_proteksi = "Protection Attendance";
                        }
                    }
                  $incentives = HairstylistGroupOvertimeDayDefault::leftJoin('hairstylist_group_overtime_days', function ($join) use ($hs) {
                            $join->on('hairstylist_group_overtime_days.id_hairstylist_group_default_overtime_day', 'hairstylist_group_default_overtime_days.id_hairstylist_group_default_overtime_day')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_group);
                        })
                            ->select('hairstylist_group_default_overtime_days.id_hairstylist_group_default_overtime_day','hairstylist_group_default_overtime_days.days',
                                DB::raw('
                                                   CASE WHEN
                                                   hairstylist_group_overtime_days.value IS NOT NULL THEN hairstylist_group_overtime_days.value ELSE hairstylist_group_default_overtime_days.value
                                                   END as value
                                                '),
                            )->orderby('days', 'DESC')->get();
                        foreach ($incentives as $valu) {
                            $overtimes_day = $total_attend-$incentive->value;
                            if($overtimes_day>0){
                                if ($valu['days'] <= (int) $overtimes_day) {
                                    $id = $valu['id_hairstylist_group_default_overtime_day'];
                                    $data = $valu;
                                    $overtime[] = array(
                                        "name"              => "Overtime Not Schedule",
                                        'id'                => $valu['id_hairstylist_group_default_overtime_day'],
                                        "value"             => $valu,
                                        'data'              => $data,
                                        'total_overtime'    => $overtimes_day
                                    );
                                }
                            }
                        }
                }else{
                    $nominals = $total_attend * $incentive->amount_day; 
                }
            }
//            $nominal = $nominal+$nominals;
            $data = array(
                'id_hairstylist_group_default_proteksi_attendance'=>$incentive->id_hairstylist_group_default_proteksi_attendance,
                'start'=>$value['start'],
                'end'=>$value['end'],
                'periode'=>$value['periode'],
                'month'=>$incentive->month,
                'value'=>$incentive->value,
                'amount'=>$incentive->amount,
                'amount_proteksi'=>$incentive->amount_proteksi,
                'amount_day'=>$incentive->amount_day,
                'total_attend'=>$total_attend,
                'nominals'=>$nominals,
            );
            $array[]= array(
                'id_hairstylist_group_default_proteksi_attendance'=>$incentive->id_hairstylist_group_default_proteksi_attendance,
                "name"  => "Proteksi Attendance",
                "value" => $nominals,
                "data" => $data,
            );
            $protec[] = array(
                'id'=>$id_proteksi,
                'name'=>$nama_proteksi,
                'value'=>$total_proteksi,
            );
        }
        return array(
            'proteksi'=>$array,
            'overtime'=>$overtime,
            'proteksi_fee' => $protec
        );
        
    }
     public static function calculateGenerateIncomeProtec(UserHairStylist $hs, $startDate, $endDate)
    {
          $total_income = 0;
          $date_end         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value')??null;
          $date_start         = (int)$date_end+1;
          $start_date =  date('Y-m-'.$date_start, strtotime($startDate));
          $end_date = date('Y-m-'.$date_end, strtotime($start_date.'+1 months'));
          $starts = $startDate;
          $ends = $endDate;
          $periode = date('m', strtotime($endDate));
        $total          = 0;
        $nominal = 0;
        $data = array();
        $array = array();
        $overtime = array();
        $protec = array();
            $id_proteksi = 0;
            $total_proteksi = 0;
            $nama_proteksi = "No Protection";
        $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $starts)
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $ends)
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->count();
        $total_timeoff = HairStylistTimeOff::whereNotNull('approve_at')
            ->whereNull('reject_at')
            ->whereDate('date', '>=',$starts)
            ->whereDate('date', '<=', $ends)
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->count();
        //terlambat
        $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->where('is_on_time', 0)
            ->whereDate('hairstylist_attendances.attendance_date', '>=',$starts)
            ->whereDate('hairstylist_attendances.attendance_date', '<=',$ends)
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->count();
        //absensi
        $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=',$starts)
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $ends)
            ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->count();
            $incentive = HairstylistGroupProteksiAttendanceDefault::leftJoin('hairstylist_group_proteksi_attendances', function ($join) use ($hs) {
                $join->on('hairstylist_group_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance', 'hairstylist_group_default_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance')
                    ->where('id_hairstylist_group', $hs->id_hairstylist_group);
            })
                 ->where('month', $periode)
                ->select('hairstylist_group_default_proteksi_attendances.id_hairstylist_group_default_proteksi_attendance','hairstylist_group_default_proteksi_attendances.month',
                    DB::raw('
                        CASE WHEN
                        hairstylist_group_proteksi_attendances.value IS NOT NULL THEN hairstylist_group_proteksi_attendances.value ELSE hairstylist_group_default_proteksi_attendances.value
                        END as value
                     '),
                DB::raw('
                    CASE WHEN
                    hairstylist_group_proteksi_attendances.amount IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount ELSE hairstylist_group_default_proteksi_attendances.amount
                    END as amount
                 '),
                DB::raw('
                    CASE WHEN
                    hairstylist_group_proteksi_attendances.amount_proteksi IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount_proteksi ELSE hairstylist_group_default_proteksi_attendances.amount_proteksi
                    END as amount_proteksi
                 '),
                DB::raw('
                    CASE WHEN
                    hairstylist_group_proteksi_attendances.amount_day IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount_day ELSE hairstylist_group_default_proteksi_attendances.amount_day
                    END as amount_day
                 '),
                )->first();
            $nominals = 0;
             //proteksi
                $proteksi_outlet = array();
                $proteksi = Setting::where('key','proteksi_hs')->first();
                
                
            $total_attend = $total_attend+$total_timeoff;
            $nama_proteksi = "Non Protection";
            if($total_attend>0){
                if($total_attend>=$incentive->value){
                    if($total_timeoff==0&&$total_late==0&&$total_absen==0){
                        $nama_proteksi = "Brand Salary Protection";
                        if($total_income<$incentive->amount_proteksi){
                            $total_income = $incentive->amount_proteksi;
                        }
                            if($proteksi){
                                    $outlet = Outlet::join('locations','locations.id_location','outlets.id_location')->where('id_outlet',$hs->id_outlet)->first();
                                    if(isset($outlet->start_date)){
                                            $nominals = $incentive->amount_proteksi;
                                            $proteksi_outlet = json_decode($proteksi['value_text']??[],true);
                                            $group = HairstylistGroupProteksi::where(array('id_hairstylist_group'=>$hs->id_hairstylist_group))->first();
                                            if(isset($group['value'])){
                                                    $proteksi_outlet['value'] = $group['value'];
                                            }
                                            $date3      = date_create(date('Y-m-d', strtotime($outlet->start_date)));
                                            $date4      = date_create($ends);
                                            $diff       = date_diff($date3, $date4);
                                            $outlet_age = $diff->y * 12 + $diff->m;
                                            if($outlet_age < $proteksi_outlet['range']){
                                                    if($proteksi_outlet['value']>$incentive->amount_proteksi){
                                                            $nama_proteksi = "Protection Outlet";
                                                    }
                                                    if($total_income<$proteksi_outlet['value']){
                                                        $total_income = $proteksi_outlet['value'];
                                                    }
                                            } 
                                    }
                            }
                    }else{
                        $nama_proteksi = "Brand Salary";
                    }
                }else{
                    $nama_proteksi = "Brand Salary Per Day";
                }
            }
        return array(
            'name'=>$nama_proteksi,
//            'total_income'=>$total_income,
        );
        
    }
    public static function calculateFixedIncentive(UserHairStylist $hs, $startDate, $endDate,$outlet, $incomeDefault)
    { 
        $array   = array();
        $tanggal = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value') ?? 25;
        // Variable that store the date interval
        // of period 1 day
        $interval = new DateInterval('P1D');
        $outlet = Outlet::where('id_outlet',$hs->id_outlet)->join('locations','locations.id_location','outlets.id_location')
                    ->select(
                            'id_outlet',
                            'start_date'
                    )->first();
        $realEnd = new DateTime($endDate);
        $realEnd->add($interval);

        $period     = new DatePeriod(new DateTime($startDate), $interval, $realEnd);
        $total_date = 0;
        // Use loop to store date into array
        foreach ($period as $date) {
            $angka = $date->format('d');
            if ($angka == $tanggal) {
                $total_date++;
            }
        }
        $date_now         = date('Y-m-d');
        $years_of_service = 0;
        $date3            = date_create(date('Y-m-d', strtotime($hs->join_date)));
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
        $overtime   = $incomeDefault;
        foreach ($overtime as $value) {
            foreach ($value['detail'] as $va) {
                $insen               = HairstylistGroupFixedIncentive::where(array('id_hairstylist_group_default_fixed_incentive_detail' => $va['id_hairstylist_group_default_fixed_incentive_detail'], 'id_hairstylist_group' => $hs->id_hairstylist_group))->first();
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
                if ($va['type']??null == "Multiple") {
                    if ($va['formula']??null == 'outlet_age') {
                        $h = $outlet_age;
                    } elseif ($va['formula']??null == 'years_of_service') {
                        $h = $years_of_service;
                    } else {
                        break;
                    }
                    foreach ($va['detail'] as $valu) {
                        if ($valu['range']??0 <= (int) $h) {
                            if ($valu['default'] == 1) {
                                $harga = (int)$valu['value']??0 * $total_date;
                            } else {
                                $harga = (int)$valu['default_value']??0 * $total_date;
                            }
                            break;
                        }
                    }
                } else {

                    if ($va['detail']['0']['default'] == 1) {
                        $harga = (int)$va['detail']['0']['value']??0 * $total_date;
                    } else {
                        $harga = (int)$va['detail']['0']['default_value']??0 * $total_date;
                    }
                }
            }
            $array[] = array(
                "id_hairstylist_group_default_fixed_incentive" => $va['id_hairstylist_group_default_fixed_incentive']??null,
                "name"                                         => $va['name_fixed_incentive']??null,
                "value"                                        => (int)$harga??0,
                'status'                                       => $va['status']??null,
                'id_outlet'                                    => $outlet['id_outlet']??null,
            );
        }
        
        return $array;
    }
    public static function calculateSalaryCuts(UserHairStylist $hs, $startDate, $endDate, $loan)
    {
        $array = array();
        foreach ($loan as $value) {
            $array[] = array(
                "name"  => $value['name_category_loan'],
                "value" => $value['value'],

            );
        }

        return $array;
    }
    public static function calculateLoan(UserHairStylist $hs, $startDate, $endDate)
    {
        $array = array();
        $loan  = HairstylistLoan::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->join('hairstylist_category_loans', 'hairstylist_category_loans.id_hairstylist_category_loan', 'hairstylist_loans.id_hairstylist_category_loan')
            ->join('hairstylist_loan_returns', function ($join) use ($startDate, $endDate) {
                $join->on('hairstylist_loan_returns.id_hairstylist_loan', 'hairstylist_loans.id_hairstylist_loan')
                    ->where('hairstylist_loan_returns.return_date', '<=', $endDate)
                    ->where('hairstylist_loan_returns.status_return', 'Pending');
            })
            ->leftjoin('hairstylist_sales_payments','hairstylist_sales_payments.id_hairstylist_sales_payment','hairstylist_loans.id_hairstylist_sales_payment')
            ->where('status_loan', 'Success')
            ->get();
          
        foreach ($loan as $value) {
            $location = UserHairStylist::join('outlets', 'outlets.id_outlet', 'user_hair_stylist.id_outlet')
            ->join('locations', 'locations.id_location', 'outlets.id_location')
            ->where('user_hair_stylist.id_user_hair_stylist', $loan['id_user_hair_stylist'])
            ->select('locations')
            ->first();
            if(isset($value['type'])){
                    $array[] = array(
                        "name"                       => $value['name_category_loan'],
                        "value"                      => $value['amount_return'],
                        "id_outlet"                  => $hs->id_outlet,
                        "id_hairstylist_loan_return" => $value['id_hairstylist_loan_return'],
                        "SalesInvoiceID"             => $value['SalesInvoiceID'],
                        "amount_return"              => $value['amount_return'],
                        "name_category_loan"         => $value['name_category_loan'],
                        "type"                       => $value['type'],
                        "company"                    => $location['company_type'] ?? 'PT IMA'      
                    );
            }else{
                $array[] = array(
                    "name"                       => $value['name_category_loan'],
                    "value"                      => $value['amount_return'],
                    "id_outlet"                  => $hs->id_outlet,
                    "id_hairstylist_loan_return" => $value['id_hairstylist_loan_return'],
                    "company"                    => $location['company_type'] ?? 'PT IMA'
                );
            }
        }
        return $array;
    }
    public static function generateIncome($hs, $type = 'end',$startDate,$endDate,$id,$year,$month,$date)
    {
        $total = 0;
        $starts = date('Y-m-d', strtotime($endDate. "-1 months +1 days"));
        $hsIncome = Static::where('id_hairstylist_income',$id)->first();
        if (!$hsIncome) {
            throw new \Exception('Failed create hs income data');
        }
        
//        $hair = HairstylistIncomeDetail::where('id_hairstylist_income',$id)->delete();
        if ($type == 'middle') {
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
            $dates         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value')??0;
            $calcu = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
            if($dates>0){
                $dates = date('Y-m-'.$dates, strtotime($endDate));
                $dates = date('Y-m-d', strtotime($dates.'-1 months +1 days'));
            }
        } else {
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
            $dates         = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value')??0;
            $calcu = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
            if($dates>0){
                $dates = date('Y-m-'.$dates, strtotime($endDate));
                $dates = date('Y-m-d', strtotime($dates.'+1 days'));
            }
        }
        $call = array(); 
        foreach ($calculations as $calculation) {
            if (!$calcu) {
                $call[] = array(
                    'calculation'=>$calculation, 
                    'start_date'=>$starts, 
                    'end_date'=>$endDate, 
                );
            }
            if (in_array($calculation, $calcu)){
                $call[] = array(
                    'calculation'=>$calculation, 
                    'start_date'=>$dates, 
                    'end_date'=>$endDate, 
                );
                }else{
                    $call[] = array(
                        'calculation'=>$calculation, 
                        'start_date'=>$starts, 
                        'end_date'=>$endDate, 
                    );
                }
        }
        $incomeDefault = HairstylistGroupFixedIncentiveDefault::with(['detail'])->get();
        $total_attend   = 0;
        $total_late     = 0;
        $total_absen    = 0;
        $total_overtime = 0;
        $overtime       = array();
        $outlet         = Outlet::where('id_outlet', $hs->id_outlet)->first();
        $id_outlets     = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        
        
        $outlets = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
           ->whereDate('hairstylist_schedule_dates.date', '>=', $startDate)
            ->whereDate('hairstylist_schedule_dates.date', '<=', $endDate)
            ->selectRaw('id_outlet, id_user_hair_stylist')
            ->where('hairstylist_schedules.id_user_hair_stylist', $hs->id_user_hair_stylist)
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get();
       
        $list_attendance = array();
        $incomes = array();
        $salary_cuts = array();
        $total_incomes = 0;
        $total_salary_cuts = 0;
        foreach ($outlets as $vas) {
            $list_income = array();
            $list_salary_cut = array();
            $price_salary_cut = 0;
            $price_income = 0;
            $hair = $vas['id_user_hair_stylist'];
            $hairst = UserHairStylist::where('id_user_hair_stylist',$vas['id_user_hair_stylist'])->select('id_hairstylist_group')->first();
            $outl = $vas['id_outlet'];
            $outlet_name = Outlet::where('id_outlet',$vas['id_outlet'])->select('outlet_name')->first();
            $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                        ->whereNotNull('clock_in')
                        ->where('id_outlet',$outl)
                        ->where('id_user_hair_stylist',$hair)
                        ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
                        ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
                        ->selectRaw('count(*) as total')
                        ->first()['total']??'0';
            $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                            ->whereNotNull('clock_in')
                            ->where('is_on_time', 0)
                            ->where('id_outlet',$outl)
                            ->where('id_user_hair_stylist',$hair)
                            ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
                            ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
                            ->selectRaw('count(*) as total')
                            ->first()['total'] ?? '0';
            $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                            ->whereNull('clock_in')
                            ->where('id_outlet',$outl)
                            ->where('id_user_hair_stylist',$hair)
                            ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
                            ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
                            ->selectRaw('count(*) as total')
                            ->first()['total'] ?? '0';
            $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->whereNotNull('clock_in')
                                ->where('is_overtime',1)
                                ->where('id_outlet',$outl)
                                ->where('id_user_hair_stylist',$hair)
                                ->whereDate('hairstylist_attendances.attendance_date', '>=', $startDate)
                                ->whereDate('hairstylist_attendances.attendance_date', '<=', $endDate)
                                ->select(DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as dates'))
                                ->get()?? '0';
            $list_attendance[] = array(
                'header_title' => "Outlet",
                'header_content' => $outlet_name->outlet_name,
                'footer_title' => null,
                'footer_content' => null,
                'contents'=>array(
                    array(
                       'title'=>"Hari Masuk",
                       'content' => $total_attend,
                    ),
                    array(
                       'title'=>"Total Terlambat",
                       'content' => $total_late,
                    ),
                    array(
                       'title'=>"Tidak Masuk",
                       'content' => $total_absen,
                    ),
                    array(
                       'title'=>"Tambahan Jam",
                       'content' => $total_overtimes,
                    ),
                )
            ); 
            foreach ($call as $value) {
                $start_date = $value['start_date'];
                $end_date = $value['end_date'];
                $calculation = $value['calculation'];
                $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                        ->whereNotNull('clock_in')
                        ->where('id_outlet',$outl)
                        ->where('id_user_hair_stylist',$hair)
                        ->whereDate('hairstylist_attendances.attendance_date', '>=', $start_date)
                        ->whereDate('hairstylist_attendances.attendance_date', '<=', $end_date)
                        ->selectRaw('count(*) as total')
                        ->first()['total']??'0';
            $total_late = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                            ->whereNotNull('clock_in')
                            ->where('is_on_time', 0)
                            ->where('id_outlet',$outl)
                            ->where('id_user_hair_stylist',$hair)
                            ->whereDate('hairstylist_attendances.attendance_date', '>=', $start_date)
                            ->whereDate('hairstylist_attendances.attendance_date', '<=', $end_date)
                            ->selectRaw('count(*) as total')
                            ->first()['total'] ?? '0';
            $total_absen = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                            ->whereNull('clock_in')
                            ->where('id_outlet',$outl)
                            ->where('id_user_hair_stylist',$hair)
                            ->whereDate('hairstylist_attendances.attendance_date', '>=', $start_date)
                            ->whereDate('hairstylist_attendances.attendance_date', '<=', $end_date)
                            ->selectRaw('count(*) as total')
                            ->first()['total'] ?? '0';
            $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->whereNotNull('clock_in')
                                ->where('is_overtime',1)
                                ->where('id_outlet',$outl)
                                ->where('id_user_hair_stylist',$hair)
                                ->whereDate('hairstylist_attendances.attendance_date', '>=', $start_date)
                                ->whereDate('hairstylist_attendances.attendance_date', '<=', $end_date)
                                ->select(DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as dates'))
                                ->get()?? '0';
                if ($calculation == 'product_commission') {
                    $mulai = $start_date;
                    $pc_mid = 0;
                    $pc_end = 0;
                    $product_mid = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? [];
                     foreach ($product_mid as $c) {
                         if ($c == 'product_commission') {
                             $pc_mid = 1;
                         }
                     }
                    $product_end = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? [];
                    foreach ($product_end as $c) {
                         if ($c == 'product_commission') {
                             $pc_end = 1;
                         }
                     }
                   $trxs = TransactionProduct::where(array('transaction_product_services.id_user_hair_stylist' => $vas['id_user_hair_stylist']))
                        ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
                        ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                        ->join('transaction_breakdowns', function ($join) use ($mulai, $end_date) {
                            $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                                ->whereNotNull('transaction_products.transaction_product_completed_at')
                                ->whereBetween('transaction_product_completed_at', [$mulai, $end_date]);
                        })
                        ->where('transactions.id_outlet', $vas['id_outlet'])
                        ->where('transaction_product_services.service_status', 'Completed')
                        ->wherenotnull('transaction_product_services.completed_at')
                        ->where('transaction_breakdowns.type', 'fee_hs')
                        ->select('transaction_products.id_transaction', 'transaction_products.id_transaction_product', 'transaction_breakdowns.*')
                        ->get();
                    $amount = 0;
                    foreach ($trxs as $item) {
                        $amount += $item->value;
                    }
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => $calculation,
                        ],
                            [
                                'id_outlet'    => $vas['id_outlet'],
                                'amount'    => $amount,
                                'type'        => "Incentive",
                                'name_income' => 'Product Comission',
                                'value_detail'=>json_encode(array(
                                    'start_date'=>$mulai,
                                    'end_date'=>$end_date,
                                    'total_attend'   => $total_attend,
                                    'total_late'     => $total_late,
                                    'total_absen'    => $total_absen,
                                    'total_overtime' => $total_overtime,
                                    'data'=>$trxs
                                )),
                            ]);
                    $list_income[] = array(
                        'list'=>"Product Commission",
                        'content'=>$amount
                    );
                    $total_incomes = $total_incomes + $amount;
                    $price_income = $price_income + $amount;
                    $total = $total + $amount;
                } elseif (strpos($calculation, 'incentive_') === 0) {
                    // start_with_calculation
                    $code      = str_replace('incentive_', '', $calculation);
                    $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function ($join) use ($hairst) {
                        $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                            ->where('id_hairstylist_group', $hairst->id_hairstylist_group);
                    })->where('hairstylist_group_default_insentifs.code', $code)
                        ->select('hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.code',
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
                    $formula = str_replace('value', $incentive->value, $incentive->formula);
                    $amount     = 0;
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

                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => $calculation,
                            'reference' => $incentive->id_hairstylist_group_default_insentifs,
                        ],
                            [
                                'id_outlet' => $outl,
                                'amount'    => $amount,
                                'type'        => "Incentive",
                                'name_income' => ucfirst(str_replace('_', ' ', $code)),
                                'value_detail'=> json_encode(array(
                                    'start_date'=>$mulai,
                                    'start_date'=>$end_date,
                                    'total_attend'   => $total_attend,
                                    'total_late'     => $total_late,
                                    'total_absen'    => $total_absen,
                                    'total_overtime' => $total_overtime,
                                    'data'=>json_encode($incentive)
                                )),
                            ]);
                        $list_income[] = array(
                                'list'=>ucfirst(str_replace('_', ' ', $code)),
                                'content'=>$amount
                            );
                        $total_incomes = $total_incomes + $amount;
                        $price_income = $price_income + $amount;
                    $total = $total + $amount;
                } elseif (strpos($calculation, 'salary_cut_') === 0) {
                    // start_with_calculation
                    $code       = str_replace('salary_cut_', '', $calculation);
                    $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function ($join) use ($hairst) {
                        $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                            ->where('id_hairstylist_group', $hairst->id_hairstylist_group);
                    })->where('hairstylist_group_default_potongans.code', $code)
                        ->select('hairstylist_group_default_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.code',
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

                    $formula    = str_replace('value', $salary_cut->value, $salary_cut->formula);
                    $amount     = 0;
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

                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => $calculation,
                            'reference' => $salary_cut->id_hairstylist_group_default_potongans,
                        ],
                            [
                                'id_outlet' => $outl,
                                'amount'    => $amount,
                                'type'        => "Salary Cut",
                                'name_income' => ucfirst(str_replace('_', ' ', $code)),
                                'value_detail'=> json_encode(array(
                                    'start_date'=>$mulai,
                                    'start_date'=>$end_date,
                                    'total_attend'   => $total_attend,
                                    'total_late'     => $total_late,
                                    'total_absen'    => $total_absen,
                                    'total_overtime' => $total_overtime,
                                    'data'=>json_encode($salary_cut)
                                )),
                            ]);
                        $list_salary_cut[] = array(
                                'list'=>ucfirst(str_replace('_', ' ', $code)),
                                'content'=>$amount
                            );
                    $total_salary_cuts = $total_salary_cuts + $amount;
                    $price_salary_cut = $price_salary_cut + $amount;
                    $total = $total - $amount;
                }
            }
            if ($type == 'end') {
           $startDate = date('Y-m-d', strtotime("$year-" . ($month - 1) . "-$date +1 days"));
           $total_overtimes = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hair, $outl) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hair)
                    ->where('id_outlet', $outl);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$startDate, $endDate])
                ->select('date')
                ->get();
            foreach ($total_overtimes as $value) {
                array_push($overtime, $value);
            }
                $over = 0;
                $ove  = array();
                foreach (array_unique($overtime) as $value) {
                    $overtimess = HairstylistOverTime::where('id_user_hair_stylist',$hair)
                        ->wherenotnull('approve_at')
                        ->wherenull('reject_at')
                        ->where('not_schedule',0)
                        ->wheredate('date', $value['date'])
                        ->get();
                    foreach ($overtimess as $va) {
                        array_push($ove, array(
                            'duration'                => $va['duration'],
                            'id_outlet'               => $va['id_outlet'],
                            'id_hairstylist_overtime' => $va['id_hairstylist_overtime'],
                        ));
                    }
                }
                $to_overtime = 0;
                foreach ($ove as $value) {
                        $va        = explode(":", $value['duration']);
                        $nominal   = 0;
                        $h         = $va[0];
                        $id_hairstylist_group_default_overtimes = 0;
                        $incentive = HairstylistGroupOvertimeDefault::leftJoin('hairstylist_group_overtimes', function ($join) use ($hairst) {
                            $join->on('hairstylist_group_overtimes.id_hairstylist_group_default_overtimes', 'hairstylist_group_default_overtimes.id_hairstylist_group_default_overtimes')
                                ->where('id_hairstylist_group', $hairst->id_hairstylist_group);
                        })
                            ->select('hairstylist_group_default_overtimes.id_hairstylist_group_default_overtimes','hairstylist_group_default_overtimes.hours',
                                DB::raw('
                                                   CASE WHEN
                                                   hairstylist_group_overtimes.value IS NOT NULL THEN hairstylist_group_overtimes.value ELSE hairstylist_group_default_overtimes.value
                                                   END as value
                                                '),
                            )->orderby('hours', 'DESC')->get();
                        foreach ($incentive as $valu) {
                            if ($valu['hours'] <= (int) $h) {
                                $nominal = $valu['value'];
                                $id_hairstylist_group_default_overtimes = $valu['id_hairstylist_group_default_overtimes'];
                                break;
                            }
                            $nominal = $valu['value'];
                            $id_hairstylist_group_default_overtimes = $valu['id_hairstylist_group_default_overtimes'];
                        }
                        if($id_hairstylist_group_default_overtimes){
                           $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Overtime",
                                'reference' => $id_hairstylist_group_default_overtimes,
                            ],
                                [
                                    'id_outlet' => $value['id_outlet'],
                                    'amount'    => $nominal,
                                ]); 
                        }
                        
                        $total = $total + $nominal;
                        $total_incomes = $total_incomes + $nominal;
                        $price_income = $price_income + $nominal;
                        $to_overtime = $to_overtime + $nominal;
                    }
                if($to_overtime>0){
                    $list_income[] = array(
                            'list'=>"Overtime",
                            'content'=>$amount
                        );
                }
                //Fixed Incentive
                if($hs->id_outlet == $outl&&count($incomeDefault)>0){
                $fixed = self::calculateFixedIncentive($hs, $startDate, $endDate,$outl,$incomeDefault);
                foreach ($fixed as $value) {
                    if ($value['status'] == 'incentive') {
                        $total = $total + $value['value'];
                        $total_incomes = $total_incomes + $value['value'];
                        $price_income = $price_income + $value['value'];
                        $typess = "Incentive";
                        $list_income[] = array(
                            'list'=>$value['name'],
                            'content'=> $value['value']
                        );
                    } else {
                        $total = $total - $value['value'];
                        $total_salary_cuts = $total_salary_cuts + $value['value'];
                        $price_salary_cut = $price_salary_cut + $value['value'];
                        $typess = "Salary Cut";
                        $list_salary_cut[] = array(
                            'list'=>$value['name'],
                            'content'=> $value['value']
                        );
                    }
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Fixed Incentive",
                        'reference' => $value['id_hairstylist_group_default_fixed_incentive'],
                    ],
                        [
                            'id_outlet' => $value['id_outlet'],
                            'amount'    => $value['value'],
                            'type'        => $typess,
                            'name_income' => $value['name'],
                            'value_detail'=> json_encode($value),
                        ]);
                    }
                }       
                //Proteksi Attendance
              if($hs->id_outlet == $outl){
             $proteksi = self::calculateGenerateIncomeProteksi($hs, $startDate, $endDate);
                foreach ($proteksi['proteksi'] as $value) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Proteksi Attendace",
                                'reference' => $value['id_hairstylist_group_default_proteksi_attendance'],
                            ],
                            [
                                'id_outlet'   => $outlet,
                                'amount'      => $value['value'],
                                'type'        => "Incentive",
                                'name_income' => "Proteksi Attendance",
                                'value_detail'=> json_encode($value),
                            ]);
                    $total_incomes = $total_incomes + $value['value']??0;
                    $price_income = $price_income + $value['value']??0;
                    $total = $total + $value['value']??0;
                    $list_income[] = array(
                            'list'=>'Proteksi Attendance',
                            'content'=> $value['value']
                        );
                    } 
                foreach ($proteksi['overtime'] as $value) {
                    $value;
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Overtime Not Schedule",
                                'reference' => $value['id'],
                            ],
                            [
                                'id_outlet'   => $outlet,
                                'amount'      => $value['value'],
                                'type'        => "Incentive",
                                'name_income' => "Overtime Not Schedule",
                                'value_detail'=> json_encode($value),
                            ]);
                    $total_incomes = $total_incomes + $value['value']['value']??0;
                    $price_income = $price_income + $value['value']['value']??0;
                    $total = $total + $value['value']['value']??0;
                    $list_income[] = array(
                            'list'=>"Overtime Not Schedule",
                            'content'=> $value['value']['value']??0
                        );
                    } 
                    
                } 
                  //Lateness
               $late = self::calculateGenerateIncomeLateness($hs, $startDate, $endDate, $outl);
                $price_late = 0;
                foreach ($late as $value) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Lateness Hairstylist",
                        'reference' => $value['id_hairstylist_group_default_late'],
                    ],
                    [
                        'id_outlet'   => $outl,
                        'amount'      => $value['value'],
                        'type'        => "Salary Cut",
                        'name_income' => "Lateness Hairstylist",
                        'value_detail'=> json_encode($value),
                    ]);
                    $price_late = $price_late + $value['value'];
                    $total = $total - $value['value'];
                } 
                if($price_late){
                    $list_salary_cut[] = array(
                                'list'=>'Keterlambatan',
                                'content'=>$price_late
                            );
                    $total_salary_cuts = $total_salary_cuts + $price_late;
                    $price_salary_cut = $price_salary_cut + $price_late;
                };
                    
        
                //loan
                $loan = self::calculateLoan($hs, $startDate, $endDate);
                foreach ($loan as $value) {
                    $total = $total - $value['value'];
                    $total_salary_cuts = $total_salary_cuts + $value['value'];
                    $price_salary_cut = $price_salary_cut + $value['value'];
                    if ($total >= 0) {
                        if(isset($value['type'])){
                        $icount = Icount::SalesPayment($value, $value['company'], null, null);
                        if($icount['response']['Status']=='1' && $icount['response']['Message']=='success'){
                            $icount = $icount['response']['Data'][0];
                            $loanicount = HairstylistLoanIcount::create([
                             'SalesPaymentID'=> $icount['SalesPaymentID'],
                             'SalesInvoiceID'=> $value['SalesInvoiceID'],
                             'BusinessPartnerID'=> $icount['BusinessPartnerID'],
                             'CompanyID'=> $icount['CompanyID'],
                             'BranchID'=> $icount['BranchID'],
                             'VoucherNo'=> $icount['VoucherNo'],
                             'id_hairstylist_loan_return'=> $value['id_hairstylist_loan_return'],
                             'value_detail'=> json_encode($icount),
                         ]);
                        $hsIncome->hairstylist_income_details()->updateOrCreate([
                            'source'    => "Hairstylist Loan",
                            'reference' => $value['id_hairstylist_loan_return'],
                        ],
                            [
                                'id_outlet' => $value['id_outlet'],
                                'amount'    => $value['value'],
                                'type'        => "Salary Cut",
                                'name_income' => $value['name'],
                                'value_detail'=> json_encode($value),
                            ]);
                        $list_salary_cut[] = array(
                                'list'=>$value['name'],
                                'content'=>$value['value']
                            );
                        $total_salary_cuts = $total_salary_cuts + $value['value'];
                        $price_salary_cut = $price_salary_cut + $value['value'];
                        $loan_return = HairstylistLoanReturn::where('id_hairstylist_loan_return', $value['id_hairstylist_loan_return'])
                            ->update([
                                'status_return' => "Success",
                                'date_pay'      => date('Y-m-d H:i:s'),
                            ]);
                        }else{
                            $total = $total + $value['value'];
                            $total_salary_cuts = $total_salary_cuts - $value['value'];
                            $price_salary_cut = $price_salary_cut - $value['value'];
                        }
                        }else{
                            $hsIncome->hairstylist_income_details()->updateOrCreate([
                                'source'    => "Hairstylist Loan",
                                'reference' => $value['id_hairstylist_loan_return'],
                            ],
                                [
                                    'id_outlet' => $value['id_outlet'],
                                    'amount'    => $value['value'],
                                    'type'        => "Salary Cut",
                                    'name_income' => $value['name'],
                                    'value_detail'=> json_encode($value),
                                ]);
                            $loan_return = HairstylistLoanReturn::where('id_hairstylist_loan_return', $value['id_hairstylist_loan_return'])
                                ->update([
                                    'status_return' => "Success",
                                    'date_pay'      => date('Y-m-d H:i:s'),
                                ]);
                            $list_salary_cut[] = array(
                                'list'=>$value['name'],
                                'content'=>$value['value']
                            );
                            
                        }
                    } else {
                        $total = $total + $value['value'];
                        $total_salary_cuts = $total_salary_cuts - $value['value'];
                        $price_salary_cut = $price_salary_cut - $value['value'];
                        break;
                    }
                }
                }
              
            $incomes[] = array(
                'header_title' => "Outlet",
                'header_content' => $outlet_name->outlet_name,
                'footer_title' => "Total",
                'footer_content' => $price_income,
                'contents'=>$list_income
            ); 
            $salary_cuts[] = array(
                'header_title' => "Outlet",
                'header_content' => $outlet_name->outlet_name,
                'footer_title' => "Total",
                'footer_content' => $price_salary_cut,
                'contents'=>$list_salary_cut
            ); 
        }
        if ($type == 'middle') {
        $response_income = array(
            'name' => 'Tengah Bulan',
            'icon' => 'half',
            'footer' => array(
                'title_title' => 'Penerimaan Tengah Bulan',
                'title_content' => $total_incomes,
                'subtitle_title' => 'Ditransfer',
                'subtitle_content' => date('d M Y', strtotime("$year-$month-$date")),
            ),
            'list'=>$incomes
            );
        $response_salary_cut = array(
            'name' => 'Tengah Bulan',
            'icon' => 'half',
            'footer' => array(
                'title_title' => 'Total Potongan',
                'title_content' => $total_salary_cuts,
                'subtitle_title' => null,
                'subtitle_content' => null,
            ),
            'list'=>$salary_cuts
            );
        $attendances = array(
            'name' => 'Tengah Bulan',
            'icon' => 'half',
            'footer' => null,
            'list'=>$list_attendance
            );
        }else{
         $attendances = array(
            'name' => 'Akhir Bulan',
            'icon' => 'end',
            'footer' => null,
            'list'=>$list_attendance
            );   
         $response_income = array(
            'name' => 'Akhir Bulan',
            'icon' => 'end',
            'footer' => array(
                'title_title' => 'Penerimaan Akhir Bulan',
                'title_content' => $total_incomes,
                'subtitle_title' => 'Ditransfer',
                'subtitle_content' => date('d M Y', strtotime("$year-$month-$date")),
            ),
            'list'=>$incomes
            );
         $response_salary_cut = array(
            'name' => 'Akhir Bulan',
            'icon' => 'end',
            'footer' => array(
                'title_title' => 'Total Potongan',
                'title_content' => $total_salary_cuts,
                'subtitle_title' => null,
                'subtitle_content' => null,
            ),
            'list'=>$salary_cuts
            );
        }
        $hairstylist_bank = UserHairStylist::leftjoin('bank_accounts','bank_accounts.id_bank_account','user_hair_stylist.id_bank_account')
                ->leftjoin('bank_name','bank_name.id_bank_name','bank_accounts.id_bank_name')
                ->select(
                        'id_user_hair_stylist',
                        'id_outlet',
                        'id_hairstylist_group',
                        'bank_accounts.id_bank_account',
                        'beneficiary_name',
                        'beneficiary_account',
                        'bank_name'
                        )
                ->where('id_user_hair_stylist',$hs->id_user_hair_stylist)
                ->first();
        $footer_title = 'Total diterima bulan ini setelah potongan';
        if ($type == 'end') {
         $proteksion = self::calculateGenerateIncomeProtec($hs, $startDate, $endDate);
          if($total< $proteksion['total_income']??0){
                $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Proteksi",
                        'reference' => $proteksion['name'],
                    ],
                    [
                        'id_outlet'   => $hs->id_outlet,
                        'amount'      => $proteksion['total_income'],
                        'type'        => "Incentive",
                        'name_income' => $proteksion['name'],
                        'value_detail'=> json_encode($proteksion),
                    ]);
                    $total = $proteksion['total_income'];
                    $footer_title = 'Total diterima bulan ini mendapat '.$proteksion['name'];
            }
        }
      $response = array(
            'month' => date('Y-m-d', strtotime("$year-$month-$date")),
            'type' => $type,
            'bank_name' => $hairstylist_bank->bank_name??null,
            'account_number' => $hairstylist_bank->beneficiary_account??null,
            'account_name' => $hairstylist_bank->beneficiary_name??null,
            'footer' => array(
                'footer_title' => $footer_title,
                'footer_content' => $total,
            ),
            'incomes'=>$response_income,
            'attendances'=>$attendances,
            'salary_cuts'=>$response_salary_cut,
        );
       $hsIncome->update([
            'status' => 'Pending',
            'amount' => $total,
            'value_detail'=> json_encode($response)
        ]);
        return $hsIncome;
    }
}
