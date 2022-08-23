<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupProteksiAttendance extends Model
{
	protected $table = 'hairstylist_group_proteksi_attendances';
	protected $primaryKey = 'id_hairstylist_group_proteksi_attendance';


	protected $fillable = [
		'id_hairstylist_group',
		'id_hairstylist_group_default_proteksi_attendance',
		'value',
		'amount',
		'amount_day',
                'created_at',   
                'updated_at'
	];
}
