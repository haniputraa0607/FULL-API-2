<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeFile extends Model
{
    protected $table = 'employee_files';

    protected $primaryKey = 'id_employee_file';
    
    protected $fillable = [
        'id_user',
        'notes',
        'category',
        'attachment',
        'created_at',
        'updated_at',
    ];
}
