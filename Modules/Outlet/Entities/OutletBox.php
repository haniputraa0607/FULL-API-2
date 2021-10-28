<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletBox extends Model
{
    protected $table = 'outlet_box';
    protected $primaryKey = 'id_outlet_box';

    protected $fillable = [
        'id_outlet',
        'outlet_box_code',
        'outlet_box_name',
        'outlet_box_url',
        'outlet_box_status',
        'outlet_box_use_status'
    ];

    public function hairstylist_schedule_dates()
	{
		return $this->hasMany(\Modules\Recruitment\Entities\HairstylistScheduleDate::class, 'id_outlet_box');
	}
}
