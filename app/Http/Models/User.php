<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace App\Http\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Lib\MyHelper;

use Modules\UserFeedback\Entities\UserFeedbackLog;

class User extends Authenticatable
{
	protected $connection = 'mysql';
    use HasApiTokens, Notifiable;
	
	public function findForPassport($username) {
		if(substr($username, 0, 2) == '62'){
			$username = substr($username,2);
		}elseif(substr($username, 0, 3) == '+62'){
			$username = substr($username,3);
		}

		if(substr($username, 0, 1) != '0'){
			$username = '0'.$username;
		}

        return $this->where('phone', $username)->first();
    }
	protected $primaryKey = "id";
	protected $casts = [
		'id_membership' => 'int',
		'id_city' => 'int',
		'points' => 'int',
		'count_transaction_day' => 'int',
		'count_transaction_week' => 'int'
	];

	protected $dates = [
		'birthday'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'name',
		'phone',
		'id_membership',
		'email',
		'password',
        'id_card_image',
		'id_city',
		'gender',
		'provider',
		'birthday',
		'relationship',
		'phone_verified',
		'email_verified',
        'email_verified_valid_time',
		'level',
        'id_outlet',
        'id_role',
		'points',
		'balance',
		'count_complete_profile',
		'last_complete_profile',
		'complete_profile',
		'complete_profile_date',
		'android_device',
		'ios_device',
		'ios_apps_version',
		'android_apps_version',
		'is_suspended',
		'remember_token',
		'count_transaction_day',
		'count_transaction_week',
		'subtotal_transaction',
		'count_transaction',
		'count_login_failed',
		'new_login',
		'pin_changed',
		'first_pin_change',
        'status_new_user',
		'celebrate',
		'job',
		'address',
        'email_verify_request_status',
        'otp_forgot',
        'otp_request_status',
        'otp_valid_time',
        'otp_available_time_request',
        'otp_increment',
        'transaction_online',
        'transaction_online_status',
        'user_time_zone_utc',
        'claim_point_status',
        'custom_name',
        'is_anon',
        'from_pos'    
	];

	public function city()
	{
		return $this->belongsTo(\App\Http\Models\City::class, 'id_city');
	}

	public function autocrm_email_logs()
	{
		return $this->hasMany(\App\Http\Models\AutocrmEmailLog::class, 'id', 'id_user');
	}
	
	public function user_outlets()
	{
		return $this->hasOne(\App\Http\Models\UserOutlet::class, 'id_user', 'id');
	}

	public function autocrm_push_logs()
	{
		return $this->hasMany(\App\Http\Models\AutocrmPushLog::class, 'id', 'id_user');
	}

	public function autocrm_sms_logs()
	{
		return $this->hasMany(\App\Http\Models\AutocrmSmsLog::class, 'id', 'id_user');
	}

	public function campaigns()
	{
		return $this->hasMany(\App\Http\Models\Campaign::class, 'id', 'id_user');
	}

	public function deals_payment_manuals()
	{
		return $this->hasMany(\App\Http\Models\DealsPaymentManual::class, 'id_user_confirming');
	}

