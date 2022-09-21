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
           '2022-01',
           '2022-02',
           '2022-03',
           '2022-04',
           '2022-05',
           '2022-06',
           '2022-07',
           '2022-08',
           '2022-09',
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
    public function cron_middle() {
         $log = MyHelper::logCron('Cron Income HS middle month');
        try {
        $hs = UserHairStylist::get();
        $type = 'middle';
        foreach ($hs as $value) {
            $income = $this->schedule_income($value['id_user_hair_stylist'], $type);
        }
        $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }
    public function cron_end() {
       $log = MyHelper::logCron('Cron Income HS end month');
        try {
        $hs = UserHairStylist::get();
        $type = 'end';
        foreach ($hs as $value) {
         $income = $this->schedule_income($value['id_user_hair_stylist'], $type);
        }
        $log->success('success');
            return response()->json(['success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
             return response()->json($e->getMessage());
        }
    }
    public function export_periode(Request $request) {
            $request->validate([
                'id_outlet'         => 'required|array',
                'start_date'        => 'required|date_format:Y-m',
                'end_date'          => 'required|date_format:Y-m',
            ]);
          $startDate = $request->start_date;
          $endDate   = $request->end_date;
          $date_end         = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value')??null;
          $date_start         = (int)$date_end+1;
          $start_date =  date('Y-m-'.$date_start, strtotime($startDate."-1 months"));
          $end_date = date('Y-m-'.$date_end, strtotime($start_date.'+1 months'));
          $sta = date('Y-m-'.$date_start, strtotime($startDate));
          $starts = date('Y-m-d', strtotime($sta."-1 months"));
          $ends = date('Y-m-'.$date_end, strtotime($endDate));
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
              if($e >= $ends){
                  $e = $ends;
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
          $array = array();
          foreach ($ar as $value) {
              $req = array(
                  'id_outlet'=>$request->id_outlet,
                  'start_date'=>$value['start'],
                  'end_date'=>$value['end'],
              );
             $data = $this->export_income2($req);
              if(isset($data['status'])&& $data['status']=='fail'){
                  continue;
              }else{
                  $array[] = $data;
              }
          }
          return MyHelper::checkGet($array);
    }
    public function schedule_income($id,$type = 'end') {
       $b = new HairstylistIncome();
       $hs = UserHairStylist::where('id_user_hair_stylist',$id)->first();
       $bro = $b->calculateIncome($hs, $type);
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
                if ($total_income<$proteksi['value']) {
                    $keterangan = $proteksi['name'];
                    $total_income = $proteksi['value'];
                } else {
                    $keterangan = "Non Proteksi";
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
        $in_array = ["NIK","NAMA LENGKAP","Nama Panggilan","Jabatan","Join Date","Outlet","Keterangan","Bank","Bank account","Email"];
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

    public function export_income(Export_Outlet $request) {
        $array = array();
        $b = new HairstylistIncome();
        $hairstyllist = UserHairStylist::join('outlets','outlets.id_outlet','user_hair_stylist.id_outlet')
                ->leftjoin('bank_accounts','bank_accounts.id_bank_account','user_hair_stylist.id_bank_account')
                ->leftjoin('hairstylist_categories','hairstylist_categories.id_hairstylist_category','user_hair_stylist.id_hairstylist_category')
                ->leftjoin('bank_name','bank_name.id_bank_name','bank_accounts.id_bank_name')
                ->leftjoin('hairstylist_groups','hairstylist_groups.id_hairstylist_group','user_hair_stylist.id_hairstylist_group')
                ->wherein('user_hair_stylist.id_outlet',$request['id_outlet'])
                ->get();
        foreach ($hairstyllist as $value) {
            $hs = UserHairStylist::where('id_user_hair_stylist',$value->id_user_hair_stylist)->first();
            $location = Outlet::where('id_outlet',$value->id_outlet)->join('locations','locations.id_location','outlets.id_location')->first();
            
            $data = array(
                'NIK'=>$hs->user_hair_stylist_code??'',
                'NAMA LENGKAP'=>$hs->fullname??'',
                'Nama Panggilan'=>$hs->nickname??'',
                'Jabatan'=>$value['hairstylist_category_name']??'',
                'Join Date'=>date('d-M-Y',strtotime($hs->join_date))??'',
                'Outlet'=>$value->outlet_name??'',
            );
           $response = $b->calculateIncomeGross($hs, $request['start_date'],$request['end_date']);
            foreach ($response as $valu) {
                $data[ucfirst(str_replace('-', ' ', $valu['name']))]=(string)$valu['value'];
            }
            $response = $b->calculateIncomeProductCode($hs, $request['start_date'],$request['end_date']);
            foreach ($response as $values) {
                $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
            }
            $response = $b->calculateTambahanJam($hs, $request['start_date'],$request['end_date']);
            foreach ($response as $values) {
                $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
            }
            $response = $b->calculateFixedIncentive($hs, $request['start_date'],$request['end_date']);
            foreach ($response as $valu) {
                $data[ucfirst(str_replace('-', ' ', $valu['name']))]=(string)$valu['value'];
            }
            $response = $b->calculateSalaryCuts($hs, $request['start_date'],$request['end_date']);
            foreach ($response as $valu) {
                $data[ucfirst(str_replace('-', ' ', $valu['name']))]=(string)$valu['value'];
            }
            $response = $b->calculateIncomeExport($hs, $request['start_date'],$request['end_date']);
            foreach ($response as $values) {
                $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
            }
            $response = $b->calculateIncomeOvertime($hs, $request['start_date'],$request['end_date']);
            foreach ($response as $values) {
                $data[ucfirst(str_replace('-', ' ', $values['name']))]=(string)$values['value'];
            }
           $response = $b->calculateIncomeTotal($hs, $request['start_date'],$request['end_date']);
            foreach ($response as $valu) {
                $data[ucfirst(str_replace('-', ' ', $valu['name']))]=(string)$valu['value'];
            }
            $data['Bank'] = $value->bank_name??'';
            $data['Bank account'] = $value->beneficiary_name??'';
            $data['Email'] = $value->email??'';
            array_push($array,$data);
        }
        $b = array();
        foreach ($array as $key => $value) {
            $b = array_merge($b,array_keys($value));
        }
        $head = array_unique($b);
        $body = array();
        $in_array = ["NIK","NAMA LENGKAP","Nama Panggilan","Jabatan","Join Date","Outlet","Keterangan","Bank","Bank account","Email"];
        foreach ($array as $vab) {
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
        return MyHelper::checkGet($response);
    }
}
