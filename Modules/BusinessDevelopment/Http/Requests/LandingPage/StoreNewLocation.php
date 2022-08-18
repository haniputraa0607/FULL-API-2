<?php

namespace Modules\BusinessDevelopment\Http\Requests\LandingPage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreNewLocation extends FormRequest
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
                "name"   => 'required|string',
                "email"   => 'required|email',
                "address"   => 'required|string',
                "id_city"   => 'required|integer',
                "latitude"   => 'required',
                "longitude"   => 'required',
                "width"   => 'required|integer',
                "length"   => 'required|integer',
                "location_large"   => 'required|integer',
                "location_type"   => 'required|string',
                "pic_name"   => 'required|string',
                "pic_contact"   => 'required|numeric',
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
