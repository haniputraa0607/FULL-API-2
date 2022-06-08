<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeTimeOffCreate extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "type"          => "required|string",
            "date"          => "required|date_format:Y-m-d",
            "notes"          => "required|string",
            "attachment.*"  => "mimes:jpeg,jpg,bmp,png,pdf|max:2000"
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
