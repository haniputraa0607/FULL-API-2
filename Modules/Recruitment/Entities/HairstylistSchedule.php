<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 14 Sep 2021 10:44:38 +0700.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HairstylistSchedule
 * 
 * @property int $id_hairstylist_schedule
 * @property int $id_user_hair_stylist
 * @property int $id_outlet
 * @property int $approve_by
 * @property \Carbon\Carbon $request_at
 * @property \Carbon\Carbon $approve_at
 * @property \Carbon\Carbon $reject_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package Modules\Recruitment\Entities
 */
class HairstylistSchedule extends Model
{
	protected $primaryKey = 'id_hairstylist_schedule';

	protected $casts = [
		'id_user_hair_stylist' => 'int',
		'id_outlet' => 'int',
		'approve_by' => 'int'
	];

	protected $dates = [
		'request_at',
		'approve_at',
		'reject_at'
	];

	protected $fillable = [
		'id_user_hair_stylist',
		'id_outlet',
		'approve_by',
		'last_updated_by',
		'schedule_month',
		'schedule_year',
		'request_at',
		'approve_at',
		'reject_at'
	];

	public function hairstylist_schedule_dates()
	{
		return $this->hasMany(\Modules\Recruitment\Entities\HairstylistScheduleDate::class, 'id_hairstylist_schedule');
	}
}
