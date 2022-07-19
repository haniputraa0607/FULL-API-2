<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleFixedIncentiveDefault extends Model
{
    protected $table = 'employee_role_default_fixed_incentives';

    protected $primaryKey = 'id_employee_role_default_fixed_incentive';
    
    protected $fillable = [
        'name_fixed_incentive',
        'status',
        'type',
        'formula',
        'created_at',
        'updated_at',
    ];
        public function detail(){
            return $this->hasMany(EmployeeRoleFixedIncentiveDefaultDetail::class, 'id_employee_role_default_fixed_incentive')->orderBy('range','desc');
        }
}
