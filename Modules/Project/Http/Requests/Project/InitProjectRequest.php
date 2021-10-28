<?php

namespace Modules\Project\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;
class InitProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'partner'        => 'required|partner',
            'location'         => 'required|location|status',
        ]; 
    }
     public function withValidator($validator)
    {
        $validator->addExtension('location', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData();
            if(!empty($data['partner'])){
                $outlet = Location::join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('locations.id_location'=>$value,'partners.id_partner'=>$data['partner']))->first();
                if($outlet){
                    return true;
                }
            }
             return false; 
            
        }); 
        $validator->addExtension('partner', function ($attribute, $value, $parameters, $validator) {
           
                $partner = Partner::where(array('id_partner'=>$value))->first();
                if($partner){
                    return true;
                
            }
             return false; 
            
        }); 
        $validator->addExtension('status', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData();
            if(!empty($data['partner'])){
                $outlet = Project::where(array('id_partner'=>$data['partner'],'id_location'=>$value,'status'=>"Process"))->first();
                if(!$outlet){
                    return true;
                }
            }
             return false; 
            
        });

    }
    public function messages()
    {
        return [
            'location' => 'Invalid Partner',
            'partner' => 'Partner not found',
            'status'=> 'Status dalam Proses',
            'required' => ':attribute harus diisi'
        ];
    }
    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['status' => 'fail', 'messages'  => $validator->errors()->all()], 200));
    }

    protected function validationData()
    {
        return $this->all();
    }
}
