<?php

namespace Modules\Setting\Http\Requests\Holiday;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class HolidayStore extends FormRequest
{
    public function rules()
    {
        return [
            'id_outlet.*'        => 'required|integer',
            'day.*.day'              => 'required|date',
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
