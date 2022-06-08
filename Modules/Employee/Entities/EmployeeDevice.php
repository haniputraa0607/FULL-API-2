<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeDevice extends Model
{
    protected $table = 'employee_devices';
    protected $primaryKey = 'id_employee_device';

    protected $casts = [
		'id_employee' => 'int'
	];

	protected $hidden = [
		'device_token'
	];

    protected $fillable = [
        'id_employee',
        'device_type',
        'device_id',
    ];

    public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_employee');
	}
}
