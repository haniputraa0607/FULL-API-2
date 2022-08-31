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
use Modules\Recruitment\Entities\HairstylistLoanIcount;

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

    public static function calculateIncome(UserHairStylist $hs, $type = 'end')
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
        $outlet = Outlet::where('id_outlet', $hs->id_outlet)->first();
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
                ->where('is_overtime',1)
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
                ->where('not_schedule',0)
                ->wheredate('date', $value['date'])
                ->get();
            foreach ($overtimess as $va) {
                array_push($ove, $va['duration']);
            }
        }
        $jml_overtime = 0;
        foreach (array_unique($overtime) as $value) {
            $overtimess = HairstylistOverTime::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                ->wherenotnull('approve_at')
                ->wherenull('reject_at')
                ->where('not_schedule',1)
                ->wheredate('date', $value['date'])
                ->count();
            $jml_overtime += $jml_overtime+$overtimess;
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
                $trxs = TransactionProduct::where(array('transaction_product_services.id_user_hair_stylist' => $hs->id_user_hair_stylist))
                    ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
                    ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                    ->join('transaction_breakdowns', function ($join) use ($mulai, $endDate) {
                        $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                            ->whereNotNull('transaction_products.transaction_product_completed_at')
                            ->whereBetween('transaction_product_completed_at', [$mulai, $endDate]);
                    })
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
                $total = $total + $amount;
            } elseif (strpos($calculation, 'incentive_') === 0) {
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
                $formula = str_replace('value', $incentive->value, $incentive->formula);

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
                        $hsIncome->update(['notes' => $e->getMessage()]);
                    }

                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => $calculation,
                        'reference' => $incentive->id_hairstylist_group_default_insentifs,
                    ],
                        [
                            'id_outlet' => $id_outlet,
                            'amount'    => $amount,
                            'type'        => "Incentive",
                            'name_income' => $calculation,
                            'value_detail'=> json_encode($incentive),
                        ]);
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
                            'id_outlet' => $id_outlet,
                            'amount'    => $amount,
                            'type'        => "Salary Cut",
                            'name_income' => $calculation,
                            'value_detail'=> json_encode($salary_cut),
                        ]);

                }
                $total = $total - $amount;
            }
        }
        $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->groupby('id_outlet')->distinct()->get()->pluck('id_outlet');
        if ($type == 'end') {
            $startDate = date('Y-m-d', strtotime("$year-" . ($month - 1) . "-$date +1 days"));
            foreach ($id_outlets as $id_outlet) {
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
            foreach ($ove as $value) {
                    $va        = explode(":", $value['duration']);
                    $nominal   = 0;
                    $h         = $va[0];
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
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Overtime",
                        'reference' => $value['id_hairstylist_overtime'],
                    ],
                        [
                            'id_outlet' => $value['id_outlet'],
                            'amount'    => $nominal,
                        ]);
                    $total = $total + $nominal;
                }
                
        //Fixed Incentive
        $fixed = self::calculateFixedIncentive($hs, $startDate, $endDate,$outlet,$incomeDefault);
        foreach ($fixed as $value) {
            if ($value['status'] == 'incentive') {
                $total = $total + $value['value'];
                $typess = "Incentive";
            } else {
                $total = $total - $value['value'];
                $typess = "Salary Cut";
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
        //Proteksi Attendance
      $proteksi = self::calculateGenerateIncomeProteksi($hs, $startDate, $endDate, $outlet);
        foreach ($proteksi as $value) {
            $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source'    => "Proteksi Attendace",
                        'reference' => $value['id_hairstylist_group_default_proteksi_attendance'],
                    ],
                    [
                        'id_outlet'   => $outlet,
                        'amount'      => $value['value'],
                        'type'        => "Incentive",
                        'name_income' => $value['id_hairstylist_group_default_proteksi_attendance'],
                        'value_detail'=> json_encode($value),
                    ]);
            $total = $total + $value['value'];
        } 
        
        //overtime Day
        $day = self::calculateGenerateIncomeOvertimeDay($hs, $startDate, $endDate,$outlet,$jml_overtime);
        if($day['id']){
        $hsIncome->hairstylist_income_details()->updateOrCreate([
            'source'    => "Overtime Not Schedule",
            'reference' => $day['id'],
        ],
            [
                'id_outlet'   => $outlet,
                'amount'      => $day['value'],
                'type'        => "Incentive",
                'name_income' => "Overtime Not Schedule",
                'value_detail'=> json_encode($day),
            ]);
        $total = $total + $day['value']; 
        }
        
          //Lateness
        $late = self::calculateGenerateIncomeLateness($hs, $startDate, $endDate, $outlet);
        foreach ($late as $value) {
            $hsIncome->hairstylist_income_details()->updateOrCreate([
                'source'    => "Lateness Hairstylist",
                'reference' => $value['id_hairstylist_group_default_late'],
            ],
            [
                'id_outlet'   => $outlet,
                'amount'      => $value['value'],
                'type'        => "Salary Cut",
                'name_income' => "Lateness Hairstylist",
                'value_detail'=> json_encode($value),
            ]);
            $total = $total - $value['value'];
        } 
        
        
        //loan
        $loan = self::calculateLoan($hs, $startDate, $endDate);
        foreach ($loan as $value) {
            
            $total = $total - $value['value'];
            if ($total >= 0) {
                if(isset($value['type'])){
                $icount = Icount::SalesPayment($value, $value['type'], null, null);
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
                $loan_return = HairstylistLoanReturn::where('id_hairstylist_loan_return', $value['id_hairstylist_loan_return'])
                    ->update([
                        'status_return' => "Success",
                        'date_pay'      => date('Y-m-d H:i:s'),
                    ]);
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
                }
            } else {
                $total = $total + $value['value'];
                break;
            }
        }
        }
        $hsIncome->update([
            'status' => 'Pending',
            'amount' => $total,
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
        if (!$calculations) {
            throw new \Exception('No calculation for income. Check setting!');
        }
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
        $trxs  = TransactionProduct::where(array('transaction_product_services.id_user_hair_stylist' => $hs->id_user_hair_stylist))
            ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
            ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
            ->join('transaction_breakdowns', function ($join) use ($startDate, $endDate) {
                $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                    ->whereNotNull('transaction_products.transaction_product_completed_at')
                    ->whereBetween('transaction_product_completed_at', [$startDate, $endDate]);
            })
            ->where('transaction_product_services.service_status', 'Completed')
            ->wherenotnull('transaction_product_services.completed_at')
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
            $date3            = date_create($clock_in_requirement);
            $date4            = date_create($clock_in);
            $diff             = date_diff($date3, $date4);
            $minute = $diff->h * 60 + $diff->i;
           $data[] = array(
               'attendance_date'=>$value['attendance_date'],
               'clock_in_requirement'=>$clock_in_requirement,
               'clock_in'=>$clock_in,
               'minute'=>$minute,
           );
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
            $date3            = date_create($clock_in_requirement);
            $date4            = date_create($clock_in);
            $diff             = date_diff($date3, $date4);
            $minute = $diff->h * 60 + $diff->i;
           
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
                                'id_hairstylist_group_default_late'=>$value['id_hairstylist_group_default_late'],
                                'id_hairstylist_schedule_date'=>$value['id_hairstylist_schedule_date'],
                                'attendance_date'=>$value['attendance_date'],
                                'clock_in_requirement'=>$clock_in_requirement,
                                'clock_in'=>$clock_in,
                                'minute'=>$minute,
                                'range'=>$valu['range'],
                                'nominal'=>$nominals
                            );
                    $array[] = array(
                        'id_hairstylist_group_default_late'=>$value['id_hairstylist_group_default_late'],
                        "name"  => "Lateness Hairstylist",
                        "value" => $nominals,
                        "data" => $data,
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
        $data = array();
        foreach ($ar as $value) {
        $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$value['start'], $value['end']])
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
                    hairstylist_group_proteksi_attendances.amount_day IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount_day ELSE hairstylist_group_default_proteksi_attendances.amount_day
                    END as amount_day
                 '),
                )->first();
            $nominals = 0;
            if($total_attend>0){
                if($total_attend>=$incentive->value){
                    $nominals = $incentive->amount;
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
//                'nominals'=>$nominals,
//            );
        }
//        return $data;
        $array[] = array(
            "name"  => "Proteksi Attendance",
            "value" => $nominal,

        );
        return $array;
        
    }
     public static function calculateGenerateIncomeProteksi(UserHairStylist $hs, $startDate, $endDate, $id_outlet)
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
        foreach ($ar as $value) {
        $total_attend = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs, $id_outlet) {
                $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                    ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                    ->where('id_outlet', $id_outlet);
            })
                ->whereNotNull('clock_in')
                ->whereBetween('hairstylist_attendances.attendance_date', [$value['start'], $value['end']])
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
                    hairstylist_group_proteksi_attendances.amount_day IS NOT NULL THEN hairstylist_group_proteksi_attendances.amount_day ELSE hairstylist_group_default_proteksi_attendances.amount_day
                    END as amount_day
                 '),
                )->first();
            $nominals = 0;
            if($total_attend>0){
                if($total_attend>=$incentive->value){
                    $nominals = $incentive->amount;
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
        }
        return $array;
        
    }
    public static function calculateFixedIncentive(UserHairStylist $hs, $startDate, $endDate, $outlet, $incomeDefault)
    {
        $array   = array();
        $tanggal = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value') ?? 25;
        // Variable that store the date interval
        // of period 1 day
        $interval = new DateInterval('P1D');

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
                "id_hairstylist_group_default_fixed_incentive" => $va['id_hairstylist_group_default_fixed_incentive'],
                "name"                                         => $va['name_fixed_incentive'],
                "value"                                        => $harga,
                'status'                                       => $va['status'],
                'id_outlet'                                    => $outlet['id_outlet'],
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
            if(isset($value['type'])){
                    $array[] = array(
                        "name"                       => $value['name_category_loan'],
                        "value"                      => $value['amount_return'],
                        "id_outlet"                  => $hs->id_outlet,
                        "id_hairstylist_loan_return" => $value['id_hairstylist_loan_return'],
                        "SalesInvoiceID"             => $value['SalesInvoiceID'],
                        "amount_return"              => $value['amount_return'],
                        "name_category_loan"         => $value['name_category_loan'],
                        "type"                       => $value['type']      
                    );
            }else{
                $array[] = array(
                    "name"                       => $value['name_category_loan'],
                    "value"                      => $value['amount_return'],
                    "id_outlet"                  => $hs->id_outlet,
                    "id_hairstylist_loan_return" => $value['id_hairstylist_loan_return'],
                );
            }
        }
        return $array;
    }
}
