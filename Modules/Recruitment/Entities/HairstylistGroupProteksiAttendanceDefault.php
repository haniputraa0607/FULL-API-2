<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupProteksiAttendanceDefault extends Model
{
	protected $table = 'hairstylist_group_default_proteksi_attendances';
	protected $primaryKey = 'id_hairstylist_group_default_proteksi_attendance';
	protected $fillable = [
        'month',
	'amount',
	'amount_day',
	'amount_proteksi',
        'value',
        'created_at',   
        'updated_at',
	];
}
