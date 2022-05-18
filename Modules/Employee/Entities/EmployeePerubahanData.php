<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeePerubahanData extends Model
{
    protected $table = 'employee_perubahan_datas';

    protected $primaryKey = 'id_employee_perubahan_data';
    
    protected $fillable = [
        'id_user',
        'key',
        'name',
        'change_data',
        'notes',
        'status',
        'created_at',
        'updated_at',
    ];
}
