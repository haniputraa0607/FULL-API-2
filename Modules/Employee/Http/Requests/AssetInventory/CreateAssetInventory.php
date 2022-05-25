<?php

namespace Modules\Employee\Http\Requests\AssetInventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\CategoryAssetInventory;

class CreateAssetInventory extends FormRequest
{
    public function withValidator($validator)
    {
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
         $survey = CategoryAssetInventory::where(array('id_asset_inventory_category'=>$value))->count();
         if($survey != 0){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'cek' => 'Category not found',
        ];
    }
    public function authorize()
    {
        return true;
    }
    public function rules()
	{
		return [
			'id_asset_inventory_category' => 'required|cek',
			'name_asset_inventory' => 'required',
			'qty' => 'required|integer',
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
