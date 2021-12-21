<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;
use App\Lib\MyHelper;

class UserHairStylist extends Authenticatable
{
	use Notifiable, HasMultiAuthApiTokens;

	public function findForPassport($username) {
        $username = str_replace('+', '', $username);
        if(substr($username, 0, 2) == 62){
            $username = str_replace('62', '0', $username);
        }

        return $this->where('phone_number', $username)->first();
	}

	public function getAuthPassword() {
		return $this->password;
	}

    protected $table = 'user_hair_stylist';
	protected $primaryKey = 'id_user_hair_stylist';

	protected $hidden = [
		'password'
	];

	protected $fillable = [
	    'id_outlet',
		'id_bank_account',
        'user_hair_stylist_status',
        'user_hair_stylist_code',
        'nickname',
        'email',
        'phone_number',
        'fullname',
        'password',
        'level',
        'gender',
        'nationality',
        'birthplace',
        'birthdate',
        'religion',
        'height',
        'weight',
        'recent_job',
        'recent_company',
        'blood_type',
        'recent_address',
        'postal_code',
        'marital_status',
        'email_verified',
        'first_update_password',
        'join_date',
        'approve_by',
        'user_hair_stylist_photo',
        'total_rating',
        'total_balance',
        'latitude',
        'longitude',
        'home_service_status',
        'balance',
        'id_hairstylist_groups',
	];

    public function getUserHairStylistPhotoAttribute($value)
    {
        if(empty($value)){
            return '';
        }
        return config('url.storage_url_api') . $value;
    }

	public function hairstylist_schedules()
	{
		return $this->hasMany(\Modules\Recruitment\Entities\HairstylistSchedule::class, 'id_user_hair_stylist');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}

    public function getPhoneAttribute()
    {
        return $this->phone_number;
    }

    public function documents()
    {
        return $this->hasMany(\Modules\Recruitment\Entities\UserHairStylistDocuments::class, 'id_user_hair_stylist');
    }

    public function location()
    {
        return $this->hasOne(HairstylistLocation::class, 'id_user_hair_stylist');
    }

    public function attendances()
    {
        return $this->hasMany(\Modules\Recruitment\Entities\HairstylistAttendance::class, 'id_user_hair_stylist');
    }

    public function getAttendanceByDate($schedule)
    {
        if (is_string($schedule)) {
            $schedule = $this->hairstylist_schedules()
                ->selectRaw('id_hairstylist_attendance, date, min(time_start) as clock_in_requirement, max(time_end) as clock_out_requirement')
                ->join('hairstylist_schedule_dates', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                ->whereNotNull('approve_at')
                ->where([
                    'schedule_month' => date('m', strtotime($schedule)),
                    'schedule_year' => date('Y', strtotime($schedule))
                ])
                ->whereDate('date', $schedule)
                ->first();
            if (!$schedule || !$schedule->date) {
                throw new \Exception('Tidak ada kehadiran dibutuhkan untuk hari ini');
            }
        }
        $attendance = $this->attendances()->where('attendance_date', $schedule->date)->first();
        if (!$attendance) {
            $attendance = $this->attendances()->create([
                'id_hairstylist_schedule_date' => $this->hairstylist_schedules()
                    ->join('hairstylist_schedule_dates', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                    ->whereNotNull('approve_at')
                    ->where([
                        'schedule_month' => date('m', strtotime($schedule->date)),
                        'schedule_year' => date('Y', strtotime($schedule->date))
                    ])
                    ->whereDate('date', $schedule->date)
                    ->orderBy('is_overtime')
                    ->first()
                    ->id_hairstylist_schedule_date,
                'attendance_date' => $schedule->date,
                'id_user_hair_stylist' => $this->id_user_hair_stylist,
                'clock_in_requirement' => $schedule->clock_in_requirement,
                'clock_out_requirement' => $schedule->clock_out_requirement,
                'clock_in_tolerance' => MyHelper::setting('clock_in_tolerance', 'value', 15),
                'clock_out_tolerance' => MyHelper::setting('clock_in_tolerance', 'value', 0),
            ]);
        }
        return $attendance;
    }

    public function devices()
    {
        return $this->hasMany(UserHairStylistDevice::class, 'id_user_hair_stylist');
    }
}
