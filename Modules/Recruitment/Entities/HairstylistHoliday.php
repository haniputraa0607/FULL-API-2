<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistHoliday extends Model
{
    protected $table = 'hs_holidays';
	protected $primaryKey = 'id_hs_holiday';

	protected $fillable = [
        'holiday_date',
        'holiday_name',
        'month',
        'year',
        'created_at',   
        'updated_at'
	];
}
