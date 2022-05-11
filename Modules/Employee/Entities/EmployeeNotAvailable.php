<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeNotAvailable extends Model
{
    protected $table = 'employee_not_available';

    protected $primaryKey = 'id_employee_not_available';

    protected $fillable   = [
        'id_outlet',
        'id_employee',
        'id_transaction',
        'id_employee_time_off',
        'id_transaction_product_service',
        'booking_start',
        'booking_end'
    ];
}
