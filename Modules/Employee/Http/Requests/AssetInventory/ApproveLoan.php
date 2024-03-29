<?php

namespace Modules\Employee\Http\Requests\AssetInventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\CategoryAssetInventory;
use Modules\Employee\Entities\AssetInventory;
use DB;
class ApproveLoan extends FormRequest
{
    public function withValidator($validator)
    {
       $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
        $asset = AssetInventory::leftjoin('asset_inventory_loans','asset_inventory_loans.id_asset_inventory','asset_inventorys.id_asset_inventory')
                ->leftjoin('asset_inventory_logs','asset_inventory_logs.id_asset_inventory','asset_inventorys.id_asset_inventory')
                ->where([
                    'asset_inventory_logs.id_asset_inventory_log'=>$value
                ])->select([
                    'asset_inventorys.id_asset_inventory',
                    'asset_inventorys.name_asset_inventory',
                    'asset_inventorys.code',
                    'asset_inventorys.id_asset_inventory_category',
                    'asset_inventory_logs.status_asset_inventory',
                    'asset_inventorys.qty',
                    DB::raw('
                        sum(
                            CASE WHEN
                            asset_inventory_logs.type_asset_inventory = "Loan" AND asset_inventory_loans.status_loan = "Active" THEN 1 ELSE 0
                            END
                        ) as jumlah
                    ')
                ])
                ->groupby('id_asset_inventory')
                ->first();
                if($asset->qty>$asset->jumlah &&$asset->status_asset_inventory == "Pending"){
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
//			'notes' => 'required',
//			'attachment' => "required",
//			'attachment' => "requiredmimes:jpeg,jpg,bmp,png|max:2000",
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
