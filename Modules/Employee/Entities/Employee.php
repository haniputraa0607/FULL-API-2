<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Outlet;
use App\Lib\Icount;

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
        'surat_perjanjian',
        'id_department',
        'id_manager',
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

    public function businessPartner($id_business_partner = null){
        $data_send['employee'] = Employee::join('users','users.id','employees.id_user')->where('id_employee',$this->id_employee)->first();
        $data_send['location'] = Outlet::leftjoin('locations','locations.id_location','outlets.id_location')->where('id_outlet',$data_send['employee']['id_outlet'])->first();
        if(isset($id_business_partner)){

            $check_id = Employee::join('users','users.id','employees.id_user')
            ->join('outlets','outlets.id_outlet','users.id_outlet')
            ->join('locations','locations.id_location','outlets.id_location')
            ->where('employees.id_business_partner', $id_business_partner)
            ->where('locations.company_type',$data_send['location']['company_type'])
            ->get()->toArray();
            if($check_id){
                return [
                    'status' => 'fail',
                    'messages' => 'This Business Partner ID already used by other employee',
                ];
            }
            $getBusinessPartner = Icount::searchBusinessPartner($id_business_partner, '013', $data_send['location']['company_type']??null);
            if($getBusinessPartner['response']['Message']=='Success'){
                $getBusinessPartner = $getBusinessPartner['response']['Data'];
                if(count($getBusinessPartner)<=0){
                    return [
                        'status' => 'fail',
                        'messages' => 'This Business Partner ID is not registered yet',
                    ];
                }else{
                    $getBusinessPartner = $getBusinessPartner[0];
                    if($data_send['location']['company_type']=='PT IMS'){
                        $initBranch_ims = Icount::ApiCreateEmployee($data_send, 'PT IMA');
                        $data_init_ims = $initBranch_ims['response']['Data'][0];
                        $update = Employee::where('id_employee', $this->id_employee)->update([
                            'id_business_partner' => $getBusinessPartner['BusinessPartnerID'],
                            'id_business_partner_ima' => $data_init_ims['BusinessPartnerID'],
                            'id_company' => $getBusinessPartner['CompanyID'],
                            'id_group_business_partner' => $getBusinessPartner['GroupBusinessPartner'],
                        ]);
                    }else{
                        $update = Employee::where('id_employee', $this->id_employee)->update([
                            'id_business_partner' => $getBusinessPartner['BusinessPartnerID'],
                            'id_company' => $getBusinessPartner['CompanyID'],
                            'id_group_business_partner' => $getBusinessPartner['GroupBusinessPartner'],
                        ]);
                    }
                    return [
                        'status' => 'success',
                        'id_business_partner' => $id_business_partner
                    ];
                }
            }else{
                return [
                    'status' => 'fail',
                    'messages' => 'Failed send data to Icount',
                ];
            }

            
        }else{
            $initBranch = Icount::ApiCreateEmployee($data_send, $data_send['location']['company_type']??null);

            if($initBranch['response']['Status']=='1' && $initBranch['response']['Message']=='success'){
                $initBranch = $initBranch['response']['Data'][0];
                if($data_send['location']['company_type']=='PT IMS'){
                    $initBranch_ims = Icount::ApiCreateEmployee($data_send, 'PT IMA');
                    $data_init_ims = $initBranch_ims['response']['Data'][0];
                    $update = Employee::where('id_employee', $this->id_employee)->update([
                        'id_business_partner' => $initBranch['BusinessPartnerID'],
                        'id_business_partner_ima' => $data_init_ims['BusinessPartnerID'],
                        'id_company' => $initBranch['CompanyID'],
                        'id_group_business_partner' => $initBranch['GroupBusinessPartner'],
                    ]);
                }else{
                    $update = Employee::where('id_employee', $this->id_employee)->update([
                        'id_business_partner' => $initBranch['BusinessPartnerID'],
                        'id_company' => $initBranch['CompanyID'],
                        'id_group_business_partner' => $initBranch['GroupBusinessPartner'],
                    ]);
                }
                return [
                    'status' => 'success',
                    'id_business_partner' => $initBranch['BusinessPartnerID']
                ];
            }else{
                return [
                    'status' => 'fail',
                    'messages' => 'Failed send data to Icount',
                ];
            }
        }
    }
}
