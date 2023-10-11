<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use App\Http\Models\User;

class update_pin extends FormRequest
{
    public function withValidator($validator)
    {
        $validator->addExtension('old_password', function ($attribute, $value, $parameters, $validator) {
         if(Hash::check($value, auth()->user()->password)){
             return true;
         }
         return false;
        }); 

    }
    public function messages()
    {
        return [
            'old_password' => 'Pin lama tidak sama',
            'same' => 'Pin baru tidak sama',
        ];
    }
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
	public function rules()
	{
		return [
                'old_password' => 'required|old_password',
                'new_password' => 'required|integer',
                'new_confirm_password' => 'same:new_password',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['status' => 'fail', 'messages'  => $validator->errors()->all()], 200));
    }

    protected function validationData()
    {
        return $this->json()->all();
    }
}
