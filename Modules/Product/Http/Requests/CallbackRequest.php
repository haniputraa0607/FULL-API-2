<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CallbackRequest extends FormRequest
{
    public function rules()
    {
        return [
            'status'            => 'required|string|in:Approve,Reject',
            'PurchaseRequestID' => [
                'required',
                'string',
                Rule::exists('request_products', 'id_purchase_request')->where(function ($query) {
                    $query->where('status', '<>', 'Completed By Finance');
                }),
            ],
        ]; 
    }
 
    public function authorize()
    {
        return true;
    }

    protected function validationData()
    {
        return $this->all();
    }
}
