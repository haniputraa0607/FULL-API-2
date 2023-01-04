<?php

namespace Modules\Employee\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Users\Entities\Department;

class RequestEmployee extends Model
{
    protected $table = 'request_employees';
	protected $primaryKey = "id_request_employee";
	protected $fillable = [
        'id_outlet',
        'id_department',
		'number_of_request',
		'status',
		'id_user',
		'id_employee',
		'notes',
		'notes_om',
	];
    public function outlet_request(){
        return $this->belongsTo(Outlet::class, 'id_outlet');
    }
    public function department_request(){
        return $this->belongsTo(Department::class, 'id_department');
    }
    public function applicant_request(){
        return $this->belongsTo(User::class, 'id_user');
    }
}
