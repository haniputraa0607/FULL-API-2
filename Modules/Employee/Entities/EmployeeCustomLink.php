<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeCustomLink extends Model
{
    protected $table = 'employee_custom_links';

    protected $primaryKey = 'id_employee_custom_link';
    
    protected $fillable = [
        'id_employee',
        'title',
        'link',
        'created_at',
        'updated_at',
    ];

    public function employee()
	{
		return $this->belongsTo(\Modules\Employee\Employee::class, 'id_employee','id_employee');
	}
}
