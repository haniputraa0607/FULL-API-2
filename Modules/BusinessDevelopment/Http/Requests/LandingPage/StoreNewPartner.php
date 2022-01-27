<?php

namespace Modules\BusinessDevelopment\Http\Requests\LandingPage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreNewPartner extends FormRequest
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
            "title"   => 'required|string',
            "name"   => 'required|string',
            "address"   => 'required|string',
            "contact_person"   => 'required|string',
            "phone"   => 'required|numeric',
            "mobile"   => 'required|numeric',
            "email"   => 'required|email',
            "notes"   => 'required|string',
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