<?php

namespace Modules\Employee\Http\Requests\Reimbursement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Create extends FormRequest
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
    public function rules()
	{
		return [
			'id_product_icount'		=> 'required',
			'date_reimbursement'		=> 'required|date_format:"Y-m-d"',
			'price'                         => 'required|integer',
                        'qty'                         => 'required|integer',
			'notes'                         => 'required',
			'attachment'                    => 'required|max:5000',
        ];
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
