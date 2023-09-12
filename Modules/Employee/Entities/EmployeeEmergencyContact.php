<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeEmergencyContact extends Model
{
    protected $table = 'employee_emergency_contacts';

    protected $primaryKey = 'id_employee_emergency_contact';
    
    protected $fillable = [
        'id_user',
        'name_emergency_contact',
        'relation_emergency_contact',
        'phone_emergency_contact',
        'created_at',
        'updated_at',
    ];
     protected $appends  = ['wa'];
    public function getWaAttribute()
    {
        return $this->phone_emergency_contact;
    }
}
