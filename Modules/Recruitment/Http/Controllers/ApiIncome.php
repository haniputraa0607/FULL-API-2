<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;

use Modules\Franchise\Entities\TransactionProduct;
use Modules\Outlet\Entities\OutletTimeShift;

use Modules\Recruitment\Entities\HairstylistLogBalance;
use Modules\Recruitment\Entities\OutletCash;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistInbox;
use Modules\Recruitment\Entities\HairstylistAttendance;

use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\UserRating\Entities\UserRatingSummary;
use App\Http\Models\Transaction;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Entities\OutletCashAttachment;

use App\Lib\MyHelper;
use DB;
use DateTime;
use DateTimeZone;
use Modules\Users\Http\Requests\users_forgot;
use Modules\Users\Http\Requests\users_phone_pin_new_v2;
use PharIo\Manifest\EmailTest;
use Auth;
use Modules\Recruitment\Http\Requests\Income;
use Modules\Recruitment\Entities\HairstylistIncome;
use Config;
use Modules\Recruitment\Http\Requests\Export_Outlet;
use Modules\Transaction\Entities\TransactionBreakdown;
use Modules\Recruitment\Entities\HairstylistOverTime;
use Modules\Recruitment\Entities\HairstylistLoan;
use Modules\Recruitment\Entities\HairstylistGroupProteksi;
use Modules\Recruitment\Entities\HairStylistTimeOff;
use Modules\Recruitment\Entities\HairstylistIncomeDetail;
use Modules\Recruitment\Entities\HairstylistGroupFixedIncentive;
use Modules\Recruitment\Entities\HairstylistGroupFixedIncentiveDefault;
use Modules\Recruitment\Entities\HairstylistGroupProteksiAttendanceDefault;
use Modules\Recruitment\Entities\HairstylistGroupOvertimeDayDefault;
use Modules\Recruitment\Entities\HairstylistGroupOvertimeDefault;
use Modules\Recruitment\Entities\HairstylistGroupLateDefault;
use Modules\Recruitment\Entities\HairstylistLoanReturn;
use Modules\Recruitment\Entities\HairstylistPayrollQueue;
use App\Jobs\PayrollHs;

class ApiIncome extends Controller
{

    public function income(Income $request){
        $hs = $request->user()->id_user_hair_stylist;
        $date = $request->month;
        $schedule = $this->schedule($date,$hs);
        $schedule_akhir = $this->schedule_akhir($date,$hs);
        $attandance = array(
            'Tengah Bulan'=>$schedule,
            'Akhir Bulan'=>$schedule_akhir,
        );
        Return $attandance;
       
    }
    public function generate() {
      $periode = array(
           '2022-09',
           '2022-10',
           '2022-11',
           '2022-12',
       );
      $array = array();
      foreach($periode as $key){
            $mid         = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value');
           $end         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value');
           $start = date('Y-m-'.$end,strtotime($key.'+1 day'.'-1 month'));
         $array[]=array(
             'startDate'=>date('Y-m-d',strtotime($start.'+1 day')),
             'endDate'=>date('Y-m-'.$mid,strtotime($key)),
             'start'=>date('Y-m-01',strtotime($key)),
             'end'=>date('Y-m-t',strtotime($key)),
             'type' => 'middle',
             'key'=>$key
         );
         $start = date('Y-m-'.$mid,strtotime($key.'+1 day'));
         $array[]=array(
             'startDate'=>date('Y-m-d',strtotime($start.'+1 day')),
             'endDate'=>date('Y-m-'.$end,strtotime($key)),
             'start'=>date('Y-m-01',strtotime($key)),
             'end'=>date('Y-m-t',strtotime($key)),
             'type' => 'end',
             'key'=>$key
         );
      }
       foreach ($array as $value) {
           if($value['type']=='middle'){
              $date         = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value');    
                $calculations = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? null;
                if ($calculations) {
                    $type = 'middle';
                    $year = date('Y',strtotime($value['key']));
                     if ($date >= date('d')) {
                         $month = (int) date('m',strtotime($value['key'])) - 1;
                         if (!$month) {
                             $month = 12;
                             $year -= 1;
                         }
                     } else {
                         $month = (int) date('m',strtotime($value['key']));
                     }
                     $exists = HairstylistPayrollQueue::where([
                         'month'=>$month,
                         'year'=>$year,
                         'type'=>'middle'
                     ])->exists();
                     if (!$exists) {
                         $create = HairstylistPayrollQueue::create([
                         'month'=>$month,
                            'year'=>$year,
                         'type'=>'middle',
                         'status_export'=>'Running',
                         'message'=>"Cron Income HS middle month"
                     ]);
                        if($create){
                            PayrollHs::dispatch($create)->allOnConnection('database');
                         } 
                     }
                }
           }else{
                $date         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value');    
                $calculations = json_decode(MyHelper::setting('hs_income_calculation_end', 'value_text', '[]'), true) ?? null;
                if ($calculations) {
                    $type = 'end';
                    $year = date('Y',strtotime($value['key']));
                     if ($date >= date('d')) {
                         $month = (int) date('m',strtotime($value['key'])) - 1;
                         if (!$month) {
                             $month = 12;
                             $year -= 1;
                         }
                     } else {
                         $month = (int) date('m',strtotime($value['key']));
                     }
                     $exists = HairstylistPayrollQueue::where([
                         'month'=>$month,
                         'year'=>$year,
                         'type'=>'end'
                     ])->exists();
                     if (!$exists) {
                        $create = HairstylistPayrollQueue::create([
                         'month'=>$month,
                         'year'=>$year,
                         'type'=>'end',
                         'status_export'=>'Running',
                         'message'=>"Cron Income HS end month"
                     ]);
                        if($create){
                            PayrollHs::dispatch($create)->allOnConnection('database');
                         } 
                     }
                }
           }
       }
        return array('Success');
    }
   
