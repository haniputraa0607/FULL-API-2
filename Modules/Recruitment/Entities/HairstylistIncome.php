<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Lib\MyHelper;
use App\Http\Models\TransactionProduct;
use DB;
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
        foreach  ($calculations as $calculation) {
            if ($calculation == 'product_commission') {
                $trxs = TransactionProduct::select('transaction_products.id_transaction', 'transaction_products.id_transaction_product', 'transaction_breakdowns.*')
                    ->join('transaction_breakdowns', function($join) use ($startDate, $endDate) {
                        $join->on('transaction_breakdowns.id_transaction_product', 'transaction_products.id_transaction_product')
                            ->whereNotNull('transaction_products.transaction_product_completed_at')
                            ->whereDate('transaction_product_completed_at', '>=', $startDate)
                            ->whereDate('transaction_product_completed_at', '>=', $endDate);
                    })
                    ->where('transaction_breakdowns.type', 'fee_hs')
                    ->with('transaction')
                    ->get();
                $trxs->each(function ($item) use ($hsIncome, $calculation) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => $calculation,
                        'reference' => $item->id_transaction_product,
                    ],
                    [
                        'id_outlet' => $item->transaction->id_outlet,
                        'amount' => $item->value,
                    ]);
                     $total = $total+$item->value;
                });
            } elseif (strpos($calculation, 'incentive_') === 0) { // start_with_calculation
                $code = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function($join) use ($hs) {
                                $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                                ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
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
                    $total_attend = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                        ->where('id_outlet', $id_outlet)
                        ->whereBetween('attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                        ->where('id_outlet', $id_outlet)
                        ->where('is_on_time', 0)
                        ->whereBetween('attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absent = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereNull('clock_out')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absent' => $total_absent,
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
                        ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
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
                    $total_attend = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                        ->where('id_outlet', $id_outlet)
                        ->whereBetween('attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_late = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                        ->where('id_outlet', $id_outlet)
                        ->where('is_on_time', 0)
                        ->whereBetween('attendance_date',[$startDate,$endDate])
                        ->count();
                    $total_absent = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) use ($hs,$id_outlet){
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereNull('clock_out')
                        ->whereBetween('hairstylist_attendances.attendance_date',[$startDate,$endDate])
                        ->count();
                        
                    try {
                        $amount = MyHelper::calculator($formula, [
                            'total_attend' => $total_attend,
                            'total_late' => $total_late,
                            'total_absent' => $total_absent,
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

        $hsIncome->update([
            'status' => 'Pending',
            'amount' => $total,
        ]);

        return $hsIncome;
    }
}