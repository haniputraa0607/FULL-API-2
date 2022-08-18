<?php

namespace Modules\Employee\Http\Requests\Income;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use App\Http\Models\User;

class Password extends FormRequest
{
    public function withValidator($validator)
    {
        $validator->addExtension('password', function ($attribute, $value, $parameters, $validator) {
         if(Hash::check($value, auth()->user()->password)){
             return true;
         }
         return false;
        }); 

    }
    public function messages()
    {
        return [
            'password' => ':attribute not match ',
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
			'password' => 'required|password',
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
