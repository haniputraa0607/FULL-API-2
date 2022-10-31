<?php

namespace Modules\Employee\Http\Requests\AssetInventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\CategoryAssetInventory;
use Modules\Employee\Entities\AssetInventory;
use Modules\Employee\Entities\AssetInventoryLog;
use Modules\Employee\Entities\AssetInventoryLoan;
use DB;
class CreateReturn extends FormRequest
{
    public function withValidator($validator)
    {
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
        $asset = AssetInventoryLoan::where('id_asset_inventory_loan',$value)->where('status_loan','Active')->first();
        if(!$asset){
                   return false;
                }
        $asset = AssetInventoryLog::where('id_asset_inventory',$asset->id_asset_inventory)
                ->orderby('created_at','desc')
                ->first();
            if($asset){
               if($asset->status_asset_inventory == "Pending" && $asset->type_asset_inventory == "Return"){
                   return false;
               }
            }
            return true;
        }); 
        
    }
    public function messages()
    {
        return [
            'cek' => 'Loan not available or Loan in request return'
        ];
    }
    public function authorize()
    {
        return true;
    }
    public function rules()
	{
		return [
			'id_asset_inventory_loan'     => 'required|cek',
			'notes'                       => 'required',
			'attachment'                  => 'required|max:5000',
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
