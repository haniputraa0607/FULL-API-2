<?php

namespace Modules\Outlet\Http\Requests\Holiday;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class HolidayUpdate extends FormRequest
{
    public function rules()
    {
        return [
            'id_holiday'            => 'required|integer',
            'id_outlet.*'           => 'required|integer',
            'date.*.date'           => 'required|date',
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
        return $this->json()->all();
    }
}
