<?php

namespace Modules\PortalPartner\Http\Requests\Promo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\PromoCampaign\Entities\PromoCampaign;
use App\Lib\MyHelper;

class DetailPromoCampaign extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_promo_campaign' => 'required|promo',
        ];
    }

     public function withValidator($validator)
    {
        
        $validator->addExtension('promo', function ($attribute, $value, $parameters, $validator) {
         $id = MyHelper::explodeSlug($value)[0]??'';
         $promo = PromoCampaign::where('id_promo_campaign', '=', $id)->first();
         if($promo){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'promo' => 'Data tidak ada',
            'required' => ':attribute harus diisi'
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
