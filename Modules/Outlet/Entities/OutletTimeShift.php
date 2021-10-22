<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletTimeShift extends Model
{
    protected $table = 'outlet_time_shift';
    protected $primaryKey = 'id_outlet_time_shift';

    protected $fillable = [
        'id_outlet',
        'id_outlet_schedule',
        'shift',
        'shift_time_start',
        'shift_time_end'
    ];
}
