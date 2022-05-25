<?php

namespace Modules\Employee\Http\Requests\AssetInventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\CategoryAssetInventory;
use Modules\Employee\Entities\AssetInventory;
use Modules\Employee\Entities\AssetInventoryLoan;
use Modules\Employee\Entities\AssetInventoryReturn;
use DB;
class ApproveReturn extends FormRequest
{
    public function withValidator($validator)
    {
       $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
        $asset = AssetInventoryReturn::join('asset_inventory_loans','asset_inventory_loans.id_asset_inventory_loan','asset_inventory_returns.id_asset_inventory_loan')
                ->where('asset_inventory_returns.id_asset_inventory_log',$value)->count();
                if($asset != 0){
                   return true;
                }
                return false;
        }); 

    }
    public function messages()
    {
        return [
            'cek' => 'Asset Inventory not available or the Loan has been processed',
        ];
    }
    public function authorize()
    {
        return true;
    }
    public function rules()
	{
		return [
			'id_asset_inventory_log' => 'required|cek',
			'status_asset_inventory' => 'required|in:Approved,Rejected',
			'notes' => 'required',
			'attachment' => 'required|max:5000',
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