    public function generate2() {
      $periode = array(
           '2022-10',
           '2022-11',
           '2022-12',
       );
      $array = array();
      foreach($periode as $key){
            $mid         = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value');
           $end         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value');
           $start = date('Y-m-'.$end,strtotime($key.'+1 day'.'-1 month'));
         $array[]=array(
             'startDate'=>date('Y-m-d',strtotime($start.'+1 day')),
             'endDate'=>date('Y-m-'.$mid,strtotime($key)),
             'start'=>date('Y-m-01',strtotime($key)),
             'end'=>date('Y-m-t',strtotime($key)),
             'type' => 'middle',
             'key'=>$key
         );
         $start = date('Y-m-'.$mid,strtotime($key.'+1 day'));
         $array[]=array(
             'startDate'=>date('Y-m-d',strtotime($start.'+1 day')),
             'endDate'=>date('Y-m-'.$end,strtotime($key)),
             'start'=>date('Y-m-01',strtotime($key)),
             'end'=>date('Y-m-t',strtotime($key)),
             'type' => 'end',
             'key'=>$key
         );
      }
      $b = array();
       $hs = UserHairStylist::where('user_hair_stylist_status','Active')->get();
       foreach ($array as $value) {
           $startDate = $value['startDate'];
           $endDate = $value['endDate'];
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
           foreach ($hs as $va) {
            $list_attendance = array();
            $incomes = array();
            $salary_cuts = array();
            $total_incomes = 0;
            $total_salary_cuts = 0;
            $data = HairstylistIncome::where('id_user_hair_stylist',$va['id_user_hair_stylist'])->whereDate('periode', '>=', $value['start'])
                        ->whereDate('periode', '<=',$value['end'])->first();
             if($data){
                 if(empty($data->value_detail)){
                $income_detail_outlet = HairstylistIncomeDetail::where('id_hairstylist_income',$data->id_hairstylist_income)->groupby('id_outlet')->select('id_outlet')->get();
                 foreach ($income_detail_outlet as $vas) {
                    $outlet_name = Outlet::where('id_outlet',$vas['id_outlet'])->first();
                    $income_detail = HairstylistIncomeDetail::where('id_hairstylist_income',$data->id_hairstylist_income)
                                        ->where('id_outlet',$vas['id_outlet'])
                                        ->get();
                    $list_income = array();
                    $list_salary_cut = array();
                    $price_salary_cut = 0;
                    $price_income = 0;
                    $total_attend = $all_attends[$va['id_user_hair_stylist']][$vas['id_outlet']]['total'] ?? '0';
                    $total_late = $all_lates[$va['id_user_hair_stylist']][$vas['id_outlet']]['total'] ?? '0';
                    $total_absen = $all_absens[$va['id_user_hair_stylist']][$vas['id_outlet']]['total'] ?? '0';
                    $total_overtimes = $all_overtimes[$va['id_user_hair_stylist']][$vas['id_outlet']] ?? '0';
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
                 foreach($income_detail as $v){
                     if($v['source']=='product_commission'){
                         $v['type']="Incentive";
                         $v['name_income']="Product Comission";
                         $v->save();
                          $list_income[] = array(
                                'list'=>"Product Commission",
                                'content'=>$v['amount']
                            );
                            $total_incomes = $total_incomes + $v['amount'];
                            $price_income = $price_income + $v['amount'];
                     }elseif(strpos($v['source'], 'incentive_') === 0){
                         $code = str_replace('incentive_', '', $v['source']);
                         $v['type']="Incentive";
                         $v['name_income']=ucfirst(str_replace('_', ' ', $code));
                         $v->save();
                         $list_income[] = array(
                                'list'=>ucfirst(str_replace('_', ' ', $code)),
                                'content'=>$v['amount']
                            );
                            $total_incomes = $total_incomes + $v['amount'];
                            $price_income = $price_income + $v['amount'];
                     }elseif(strpos($v['source'], 'salary_cut_') === 0){
                         $code      = str_replace('incentive_', '', $v['source']);
                         $v['type']="Salary Cut";
                         $v['name_income']=ucfirst(str_replace('_', ' ', $code));
                         $v->save();
                         $list_salary_cut[] = array(
                                'list'=>ucfirst(str_replace('_', ' ', $code)),
                                'content'=>$v['amount']
                            );
                        $total_salary_cuts = $total_salary_cuts + $v['amount'];
                        $price_salary_cut = $price_salary_cut + $v['amount'];
                     }elseif($v['source']=='Fixed Incentive'){
                       $fixed = HairstylistGroupFixedIncentiveDefault::where('id_hairstylist_group_default_fixed_incentive',$v['reference'])->first();
                       if($fixed){
                        if($fixed['status']=='incentive'){
                            $v['type']="Incentive";
                            $v['name_income']=$fixed['name_fixed_incentive'];
                            $v->save();
                            $list_income[] = array(
                                'list'=>$fixed['name_fixed_incentive'],
                                'content'=>$v['amount']
                            );
                            $total_incomes = $total_incomes + $v['amount'];
                            $price_income = $price_income + $v['amount'];
                        }else{
                            $v['type']="Salary Cut";
                            $v['name_income']=$fixed['name_fixed_incentive'];
                            $v->save();
                            $list_salary_cut[] = array(
                                'list'=>$fixed['name_fixed_incentive'],
                                'content'=>$v['amount']
                            );
                            $total_salary_cuts = $total_salary_cuts + $v['amount'];
                            $price_salary_cut = $price_salary_cut + $v['amount'];
                        }
                       }else{
                           $v['type']="Incentive";
                            $v['name_income']="Fixed Incentive";
                            $v->save();
                            $list_income[] = array(
                                'list'=>"Fixed Incentive",
                                'content'=>$v['amount']
                            );
                            $total_incomes = $total_incomes + $v['amount'];
                            $price_income = $price_income + $v['amount'];
                       } 
                       
                     }elseif($v['source']=='Proteksi Attendance'){
                        $v['type']="Incentive";
                        $v['name_income']='Proteksi Attendance';
                        $v->save();
                        $list_income[] = array(
                                'list'=>'Proteksi Attendance',
                                'content'=>$v['amount']
                            );
                        $total_incomes = $total_incomes + $v['amount'];
                        $price_income = $price_income + $v['amount'];
                     }elseif($v['source']=='Overtime Not Schedule'){
                        $v['type']="Incentive";
                        $v['name_income']='Overtime Not Schedule';
                        $v->save();
                        $list_income[] = array(
                                'list'=>'Overtime Not Schedule',
                                'content'=>$v['amount']
                            );
                        $total_incomes = $total_incomes + $v['amount'];
                        $price_income = $price_income + $v['amount'];
                     }elseif($v['source']=='Overtime'){
                         $v['type']="Incentive";
                        $v['name_income']='Overtime';
                        $v->save();
                        $list_income[] = array(
                                'list'=>'Overtime',
                                'content'=>$v['amount']
                            );
                        $total_incomes = $total_incomes + $v['amount'];
                        $price_income = $price_income + $v['amount'];
                     }elseif($v['source']=='Lateness Hairstylist'){
                        $v['type']="Salary Cut";
                        $v['name_income']="Keterlambatan";
                        $v->save();
                        $list_salary_cut[] = array(
                                'list'=>"Keterlambatan",
                                'content'=>$v['amount']
                            );
                            $total_salary_cuts = $total_salary_cuts + $v['amount'];
                            $price_salary_cut = $price_salary_cut + $v['amount'];
                     }elseif($v['source']=='Hairstylist Loan'){
                         $loan = HairstylistLoan::join('hairstylist_category_loans', 'hairstylist_category_loans.id_hairstylist_category_loan', 'hairstylist_loans.id_hairstylist_category_loan')
                            ->join('hairstylist_loan_returns', function ($join) use ($v) {
                                $join->on('hairstylist_loan_returns.id_hairstylist_loan', 'hairstylist_loans.id_hairstylist_loan')
                                    ->where('hairstylist_loan_returns.id_hairstylist_loan_return', $v['reference']);
                            })
                            ->first();
                            if($loan){
                                $v['type']="Salary Cut";
                                $v['name_income']=$loan->name_category_loan;
                                $v->save();
                                $list_salary_cut[] = array(
                                'list'=>$loan->name_category_loan,
                                'content'=>$v['amount']
                            );
                            }else{
                                $v['type']="Salary Cut";
                                $v['name_income']='Hairstylist Loan';
                                $v->save();
                                $list_salary_cut[] = array(
                                        'list'=>'Hairstylist Loan',
                                        'content'=>$v['amount']
                                    );
                            }
                        
                        $total_salary_cuts = $total_salary_cuts + $v['amount'];
                        $price_salary_cut = $price_salary_cut + $v['amount'];
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
                if ($data->type == 'middle') {
                $response_income = array(
                    'name' => 'Tengah Bulan',
                    'icon' => 'half',
                    'footer' => array(
                        'title_title' => 'Penerimaan Tengah Bulan',
                        'title_content' => $total_incomes,
                        'subtitle_title' => 'Ditransfer',
                        'subtitle_content' => date('d M Y', strtotime($data['periode'])),
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
                        'subtitle_content' => date('d M Y', strtotime($data['periode'])),
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
                ->where('id_user_hair_stylist',$data->id_user_hair_stylist)
                ->first();
                $response = array(
                     'month' => date('Y-m-d', strtotime($data['periode'])),
                     'type' => $data->type,
                     'bank_name' => $hairstylist_bank->bank_name??null,
                     'account_number' => $hairstylist_bank->beneficiary_account??null,
                     'account_name' => $hairstylist_bank->beneficiary_name??null,
                     'footer' => array(
                         'footer_title' => 'Total diterima bulan ini setelah potongan',
                         'footer_content' => $data->amount??0,
                     ),
                     'incomes'=>$response_income,
                     'attendances'=>$attendances,
                     'salary_cuts'=>$response_salary_cut,
                 );
                $data->value_detail = json_encode($response);
                $data->save();
             }
             }
           }
       }
        return array('Success');
    }
    public function generatePayroll($data) {
        $log = MyHelper::logCron($data['message']);
        try {
        $hs = HairstylistSchedule::where(array(
                'schedule_month'=>$data['month'],
                'schedule_year'=>$data['year']
               ))
                ->groupby('id_user_hair_stylist')
                ->select('id_user_hair_stylist')
                ->get();
        foreach ($hs as $value) {
          $income = $this->schedule_income($value['id_user_hair_stylist'], $data['type'],$data);
        }
        $log->success('success');
        return MyHelper::checkGet($income);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }
    public function cron_middle() {
//        $log = MyHelper::logCron('Cron Income HS middle month');
        try {
        $date         = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value');    
        $calculations = json_decode(MyHelper::setting('hs_income_calculation_mid', 'value_text', '[]'), true) ?? null;
        if (!$calculations) {
            throw new \Exception('No calculation for current periode. Check setting!');
        }
        $type = 'middle';
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
        $exists = HairstylistPayrollQueue::where([
            'month'=>$month,
             'year'=>$year,
            'type'=>'middle'
        ])->exists();
        if ($exists) {
            throw new \Exception("Hairstylist income for periode $type $month/$year already exists for $hs->id_user_hair_stylist");
        }
       $create = HairstylistPayrollQueue::create([
            'month'=>$month,
             'year'=>$year,
            'type'=>'middle',
            'status_export'=>'Running',
            'message'=>"Cron Income HS middle month"
        ]);
       if($create){
           PayrollHs::dispatch($create)->allOnConnection('database');
        }
//        $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
//            $log->fail($e->getMessage());
        }
    }
    public function cron_end() {
//        $log = MyHelper::logCron('Cron Income HS end month');
       try {
           $type         = 'end';
            $date         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value');
           $type = 'middle';
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
        $exists = HairstylistPayrollQueue::where([
            'month'=>$month,
             'year'=>$year,
            'type'=>'end',
        ])->exists();
        if ($exists) {
            throw new \Exception("Hairstylist income for periode $type $month/$year already exists for $hs->id_user_hair_stylist");
        }
        $create = HairstylistPayrollQueue::create([
            'month'=>$month,
             'year'=>$year,
            'type'=>'end',
            'status_export'=>'Running',
            'message'=>"Cron Income HS end month"
        ]);
        if($create){
           PayrollHs::dispatch($create)->allOnConnection('database');
        }
//        $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
//            $log->fail($e->getMessage());
        }
    }
    public function export_periode($request) {
          $startDate = $request['start_date'];
          $endDate   = $request['end_date'];
          $date_end         = (int) MyHelper::setting('hs_income_cut_off_mid_date', 'value')??null;
          $cek_end         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value')??null;
          if($cek_end){
              $date_end = $cek_end;
          }
         $end_date = date('Y-m-'.$date_end, strtotime($startDate));
          if(!$date_end){
              return array();
          }
          $ar = array();
          $s = 2;
          for($i=1;$i<$s;$i){
              if($startDate>=$end_date){
               $end_date = date('Y-m-d', strtotime($end_date.'+1 months'));
              }
              if($end_date>=$endDate){
                  $end_date = $endDate;
              }
              $ar[]= array(
                  'start'=>$startDate,
                  'end'=>$end_date,
              );
              
              if($end_date>=$endDate){
                  break;
              }
              $startDate = date('Y-m-d', strtotime($end_date.'+1 days'));
          }
          $array = array();
          foreach ($ar as $value) {
            $req = array(
                  'id_outlet'=>$request['id_outlet'],
                  'start_date'=>$value['start'],
                  'end_date'=>$value['end'],
              );
            if($request['type_export']== "Combine"){
                $data = $this->export_income2($req);
            }else{
                $data = $this->export_income($req);
            }
              if(isset($data['status'])&& $data['status']=='fail'){
                  continue;
              }else{
                  $array[] = $data;
              }
          }
          return MyHelper::checkGet($array);
    }
    public function schedule_income($id,$type = 'end',$queue) {
       $b = new HairstylistIncome();
       $hs = UserHairStylist::where('id_user_hair_stylist',$id)->first();
       $bro = $b->calculateIncome($hs, $type,$queue);
       return $bro;
    }
    public function schedule($date,$hs) {
        $tanggal = Setting::where('key','attendances_date')->first();
        if($tanggal){
            $value = json_decode($tanggal->value_text);
            $bulan_awal = date('Y-m-d', strtotime(date($date.'-'.$value->start) . '- 1 month'));
              $bulan_tengah = date('Y-m-d', strtotime(date($date.'-'.$value->start) . '+'.$value->middle.'day'. '- 1 month'));
            $bulan_akhir = date('Y-m-d', strtotime(date($date.'-'.$value->end)));
        }else{
            $bulan_awal = date('Y-m-d', strtotime(date($date)));
            $bulan_akhir = date('Y-m-d', strtotime(date($date) . '+. 15 day'));
//            $bulan_akhir = date('Y-m-d', strtotime(date($date) . '+ 1 month'. '- 1 day'));
        }
        return array(
            $bulan_awal,$bulan_tengah,$bulan_akhir
        );
        $date = explode('-', $bulan_awal);
        $month = $date[1]??null;
        $year = $date[0]??null;
        $date1 = explode('-', $bulan_akhir);
        $month1 = $date1[1]??null;
        $year1 = $date1[0]??null;
        $shedule = array();
        $data = array();
        $data_schedule_outlet = array();
        $schedule_outlet_data = array();
        $schedule_outlet = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.id_outlet','outlet_name'])
                ->where(array('id_user_hair_stylist'=>$hs,'schedule_month'=>$month,'schedule_year'=>$year))
                ->distinct()
                ->get();
        foreach ($schedule_outlet as $value) {
             array_push($schedule_outlet_data,$value);
        }
        
        $schedule_outlet = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.id_outlet','outlet_name'])
                ->where(array('id_user_hair_stylist'=>$hs,'schedule_month'=>$month1,'schedule_year'=>$year1))
                ->distinct()
                ->get();
        foreach ($schedule_outlet as $value) {
             array_push($schedule_outlet_data,$value);
        }
        $schedule_outlet = array_unique($schedule_outlet_data);
       foreach ($schedule_outlet as $values) {
            $kehadiran = 0;
            $terlambat = 0;
            $tidak_hadir = 0;
            $jml_jadwal = 0;
           $data_schedules = array();
           $schedule = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.*'])
                ->where(array('hairstylist_schedules.id_outlet'=>$values['id_outlet'],'id_user_hair_stylist'=>$hs,'schedule_month'=>$month,'schedule_year'=>$year))
                ->get();
        foreach ($schedule as $value) {
            $schedule_date = HairstylistScheduleDate::where(array('id_hairstylist_schedule'=>$value['id_hairstylist_schedule']))
                             ->wherebetween('date',[$bulan_awal,$bulan_akhir])
                             ->get();
                     foreach ($schedule_date as $va) {
                        $jml_jadwal++;
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->count();
                        $absensi = array();
                        if($attendance != 0){
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->get();
                        foreach ($attendance as $v) {
                            array_push($absensi,$v);
                            $kehadiran++;
                            if($v['is_on_time'] == 0){
                                $terlambat++;
                            }
                        }
                        }else{
                           $tidak_hadir++;
                        }
                        $data_value = array(
                            'data_schedules'=>$va,
                            'attendance'=>$absensi
                        );
                         array_push($data_schedules,$data_value);
                     }
                     
        }
        $schedule1 = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                    ->select(['hairstylist_schedules.*'])
                    ->where(array('hairstylist_schedules.id_outlet'=>$values['id_outlet'],'id_user_hair_stylist'=>$hs,'schedule_month'=>$month1,'schedule_year'=>$year1))
                    ->get();
        foreach ($schedule1 as $value) {
            $schedule_date = HairstylistScheduleDate::where(array('id_hairstylist_schedule'=>$value['id_hairstylist_schedule']))
                             ->wherebetween('date',[$bulan_awal,$bulan_akhir])
                             ->get();
            foreach ($schedule_date as $va) {
                        $jml_jadwal++;
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->count();
                        $absensi = array();
                        if($attendance != 0){
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->get();
                        foreach ($attendance as $v) {
                            array_push($absensi,$v);
                            $kehadiran++;
                            if($v['is_on_time'] == 0){
                                $terlambat++;
                            }
                        }
                        }else{
                           $tidak_hadir++;
                        }
                          $data_value = array(
                            'data_schedules'=>$va,
                            'attendance'=>$absensi
                        );
                         array_push($data_schedules,$data_value);
                     }
                     
        }
        $data_outlet = array(
            'id_outlet'=>$values['id_outlet'],
            'outlet_name'=>$values['outlet_name'],
//            'schedule_date'=> $data_schedules,
            'jadwal'=> $jml_jadwal,
            'kehadiran'=> $kehadiran,
            'terlambat'=> $terlambat,
            'tidak_hadir'=>$tidak_hadir
        );
        array_push($data_schedule_outlet,$data_outlet);
    }
    return $data_schedule_outlet;
    }
    public function schedule_akhir($date,$hs) {
        $tanggal = Setting::where('key','attendances_date')->first();
        if($tanggal){
//            $bulan_awal = date('Y-m-d', strtotime(date($date.'-'.$tanggal->value) . '- 1 month'));
              $bulan_awal = date('Y-m-d', strtotime(date($date.'-'.$tanggal->value) . '+ 16 day'. '- 1 month'));
            $bulan_akhir = date('Y-m-d', strtotime(date($date.'-'.$tanggal->value_text)));
        }else{
//            $bulan_awal = date('Y-m-d', strtotime(date($date)));
            $bulan_awal = date('Y-m-d', strtotime(date($date) . '+ 16 day'));
            $bulan_akhir = date('Y-m-d', strtotime(date($date) . '+ 1 month'. '- 1 day'));
        }
        $date = explode('-', $bulan_awal);
        $month = $date[1]??null;
        $year = $date[0]??null;
        $date1 = explode('-', $bulan_akhir);
        $month1 = $date1[1]??null;
        $year1 = $date1[0]??null;
        $shedule = array();
        $data = array();
        $data_schedule_outlet = array();
        $schedule_outlet_data = array();
        $schedule_outlet = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.id_outlet','outlet_name'])
                ->where(array('id_user_hair_stylist'=>$hs,'schedule_month'=>$month,'schedule_year'=>$year))
                ->distinct()
                ->get();
        foreach ($schedule_outlet as $value) {
             array_push($schedule_outlet_data,$value);
        }
        
        $schedule_outlet = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.id_outlet','outlet_name'])
                ->where(array('id_user_hair_stylist'=>$hs,'schedule_month'=>$month1,'schedule_year'=>$year1))
                ->distinct()
                ->get();
        foreach ($schedule_outlet as $value) {
             array_push($schedule_outlet_data,$value);
        }
        $schedule_outlet = array_unique($schedule_outlet_data);
       foreach ($schedule_outlet as $values) {
            $kehadiran = 0;
            $terlambat = 0;
            $tidak_hadir = 0;
            $jml_jadwal = 0;
           $data_schedules = array();
           $schedule = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                ->select(['hairstylist_schedules.*'])
                ->where(array('hairstylist_schedules.id_outlet'=>$values['id_outlet'],'id_user_hair_stylist'=>$hs,'schedule_month'=>$month,'schedule_year'=>$year))
                ->get();
        foreach ($schedule as $value) {
            $schedule_date = HairstylistScheduleDate::where(array('id_hairstylist_schedule'=>$value['id_hairstylist_schedule']))
                             ->wherebetween('date',[$bulan_awal,$bulan_akhir])
                             ->get();
            
                     foreach ($schedule_date as $va) {
                        $jml_jadwal++;
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->count();
                        $absensi = array();
                        if($attendance != 0){
                        $attendance = HairstylistAttendance::where(array('id_hairstylist_schedule_date'=>$va['id_hairstylist_schedule_date']))->get();
                        foreach ($attendance as $v) {
                            array_push($absensi,$v);
                            $kehadiran++;
                            if($v['is_on_time'] == 0){
                                $terlambat++;
                            }
                        }
                        }else{
                           $tidak_hadir++;
                        }
                        $data_value = array(
                            'data_schedules'=>$va,
                            'attendance'=>$absensi
                        );
                         array_push($data_schedules,$data_value);
                     }
                     
        }
        $schedule1 = HairstylistSchedule::join('outlets','outlets.id_outlet','hairstylist_schedules.id_outlet')
                    ->select(['hairstylist_schedules.*'])
                    ->where(array('hairstylist_schedules.id_outlet'=>$values['id_outlet'],'id_user_hair_stylist'=>$hs,'schedule_month'=>$month1,'schedule_year'=>$year1))
                    ->get();
        
        $data_outlet = array(
            'id_outlet'=>$values['id_outlet'],
            'outlet_name'=>$values['outlet_name'],
//            'schedule_date'=> $data_schedules,
            'jadwal'=> $jml_jadwal,
            'kehadiran'=> $kehadiran,
            'terlambat'=> $terlambat,
            'tidak_hadir'=>$tidak_hadir
        );
        array_push($data_schedule_outlet,$data_outlet);
    }
    return $data_schedule_outlet;
    }

    //asli
    public function export_income2($request)
    {
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];
        $outlets = Outlet::whereIn('id_outlet', $request['id_outlet'])->join('locations','locations.id_location','outlets.id_location')->get()->keyBy('id_outlet');
        if ($outlets->count() == 0) {
            return [
                'status' => 'fail',
                'messages' => ['No outlet selected']
            ];
        }
        // $transactions = Transaction::join('transaction_products', function ($join) use ($request) {
        //         $join->on('transactions.id_transaction', 'transaction_products.id_transaction')
        //             ->whereDate('transactions.transaction_date', '>=', $request['start_date'])
        //             ->whereDate('transactions.transaction_date', '<=', $request['end_date'])
        //             ->where('transaction_payment_status','Completed');
        //     })
        //     ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
        //     ->whereIn('transactions.id_outlet', $request['id_outlet'])->get();
        $transactions = Transaction::join('transaction_products', function ($join) use ($request) {
               $join->on('transactions.id_transaction', 'transaction_products.id_transaction')
                   ->whereDate('transaction_products.transaction_product_completed_at', '>=', $request['start_date'])
                   ->whereDate('transaction_products.transaction_product_completed_at', '<=', $request['end_date']);
            })
            ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
            ->whereIn('transactions.id_outlet', $request['id_outlet'])->get();
        if ($transactions->count() == 0) {
            return [
                'status' => 'fail',
                'messages' => ['No transactions found in selected date range']
            ];
        }

        $transactionBreakdowns = TransactionBreakdown::whereIn('id_transaction_product', $transactions->pluck('id_transaction_product'))->where('type', 'fee_hs')->get();
        $transactionBreakdownsGroupByTrxProduct = $transactionBreakdowns->groupBy('id_transaction_product');

        $transactionsByHS = $transactions->groupBy('id_user_hair_stylist');
       $hairstylists = UserHairStylist::where(function($query) use ($transactions, $request) {
                $query->whereIn('id_user_hair_stylist', $transactions->pluck('id_user_hair_stylist')->unique())
                    ->orWhereIn('id_outlet', $request['id_outlet']);
            })
            ->join('hairstylist_groups', 'hairstylist_groups.id_hairstylist_group','user_hair_stylist.id_hairstylist_group')
            ->where('user_hair_stylist_status', 'Active')
            ->orderBy('fullname')
            ->with('hairstylistCategory', 'bank_account')
            ->get();

        $all_attends = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $request['start_date'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $request['end_date'])
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
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $request['start_date'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $request['end_date'])
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) {
                return $item->keyBy('id_outlet');
            });

        $all_absens = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $request['start_date'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $request['end_date'])
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
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $request['start_date'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $request['end_date'])
            ->select('date','id_outlet', 'id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) {
                return $item->groupBy('id_outlet');
            });
      $all_timeoff = HairStylistTimeOff::whereNotNull('approve_at')
            ->whereNull('reject_at')
            ->whereDate('date', '>=', $request['start_date'])
            ->whereDate('date', '<=', $request['end_date'])
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) {
                return $item->keyBy('id_outlet');
            });
        $minOvertimeMinutes = MyHelper::setting('overtime_hs', 'value', 45);
       $overtimes = HairstylistOverTime::wherenotnull('approve_at')
            ->wherenull('reject_at')
            ->where('not_schedule',0)
            ->whereDate('date', '>=', $request['start_date'])
            ->whereDate('date', '<=', $request['end_date'])
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
        $overtimes_day = HairstylistOverTime::wherenotnull('approve_at')
            ->wherenull('reject_at')
            ->where('not_schedule',1)
            ->whereDate('date', '>=', $request['start_date'])
            ->whereDate('date', '<=', $request['end_date'])
            ->select('duration', 'id_user_hair_stylist', 'id_outlet', \DB::raw('DATE(date) as datex'))
            ->get()
            ->groupBy('id_user_hair_stylist');
        $allLoans = HairstylistLoan::join('hairstylist_category_loans', 'hairstylist_category_loans.id_hairstylist_category_loan', 'hairstylist_loans.id_hairstylist_category_loan')
            ->join('hairstylist_loan_returns', function ($join) use ($start_date, $end_date) {
                $join->on('hairstylist_loan_returns.id_hairstylist_loan', 'hairstylist_loans.id_hairstylist_loan')
                    ->whereDate('hairstylist_loan_returns.date_pay', '>=', $start_date)
                    ->whereDate('hairstylist_loan_returns.date_pay', '<=', $end_date)
                    ->where('hairstylist_loan_returns.status_return', 'Success');
            })
            ->where('status_loan', 'Success')
            ->select('hairstylist_category_loans.name_category_loan',
                DB::raw('
                               SUM(
                                 CASE WHEN hairstylist_loan_returns.status_return = "Success" AND hairstylist_loan_returns.date_pay IS NOT NULL THEN hairstylist_loan_returns.amount_return
                                         ELSE 0 END
                                 ) as value
                            '),
            )
            ->groupby('id_user_hair_stylist', 'hairstylist_category_loans.id_hairstylist_category_loan')
            ->get()
            ->groupBy('id_user_hair_stylist');


        $incomeDefault = \Modules\Recruitment\Entities\HairstylistGroupFixedIncentiveDefault::with(['detail'])->get();
        $hsGroup = HairstylistGroupProteksi::get()->groupBy('id_hairstylist_group');

        $exportResults = [];
        foreach ($hairstylists as $hairstylist) {
            $hs = $hairstylist;
            $periode = date('m', strtotime($request['end_date']));
            $protec = HairstylistGroupProteksiAttendanceDefault::leftJoin('hairstylist_group_proteksi_attendances', function ($join) use ($hs) {
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
            $data = array(
                'NIK'               => $hairstylist->user_hair_stylist_code,
                'NAMA LENGKAP'      => $hairstylist->fullname,
                'Nama Panggilan'    => $hairstylist->nickname,
                'Jabatan'           => $hairstylist->hairstylistCategory->hairstylist_category_name,
                'Join Date'         => date('d-M-Y',strtotime($hairstylist->join_date)),
            );
            
            $hsTransactions = $transactionsByHS[$hairstylist->id_user_hair_stylist] ?? collect([]);
            $hsTransactionsByOutlet = $hsTransactions->groupBy('id_outlet');
            if ($hsTransactionsByOutlet->count() == 0) {
                $hsTransactionsByOutlet = [$hairstylist->id_outlet => collect([])];
            }
            $hari_masuk = 0;
            $total_gross_sale = 0;
            $total_income = 0;
            $total_commissions = 0;
            $total_overtime_hs = 0;
            $total_timeoff_hs = 0;
            $total_late_hs = 0;
            $outlet_hs = array();
            foreach ($hsTransactionsByOutlet as $id_outlet => $outletTransactions) {
                $outlet_hs[] = $id_outlet;
                $outlet = $outlets[$id_outlet];
                $total_attend = $all_attends[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? '0';
                $total_timeoff = $all_timeoff[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? '0';
                $total_late = $all_lates[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? '0';
                $total_absen = $all_absens[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? '0';
                $total_overtimes = $all_overtimes[$hs->id_user_hair_stylist][$id_outlet] ?? '0';
                $hari_masuk = $hari_masuk + $total_attend;

                $total_gross_sale = $total_gross_sale + $outletTransactions->sum('transaction_product_subtotal');

                $total_commission = 0;
                foreach ($outletTransactions as $trx) {
                    $total_commission += optional($transactionBreakdownsGroupByTrxProduct[$trx->id_transaction_product] ?? null)->sum('value') ?? '0';
                }

                $total_commissions = $total_commissions + $total_commission;

                $total_overtime_hs = $total_overtime_hs + optional(optional($overtimes[$hs->id_user_hair_stylist][$id_outlet] ?? null)->values())->sum() ?? '0';

                $total_timeoff_hs = $total_timeoff_hs + $total_timeoff;
                $total_late_hs = $total_late_hs + $total_late;
            }
            
            $data['Hari Masuk'] = (string) $hari_masuk;
            $data['Brand'] = $hairstylist->hair_stylist_group_name;
            $data['Brand Protection Attendace'] = $protec->value;
            $data['Brand Salary Protection'] = $protec->amount_proteksi;
            $data['Brand Salary'] =  $protec->amount;
            $data['Brand Salary Per Day'] = $protec->amount_day * (int)$hari_masuk;


            $data['Total commission'] = (string) $total_commissions;
            $total_income += $total_commissions;

            $data['Tambahan jam'] = (string) $total_overtime_hs;

            $data['Total Izin/Cuti'] = (string) $total_timeoff_hs;
            $data['Potongan telat'] = (string) $total_late_hs;
            $response = HairstylistIncome::calculateFixedIncentive($hs, $start_date,$end_date,$outlet_hs,$incomeDefault);
            foreach ($response as $valu) {
                $data[ucfirst(str_replace('-', ' ', $valu['name']))]=(string)$valu['value'];
                if($valu['status']=='salary_cut'){
                    $total_income -= $valu['value'];
                }else{
                    $total_income += $valu['value']; 
                }
            }
            if ($allLoans[$hs->id_user_hair_stylist] ?? false) {
                $response = HairstylistIncome::calculateSalaryCuts($hs, $request['start_date'],$request['end_date'], $allLoans[$hs->id_user_hair_stylist]);
                foreach ($response as $valu) {
                    $data[ucfirst(str_replace('-', ' ', $valu['name']))]=(string)$valu['value'];
                    $total_income += $valu['value'];
                }
            }
           $response = HairstylistIncome::calculateIncomeExport($hs, $request['start_date'], $request['end_date'], $outlet_hs, $all_attends, $all_lates, $all_absens, $all_overtimes);
            foreach ($response as $values) {
                $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
                $total_income += $values['value'];
            }
                
//               $response = HairstylistIncome::calculateIncomeOvertimeDay($hs, $request['start_date'],$request['end_date'], [$id_outlet], $overtimes_day);
//                foreach ($response as $values) {
//                    $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
//                    $total_income += $values['value'];
//                }
                $response = HairstylistIncome::calculateIncomeProteksi($hs, $request['start_date'],$request['end_date'],$id_outlet);
                foreach ($response as $values) {
                    $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
                    $total_income += $values['value'];
                }
                $response = HairstylistIncome::calculateIncomeOvertime($hs, $request['start_date'],$request['end_date'], [$id_outlet], $all_overtimes);
                foreach ($response as $values) {
                    $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
                    $total_income += $values['value'];
                }
              $response = HairstylistIncome::calculateIncomeLateness($hs, $request['start_date'],$request['end_date'],$id_outlet);
                foreach ($response as $values) {
                    $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
                    $total_income -= $values['value'];
                }
		$proteksi = HairstylistIncome::calculateGenerateIncomeProtec($hs, $request['start_date'],$request['end_date'],$id_outlet);
                $keterangan = "Non Protection";
                if($proteksi['name']){
                         $keterangan = $proteksi['name'];
                }
                if($proteksi['total_income']>$total_income){
                         $total_income =(string) $proteksi['total_income'];
                }
                
                $data['Total imbal jasa'] = (string) $total_income;
                $data['Keterangan'] = $keterangan;

                $data['Bank'] = $hairstylist->bank_account->bank_name->bank_name??'';
                $data['Bank account'] = $hairstylist->bank_account->beneficiary_name??'';
                $data['Email'] = $hairstylist->email??'';

                $exportResults[] = $data;
        }

        $b = array();
        foreach ($exportResults as $key => $value) {
            $b = array_merge($b,array_keys($value));
        }
        $head = array_unique($b);
        $body = array();
        $in_array = ["NIK","NAMA LENGKAP","Nama Panggilan","Jabatan","Join Date","Outlet","Brand","Brand Protection Attendace","Brand Salary Protection","Brand Salary","Brand Salary Per Day","Keterangan","Bank","Bank account","Email"];
        foreach ($exportResults as $vab) {
            foreach($head as $v){
            if (in_array($v, $in_array)){
                $not = '';
                }else{
                $not = "0";
                }
                $isi[$v] = $vab[$v]??$not;
            }
            array_push($body,$isi);
        }
        $response = array(
            'start_date'=>$request['start_date'],
            'end_date'=>$request['end_date'],
            'head'=> $head,
            'body'=> $body,
        );
        return $response;
    }

    public function export_income($request)
    {
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];
        $outlets = Outlet::whereIn('id_outlet', $request['id_outlet'])->join('locations','locations.id_location','outlets.id_location')->get()->keyBy('id_outlet');
        if ($outlets->count() == 0) {
            return [
                'status' => 'fail',
                'messages' => ['No outlet selected']
            ];
        }
        // $transactions = Transaction::join('transaction_products', function ($join) use ($request) {
        //         $join->on('transactions.id_transaction', 'transaction_products.id_transaction')
        //             ->whereDate('transactions.transaction_date', '>=', $request['start_date'])
        //             ->whereDate('transactions.transaction_date', '<=', $request['end_date'])
        //             ->where('transaction_payment_status','Completed');
        //     })
        //     ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
        //     ->whereIn('transactions.id_outlet', $request['id_outlet'])->get();
        $transactions = Transaction::join('transaction_products', function ($join) use ($request) {
               $join->on('transactions.id_transaction', 'transaction_products.id_transaction')
                   ->whereDate('transaction_products.transaction_product_completed_at', '>=', $request['start_date'])
                   ->whereDate('transaction_products.transaction_product_completed_at', '<=', $request['end_date']);
            })
            ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
            ->whereIn('transactions.id_outlet', $request['id_outlet'])->get();
        if ($transactions->count() == 0) {
            return [
                'status' => 'fail',
                'messages' => ['No transactions found in selected date range']
            ];
        }

        $transactionBreakdowns = TransactionBreakdown::whereIn('id_transaction_product', $transactions->pluck('id_transaction_product'))->where('type', 'fee_hs')->get();
        $transactionBreakdownsGroupByTrxProduct = $transactionBreakdowns->groupBy('id_transaction_product');

        $transactionsByHS = $transactions->groupBy('id_user_hair_stylist');
       $hairstylists = UserHairStylist::where(function($query) use ($transactions, $request) {
                $query->whereIn('id_user_hair_stylist', $transactions->pluck('id_user_hair_stylist')->unique())
                    ->orWhereIn('id_outlet', $request['id_outlet']);
            })
            ->where('user_hair_stylist_status', 'Active')
            ->join('hairstylist_groups', 'hairstylist_groups.id_hairstylist_group','user_hair_stylist.id_hairstylist_group')
            ->orderBy('fullname')
            ->with('hairstylistCategory', 'bank_account')
            ->get();

        $all_attends = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNotNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $request['start_date'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $request['end_date'])
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
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $request['start_date'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $request['end_date'])
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) {
                return $item->keyBy('id_outlet');
            });

        $all_absens = HairstylistScheduleDate::leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->whereNull('clock_in')
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $request['start_date'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $request['end_date'])
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
            ->whereDate('hairstylist_attendances.attendance_date', '>=', $request['start_date'])
            ->whereDate('hairstylist_attendances.attendance_date', '<=', $request['end_date'])
            ->select('date','id_outlet', 'id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) {
                return $item->groupBy('id_outlet');
            });
      $all_timeoff = HairStylistTimeOff::whereNotNull('approve_at')
            ->whereNull('reject_at')
            ->whereDate('date', '>=', $request['start_date'])
            ->whereDate('date', '<=', $request['end_date'])
            ->selectRaw('count(*) as total, id_outlet, id_user_hair_stylist')
            ->groupBy('id_outlet', 'id_user_hair_stylist')
            ->get()
            ->groupBy('id_user_hair_stylist')
            ->map(function ($item) {
                return $item->keyBy('id_outlet');
            });
        $minOvertimeMinutes = MyHelper::setting('overtime_hs', 'value', 45);
       $overtimes = HairstylistOverTime::wherenotnull('approve_at')
            ->wherenull('reject_at')
            ->where('not_schedule',0)
            ->whereDate('date', '>=', $request['start_date'])
            ->whereDate('date', '<=', $request['end_date'])
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
        $overtimes_day = HairstylistOverTime::wherenotnull('approve_at')
            ->wherenull('reject_at')
            ->where('not_schedule',1)
            ->whereDate('date', '>=', $request['start_date'])
            ->whereDate('date', '<=', $request['end_date'])
            ->select('duration', 'id_user_hair_stylist', 'id_outlet', \DB::raw('DATE(date) as datex'))
            ->get()
            ->groupBy('id_user_hair_stylist');
        $allLoans = HairstylistLoan::join('hairstylist_category_loans', 'hairstylist_category_loans.id_hairstylist_category_loan', 'hairstylist_loans.id_hairstylist_category_loan')
            ->join('hairstylist_loan_returns', function ($join) use ($start_date, $end_date) {
                $join->on('hairstylist_loan_returns.id_hairstylist_loan', 'hairstylist_loans.id_hairstylist_loan')
                    ->whereDate('hairstylist_loan_returns.date_pay', '>=', $start_date)
                    ->whereDate('hairstylist_loan_returns.date_pay', '<=', $end_date)
                    ->where('hairstylist_loan_returns.status_return', 'Success');
            })
            ->where('status_loan', 'Success')
            ->select('hairstylist_category_loans.name_category_loan',
                DB::raw('
                               SUM(
                                 CASE WHEN hairstylist_loan_returns.status_return = "Success" AND hairstylist_loan_returns.date_pay IS NOT NULL THEN hairstylist_loan_returns.amount_return
                                         ELSE 0 END
                                 ) as value
                            '),
            )
            ->groupby('id_user_hair_stylist', 'hairstylist_category_loans.id_hairstylist_category_loan')
            ->get()
            ->groupBy('id_user_hair_stylist');


        $incomeDefault = \Modules\Recruitment\Entities\HairstylistGroupFixedIncentiveDefault::with(['detail'])->get();
        $hsGroup = HairstylistGroupProteksi::get()->groupBy('id_hairstylist_group');

        $exportResults = [];
        foreach ($hairstylists as $hairstylist) {
            $hs = $hairstylist;
            
            $periode = date('m', strtotime($request['end_date']));
            $protec = HairstylistGroupProteksiAttendanceDefault::leftJoin('hairstylist_group_proteksi_attendances', function ($join) use ($hs) {
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
            $data = array(
                'NIK'               => $hairstylist->user_hair_stylist_code,
                'NAMA LENGKAP'      => $hairstylist->fullname,
                'Nama Panggilan'    => $hairstylist->nickname,
                'Jabatan'           => $hairstylist->hairstylistCategory->hairstylist_category_name,
                'Join Date'         => date('d-M-Y',strtotime($hairstylist->join_date)),
                'Outlet'            => '',
            );
            $hsTransactions = $transactionsByHS[$hairstylist->id_user_hair_stylist] ?? collect([]);
            $hsTransactionsByOutlet = $hsTransactions->groupBy('id_outlet');
            if ($hsTransactionsByOutlet->count() == 0) {
                $hsTransactionsByOutlet = [$hairstylist->id_outlet => collect([])];
            }
            foreach ($hsTransactionsByOutlet as $id_outlet => $outletTransactions) {
                
                $outlet = $outlets[$id_outlet];
                $data['Outlet'] = $outlet['outlet_name'];
                $total_attend = $all_attends[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? '0';
                $total_timeoff = $all_timeoff[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? '0';
                $total_late = $all_lates[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? '0';
                $total_absen = $all_absens[$hs->id_user_hair_stylist][$id_outlet]['total'] ?? '0';
                $total_overtimes = $all_overtimes[$hs->id_user_hair_stylist][$id_outlet] ?? '0';
                $data['Hari Masuk'] = (string) $total_attend;
                $data['Brand'] = $hairstylist->hair_stylist_group_name;
                $data['Brand Protection Attendace'] = $protec->value;
                $data['Brand Salary Protection'] = $protec->amount_proteksi;
                $data['Brand Salary'] =  $protec->amount;
                $data['Brand Salary Per Day'] = $protec->amount_day * (int)$total_attend;
                
                $data['Total gross sale'] = (string) $outletTransactions->sum('transaction_product_subtotal');

                $total_income = 0;
                $total_commission = 0;
                foreach ($outletTransactions as $trx) {
                    $total_commission += optional($transactionBreakdownsGroupByTrxProduct[$trx->id_transaction_product] ?? null)->sum('value') ?? '0';
                }

                $data['Total commission'] = (string) $total_commission;
                $total_income += $total_commission;

                $data['Tambahan jam'] = (string) optional(optional($overtimes[$hs->id_user_hair_stylist][$id_outlet] ?? null)->values())->sum() ?? '0';

                $data['Total Izin/Cuti'] = (string) $total_timeoff;
                $data['Potongan telat'] = (string) $total_late;

                $response = HairstylistIncome::calculateFixedIncentive($hs, $request['start_date'],$request['end_date'],$outlet,$incomeDefault);
                foreach ($response as $valu) {
                    $data[ucfirst(str_replace('-', ' ', $valu['name']))]=(string)$valu['value'];
                }
                if ($allLoans[$hs->id_user_hair_stylist] ?? false) {
                    $response = HairstylistIncome::calculateSalaryCuts($hs, $request['start_date'],$request['end_date'], $allLoans[$hs->id_user_hair_stylist]);
                    foreach ($response as $valu) {
                        $data[ucfirst(str_replace('-', ' ', $valu['name']))]=(string)$valu['value'];
                        $total_income += $valu['value'];
                    }
                }

                $response = HairstylistIncome::calculateIncomeExport($hs, $request['start_date'], $request['end_date'], [$id_outlet], $all_attends, $all_lates, $all_absens, $all_overtimes);
                foreach ($response as $values) {
                    $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
                    $total_income += $values['value'];
                }
                
//               $response = HairstylistIncome::calculateIncomeOvertimeDay($hs, $request['start_date'],$request['end_date'], [$id_outlet], $overtimes_day);
//                foreach ($response as $values) {
//                    $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
//                    $total_income += $values['value'];
//                }
                $response = HairstylistIncome::calculateIncomeProteksi($hs, $request['start_date'],$request['end_date'],$id_outlet);
                foreach ($response as $values) {
                    $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
                    $total_income += $values['value'];
                }
                $response = HairstylistIncome::calculateIncomeOvertime($hs, $request['start_date'],$request['end_date'], [$id_outlet], $all_overtimes);
                foreach ($response as $values) {
                    $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
                    $total_income += $values['value'];
                }
              $response = HairstylistIncome::calculateIncomeLateness($hs, $request['start_date'],$request['end_date'],$id_outlet);
                foreach ($response as $values) {
                    $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
                    $total_income -= $values['value'];
                }
		$proteksi = HairstylistIncome::calculateGenerateIncomeProtec($hs, $request['start_date'],$request['end_date'],$id_outlet);
                $keterangan = "Non Protection";
                if($proteksi['name']){
                         $keterangan = $proteksi['name'];
                }
                if($proteksi['total_income']>$total_income){
                         $total_income = $proteksi['total_income'];
                }
                
                $data['Total imbal jasa'] = (string) $total_income;
                $data['Keterangan'] = $keterangan;

                $data['Bank'] = $hairstylist->bank_account->bank_name->bank_name??'';
                $data['Bank account'] = $hairstylist->bank_account->beneficiary_name??'';
                $data['Email'] = $hairstylist->email??'';

                $exportResults[] = $data;
            }
        }

        $b = array();
        foreach ($exportResults as $key => $value) {
            $b = array_merge($b,array_keys($value));
        }
        $head = array_unique($b);
        $body = array();
        $in_array = ["NIK","NAMA LENGKAP","Nama Panggilan","Jabatan","Join Date","Outlet","Brand","Brand Protection Attendace","Brand Salary Protection","Brand Salary","Brand Salary Per Day","Keterangan","Bank","Bank account","Email"];
        foreach ($exportResults as $vab) {
            foreach($head as $v){
            if (in_array($v, $in_array)){
                $not = '';
                }else{
                $not = "0";
                }
                $isi[$v] = $vab[$v]??$not;
            }
            array_push($body,$isi);
        }
        $response = array(
            'start_date'=>$request['start_date'],
            'end_date'=>$request['end_date'],
            'head'=> $head,
            'body'=> $body,
        );
        return $response;
    }
}
