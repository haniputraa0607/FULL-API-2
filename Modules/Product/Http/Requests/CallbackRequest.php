<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Product\Entities\RequestProduct;
use App\Http\Models\Setting;

class CallbackRequest extends FormRequest
{
    public function rules()
    {
        return [
            'PurchaseInvoiceID'    => 'required|cek',
            'status'               => 'required|status', 
            'api_key'              => 'required|api_key',
            'signature'            => 'required|signature'
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
            $share = RequestProduct::where('id_purchase_request', $value)->where('status', '<>', 'Completed By Finance')->first();
            if($share){
                return true;
            }
            return false;
        });
        $validator->addExtension('api_key', function ($attribute, $value, $parameters, $validator) {
            $api_secret = Setting::where('key','api_key')->where('value',$value)->first();
            if($api_secret){
                return true; 
            } 
            return false;
        }); 
        $validator->addExtension('status', function ($attribute, $value, $parameters, $validator) {
            if($value == 'Approve'|| $value == 'Reject'){
                return true; 
            } 
            return false;
        }); 
        $validator->addExtension('signature', function ($attribute, $value, $parameters, $validator) {
            $request = $validator->getData();
            $api_secret = Setting::where('key','api_secret')->first();
            if(isset($request['PurchaseInvoiceID'])&&isset($request['status'])){
                $enkrip = hash_hmac('sha256',$request['PurchaseInvoiceID'].$request['status'],$api_secret->value??true);
                if($enkrip == $value){
                    return true; 
                }
            }
            return false;
        }); 
    }
    public function messages()
    {
        return [
            'cek' => 'Invalid PurchaseInvoiceID or Request Product status has been completed',
            'status' => "Invalid status, status must be Approve or Reject",
            'signature' => 'Signature doesnt match',
            'api_key' => 'Api Key Invalid'
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
        return $this->all();
    }
}