	public function transaction_payment_manuals()
	{
		return $this->hasMany(\App\Http\Models\TransactionPaymentManual::class, 'id_user_confirming');
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class, 'id_user', 'id')->orderBy('created_at', 'DESC');
	}
	
	public function history_transactions()
	{
		return $this->hasMany(Transaction::class, 'id_user', 'id')->select('id_user', 'id_transaction', 'id_outlet', 'transaction_receipt_number', 'trasaction_type', 'transaction_grandtotal', 'transaction_payment_status', 'transaction_date')->orderBy('transaction_date', 'DESC');
	}

	public function addresses()
	{
		return $this->hasMany(UserAddress::class, 'id', 'id_user');
	}

	public function user_devices()
	{
		return $this->hasMany(\App\Http\Models\UserDevice::class, 'id', 'id_user');
	}

	public function employee_devices()
	{
		return $this->hasMany(\Modules\Employee\Entities\EmployeeDevice::class, 'id', 'id_employee');
	}

	public function features()
	{
		return $this->belongsToMany(\App\Http\Models\Feature::class, 'user_features', 'id_user', 'id_feature');
	}

	public function user_inboxes()
	{
		return $this->hasMany(\App\Http\Models\UserInbox::class, 'id', 'id_user');
	}

	public function user_membership()
	{
		return $this->belongsTo(\App\Http\Models\Membership::class, 'id_membership')->select('id_membership', 'membership_name');
	}

	public function memberships()
	{
		return $this->belongsToMany(\App\Http\Models\Membership::class, 'users_memberships', 'id_user', 'id_membership')
					->withPivot('id_log_membership', 'min_total_value', 'min_total_count', 'retain_date', 'retain_min_total_value', 'retain_min_total_count', 'benefit_point_multiplier', 'benefit_cashback_multiplier', 'benefit_promo_id', 'benefit_discount')
					->withTimestamps()->orderBy('id_log_membership', 'DESC');
	}
	
	public function point() {
    	return $this->hasMany(LogPoint::class, 'id_user', 'id')->orderBy('created_at', 'DESC');
    }
    
    public function log_balance() {
    	return $this->hasMany(LogBalance::class, 'id_user', 'id')->orderBy('created_at', 'DESC');
    }
    
    public function history_balance() {
    	return $this->hasMany(LogBalance::class, 'id_user', 'id')->orderBy('created_at', 'DESC');
    }

    public function pointTransaction() {
    	return $this->hasMany(LogPoint::class, 'id_user', 'id')->orderBy('created_at', 'DESC')->where('source', '=', 'transaction');
    }

    public function pointVoucher() {
    	return $this->hasMany(LogPoint::class, 'id_user', 'id')->orderBy('created_at', 'DESC')->where('source', '=', 'voucher');
	}
	
	public function promotion_queue() {
    	return $this->hasMany(PromotionQueue::class, 'id_user', 'id');
    }
    
    public function promotionSents() {
    	return $this->hasMany(PromotionSent::class, 'id_user', 'id')->orderBy('series_no', 'ASC');
    }

    public function favorites() {
    	return $this->hasMany(\Modules\Favorite\Entities\Favorite::class, 'id_user');
    }


    public function log_popup()
    {
    	return $this->hasOne(UserFeedbackLog::class,'id_user');
    }

    public function log_popup_user_rating()
    {
    	return $this->hasMany(\Modules\UserRating\Entities\UserRatingLog::class,'id_user')->orderBy('last_popup')->orderBy('id_user_rating_log');
    }

    public function referred_user()
    {
    	return $this->belongsToMany(User::class,'promo_campaign_referral_transactions','id_referrer','id_user');
    }

    public function referred_transaction()
    {
    	return $this->hasMany(\Modules\PromoCampaign\Entities\PromoCampaignReferralTransaction::class,'id_referrer','id');
    }

    public function getChallengeKeyAttribute()
    {
    	$password = md5($this->password);
    	return $password.'15F1AB77951B5JAO';
    }

	public function outlet()
    {
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
    }

	public function role()
    {
        return $this->belongsTo(\Modules\Users\Entities\Role::class, 'id_role');
    }

    public function employee_schedules()
	{
		return $this->hasMany(\Modules\Employee\Entities\EmployeeSchedule::class, 'id');
	}

    public function employee_attendances()
    {
        return $this->hasMany(\Modules\Employee\Entities\EmployeeAttendance::class, 'id');
    }

    public function employee_outlet_attendances()
    {
        return $this->hasMany(\Modules\Employee\Entities\EmployeeOutletAttendance::class, 'id');
    }

    public function getAttendanceByDate($schedule, $shift = false)
    {
        if (is_string($schedule)) {
            $data_schedule = $this->employee_schedules()
                ->selectRaw('id_employee_attendance, date, min(time_start) as clock_in_requirement, max(time_end) as clock_out_requirement')
                ->join('employee_schedule_dates', 'employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule');
            if($shift){
                $data_schedule = $data_schedule->whereNotNull('approve_at');
            }
            $data_schedule = $data_schedule->where([
                    'schedule_month' => date('m', strtotime($schedule)),
                    'schedule_year' => date('Y', strtotime($schedule))
                ])
                ->whereDate('date', $schedule)
                ->first();
            if (!$data_schedule || !$data_schedule->date) {
                throw new \Exception('Tidak ada kehadiran dibutuhkan untuk hari ini');
            }
            $schedule = $data_schedule;
        }
        $attendance = $this->employee_attendances()->where('attendance_date', $schedule->date)->first();
        if (!$attendance) {
            $id_employee_schedule_date = $this->employee_schedules()
                    ->join('employee_schedule_dates', 'employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule');
                    if($shift){
                        $id_employee_schedule_date= $id_employee_schedule_date->whereNotNull('approve_at');
                    }
                    $id_employee_schedule_date= $id_employee_schedule_date->where([
                        'schedule_month' => date('m', strtotime($schedule->date)),
                        'schedule_year' => date('Y', strtotime($schedule->date))
                    ])
                    ->whereDate('date', $schedule->date)
                    ->orderBy('is_overtime')
                    ->first()
                    ->id_employee_schedule_date;
            if (!$id_employee_schedule_date) {
                throw new \Exception('Tidak ada kehadiran dibutuhkan untuk hari ini');
            }
            $attendance = $this->employee_attendances()->create([
                'id_employee_schedule_date' => $id_employee_schedule_date,
                'id_outlet' => $this->id_outlet,
                'attendance_date' => $schedule->date,
                'id' => $this->id,
                'clock_in_requirement' => $schedule->clock_in_requirement,
                'clock_out_requirement' => $schedule->clock_out_requirement,
                'clock_in_tolerance' => MyHelper::setting('employee_clock_in_tolerance', 'value', 15),
                'clock_out_tolerance' => MyHelper::setting('employee_clock_out_tolerance', 'value', 0),
            ]);
        }
        return $attendance;
    }

    public function getAttendanceByDateOutlet($id_outlet, $schedule, $shift = false)
    {
        if (is_string($schedule)) {
            $data_schedule = $this->employee_schedules()
                ->selectRaw('id_employee_attendance, date, min(time_start) as start_shift, max(time_end) as end_shift')
                ->join('employee_schedule_dates', 'employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule');
            if($shift){
                $data_schedule = $data_schedule->whereNotNull('approve_at');
            }
            $data_schedule = $data_schedule->where([
                    'schedule_month' => date('m', strtotime($schedule)),
                    'schedule_year' => date('Y', strtotime($schedule))
                ])
                ->whereDate('date', $schedule)
                ->first();
            if (!$data_schedule || !$data_schedule->date) {
                throw new \Exception('Tidak ada kehadiran dibutuhkan untuk hari ini');
            }
            $schedule = $data_schedule;
        }
        $attendance = $this->employee_outlet_attendances()->where('attendance_date', $schedule->date)->where('id_outlet', $id_outlet)->first();
        if (!$attendance) {
            $id_employee_schedule_date = $this->employee_schedules()
                    ->join('employee_schedule_dates', 'employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule');
                    if($shift){
                        $id_employee_schedule_date= $id_employee_schedule_date->whereNotNull('approve_at');
                    }
                    $id_employee_schedule_date= $id_employee_schedule_date->where([
                        'schedule_month' => date('m', strtotime($schedule->date)),
                        'schedule_year' => date('Y', strtotime($schedule->date))
                    ])
                    ->whereDate('date', $schedule->date)
                    ->orderBy('is_overtime')
                    ->first()
                    ->id_employee_schedule_date;
            if (!$id_employee_schedule_date) {
                throw new \Exception('Tidak ada kehadiran dibutuhkan untuk hari ini');
            }
            $attendance = $this->employee_outlet_attendances()->create([
                'id_employee_schedule_date' => $id_employee_schedule_date,
                'id_outlet' => $id_outlet,
                'attendance_date' => $schedule->date,
                'id' => $this->id,
            ]);
        }
        return $attendance;
    }

    public function attendance_logs()
    {
        return $this->hasMany(\Modules\Employee\Entities\EmployeeAttendanceLog::class, 'id_employee_attendance', 'id_employee_attendance');
    }
    public function outlet_attendance_logs()
    {
        return $this->hasMany(\Modules\Employee\Entities\EmployeeOutletAttendanceLog::class, 'id_employee_outlet_attendance', 'id_employee_outlet_attendance');
    }

    public function quest_user_redemption() {
    	return $this->hasMany(\Modules\Quest\Entities\QuestUserRedemption::class, 'id_user', 'id');
    }
    public function employee() {
    	return $this->hasOne(\Modules\Employee\Entities\Employee::class, 'id_user', 'id');
    }
    public function employee_family() {
    	return $this->hasMany(\Modules\Employee\Entities\EmployeeFamily::class, 'id_user', 'id');
    }
    public function employee_main_family() {
    	return $this->hasMany(\Modules\Employee\Entities\EmployeeMainFamily::class, 'id_user', 'id');
    }
    public function employee_education() {
    	return $this->hasMany(\Modules\Employee\Entities\EmployeeEducation::class, 'id_user', 'id');
    }
    public function employee_education_non_formal() {
    	return $this->hasMany(\Modules\Employee\Entities\EmployeeEducationNonFormal::class, 'id_user', 'id');
    }
    public function employee_job_experience() {
    	return $this->hasMany(\Modules\Employee\Entities\EmployeeJobExperience::class, 'id_user', 'id');
    }
    public function employee_question() {
    	return $this->hasMany(\Modules\Employee\Entities\EmployeeQuestions::class, 'id_user', 'id');
    }
    public function employee_emergency_call() {
    	return $this->hasMany(\Modules\Employee\Entities\EmployeeEmergencyContact::class, 'id_user', 'id');
    }
    public function employee_category_question() {
    	return $this->hasMany(\Modules\Employee\Entities\CategoryQuestion::class);
    }
}
