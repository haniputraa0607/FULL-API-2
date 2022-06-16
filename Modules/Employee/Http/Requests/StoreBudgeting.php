<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Users\Entities\Department;
use App\Http\Models\Setting;

class StoreBudgeting extends FormRequest
{
    public function rules()
    {
        return [
            'DepartmentID'         => 'required|cek',
            'balance'               => 'required|integer', 
            'api_key'              => 'required|api_key',
            'signature'            => 'required|signature'
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
            $share = Department::where('id_department_icount', $value)->first();
            if($share){
                return true;
            }
            return false;
        });
        $validator->addExtension('api_key', function ($attribute, $value, $parameters, $validator) {
            $api_secret = Setting::where('key','api_key')->where('value',$value)->first();
            if($api_secret){
                return true; 
            } 
            return false;
        });  
        $validator->addExtension('signature', function ($attribute, $value, $parameters, $validator) {
            $request = $validator->getData();
            $api_secret = Setting::where('key','api_secret')->first();
            if(isset($request['DepartmentID'])){
                $enkrip = hash_hmac('sha256',$request['DepartmentID'],$api_secret->value??true);
                if($enkrip == $value){
                    return true; 
                }
            }
            return false;
        }); 
    }
    public function messages()
    {
        return [
            'cek' => 'Invalid DepartmentID',
            'signature' => 'Signature doesnt match',
            'api_key' => 'Api Key Invalid'
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
