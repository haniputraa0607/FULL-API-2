<?php

namespace Modules\Employee\Http\Requests\AssetInventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\CategoryAssetInventory;
use Modules\Employee\Entities\AssetInventory;
use DB;
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
                    DB::raw('
                        sum(
                            CASE WHEN
                            asset_inventory_logs.type_asset_inventory = "Loan" AND asset_inventory_loans.status_loan = "Active" OR asset_inventory_logs.status_asset_inventory != "Rejected" THEN 1 ELSE 0
                            END
                        ) as jumlah
                    ')
                ])
                ->groupby('id_asset_inventory')
                ->first();
                if($asset->qty>$asset->jumlah){
                   return true;
                }
                return false;
        }); 
    }
    public function messages()
    {
        return [
            'cek' => 'Asset Inventory not available',
        ];
    }
    public function authorize()
    {
        return true;
    }
    public function rules()
	{
		return [
			'id_asset_inventory'          => 'required|cek',
			'long'                        => 'required|integer',
			'long_loan'                   => 'required|in:Day,Month,Year',
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
