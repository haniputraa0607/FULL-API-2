<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupOvertimeDay extends Model
{
	protected $table = 'hairstylist_group_overtime_days';
	protected $primaryKey = 'id_hairstylist_group_overtime_day';


	protected $fillable = [
		'id_hairstylist_group',
		'id_hairstylist_group_default_overtime_day',
		'value',
                'created_at',   
                'updated_at'
	];
}
