<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Lib\MyHelper;

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

    public function calculateIncome(UserHairStylist $hs, $type = 'end')
    {
        if ($type == 'middle') {
            $date = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value', 10);
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value', '[]'), true) ?? [];
        } else {
            $type = 'end';
            $date = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value', 25);
            $calculations = json_decode(MyHelper::setting('hs_income_calculation_end', 'value', '[]'), true) ?? [];
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

        $exists = static::whereDate('periode', "$year-$month-$date")->where('type', $type)->where('status', '<>', 'Draft')->exists();
        if ($exists) {
            throw new \Exception("Hairstylist income for periode $type $month/$year already exists");
        }

        $lastDate = static::orderBy('end_date', 'desc')->whereDate('end_date', '<', date('Y-m-d'))->where('status', '<>', 'Cancelled')->first();
        if ($lastDate) {
            $startDate = date('Y-m-d', strtotime($lastDate->end_date . '+1 days'));
        } else {
            $startDate = date('Y-m-d', strtotime("$year-" . ($month - 1) . "-$date +1 days"));
            if (date('m', strtotime($startDate)) != ($month - 1)) {
                $startDate = date('Y-m-d', ("$year-$month-01 -1 days"));
            }
        }

        $endDate = date('Y-m-d', strtotime("$year-" . $month . "-$date"));
        if (date('m', strtotime($endDate)) != $month) {
            $endDate = date('Y-m-d', ("$year-" . ($month + 1) . "-01 -1 days"));
        }

        $hsIncome = static::updateOrCeate([
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
                $trxs->each(function ($item) use ($hsIncome) {
                    $hsIncome->hairstylist_income_details()->updateOrCreate([
                        'source' => $calculation,
                        'reference' => $item->id_transaction_breakdown,
                    ],
                    [
                        'id_outlet' => $item->transaction->id_outlet,
                        'amount' => $item->value,
                    ]);
                });
            } elseif (strpos($calculation, 'incentive_') === 0) { // start_with_calculation
                $code = str_replace('incentive_', '', $calculation);
                $incentive = HairstylistGroupInsentifDefault::leftJoin('hairstylist_group_insentifs', function($join) use ($hs) {
                    $join->on('hairstylist_group_insentifs.id_hairstylist_group_default_insentifs', 'hairstylist_group_default_insentifs.id_hairstylist_group_default_insentifs')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
                })->where('code', $code)->first();
                if (!$incentive) {
                    continue;
                }

                $formula = $incentive->formula;

                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                        ->where('id_outlet', $id_outlet)
                        ->count();
                    $total_late = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                        ->where('id_outlet', $id_outlet)
                        ->where('is_on_time', 0)
                        ->count();
                    $total_absent = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) {
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereNull('clock_out')
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
                        'reference' => $hsIncome->id_hairstylist_income . '#' . $id_outlet,
                    ],
                    [
                        'id_outlet' => $id_outlet,
                        'amount' => $amount,
                    ]);
                }
            } elseif (strpos($calculation, 'salary_cut_') === 0) { // start_with_calculation
                $code = str_replace('salary_cut_', '', $calculation);
                $salary_cut = HairstylistGroupPotonganDefault::leftJoin('hairstylist_group_potongans', function($join) use ($hs) {
                    $join->on('hairstylist_group_potongans.id_hairstylist_group_default_potongans', 'hairstylist_group_default_potongans.id_hairstylist_group_default_potongans')
                        ->where('id_hairstylist_group', $hs->id_hairstylist_groups);
                })->where('code', $code)->first();
                if (!$salary_cut) {
                    continue;
                }

                $formula = $salary_cut->formula;

                $id_outlets = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)->get()->pluck('id_outlet');
                foreach ($id_outlets as $id_outlet) {
                    $total_attend = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                        ->where('id_outlet', $id_outlet)
                        ->count();
                    $total_late = HairstylistAttendance::where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                        ->where('id_outlet', $id_outlet)
                        ->where('is_on_time', 0)
                        ->count();
                    $total_absent = HairstylistScheduleDate::leftJoin('hairstylist_attendances', function ($join) {
                            $join->on('hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
                                ->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
                                ->where('id_outlet', $id_outlet);
                        })
                        ->whereNull('clock_in')
                        ->whereNull('clock_out')
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
                        'reference' => $hsIncome->id_hairstylist_income . '#' . $id_outlet,
                    ],
                    [
                        'id_outlet' => $id_outlet,
                        'amount' => $amount,
                    ]);
                }
            }
        }

        return $hsIncome;
    }
}