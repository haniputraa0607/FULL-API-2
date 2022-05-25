<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';

    protected $primaryKey = 'id_employee';
    
    protected $fillable = [
        'id_user',
        'nickname',
        'country',
        'birthplace',
        'religion',
        'height',
        'weight',
        'age',
        'place_of_origin',
        'job_now',
        'companies',
        'blood_type',
        'card_number',
        'address_ktp',
        'id_city_ktp',
        'postcode_ktp',
        'address_domicile',
        'id_city_domicile',
        'postcode_domicile',
        'phone_number',
        'status_address_domicile',
        'marital_status',
        'married_date',
        'applied_position',
        'other_position',
        'vacancy_information',
        'relatives',
        'relative_name',
        'relative_position',
        'status',
        'status_step',
        'start_date',
        'end_date',
        'bank_account_name',
        'bank_account_number',
        'id_bank_name',
        'status_employee',
        'created_at',
        'updated_at',
    ];
    public function documents()
	{
		return $this->hasMany(\Modules\Employee\Entities\EmployeeDocuments::class, 'id_employee');
	}
    public function city_ktp()
	{
		return $this->belongsTo(\App\Http\Models\City::class, 'id_city_ktp','id_city');
	}
    public function city_domicile()
	{
		return $this->belongsTo(\App\Http\Models\City::class, 'id_city_domicile','id_city');
	}
}
