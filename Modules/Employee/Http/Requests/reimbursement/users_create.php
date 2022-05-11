<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class users_create extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
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
			'employee.phone'		=> 'required|unique:users,phone|max:18',
			'employee.name'			=> 'required|string',
			'employee.email'		=> 'required|email|unique:users,email',
			'employee.gender'		=> 'in:Male,Female|nullable',
			'employee.birthday'		=> 'date_format:"Y-m-d"|nullable',
			'employee.id_city_ktp'		=> 'integer',
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
