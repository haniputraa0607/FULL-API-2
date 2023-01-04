<?php

namespace Modules\Employee\Http\Requests\AssetInventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\CategoryAssetInventory;
use Modules\Employee\Entities\AssetInventory;
use Modules\Employee\Entities\AssetInventoryLog;
use DB;
use Illuminate\Support\Facades\Auth;

class CreateLoan extends FormRequest
{
    public function withValidator($validator)
    {
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
        $asset = AssetInventory::leftjoin('asset_inventory_loans','asset_inventory_loans.id_asset_inventory','asset_inventorys.id_asset_inventory')
                ->leftjoin('asset_inventory_logs','asset_inventory_logs.id_asset_inventory','asset_inventorys.id_asset_inventory')
                ->where([
                    'asset_inventorys.id_asset_inventory'=>$value
                ])->select([
                    'asset_inventorys.id_asset_inventory',
                    'asset_inventorys.name_asset_inventory',
                    'asset_inventorys.code',
                    'asset_inventorys.id_asset_inventory_category',
                    'asset_inventorys.qty',
                    'asset_inventorys.available',
                ])
                ->groupby('id_asset_inventory')
                ->first();
                if($asset->available > 0){
                   return true;
                }
                return false;
        }); 
        $validator->addExtension('cek_request', function ($attribute, $value, $parameters, $validator) {
        $asset = AssetInventory::leftjoin('asset_inventory_loans','asset_inventory_loans.id_asset_inventory','asset_inventorys.id_asset_inventory')
                ->leftjoin('asset_inventory_logs','asset_inventory_logs.id_asset_inventory','asset_inventorys.id_asset_inventory')
                ->where([
                    'asset_inventorys.id_asset_inventory'=>$value,
                    'asset_inventory_logs.status_asset_inventory'=>"Pending",
                    'asset_inventory_logs.type_asset_inventory'=>"Loan",
                    'asset_inventory_logs.id_user'=> Auth::user()->id
                ])
                ->first();
                if(!$asset){
                   return true;
                }
                return false;
        }); 
    }
    public function messages()
    {
        return [
            'cek' => 'Asset Inventory not available',
            'cek_request' => 'Request asset inventory status still pending',
        ];
    }
    public function authorize()
    {
        return true;
    }
    public function rules()
	{
		return [
			'id_asset_inventory'          => 'required|cek|cek_request',
			'long'                        => 'required|integer',
			'long_loan'                   => 'required|in:Day,Month,Year',
			'notes'                       => 'required',
			'attachment'                  => 'mimes:jpeg,jpg,bmp,png|max:2000',
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
