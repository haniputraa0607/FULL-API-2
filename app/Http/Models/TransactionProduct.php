<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Product\Entities\ProductIcount;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
use App\Http\Models\TransactionPaymentMidtran;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use App\Lib\MyHelper;
use Modules\Disburse\Entities\MDR;
use Modules\Product\Entities\ProductCommissionDefault;

/**
 * Class TransactionProduct
 * 
 * @property int $id_transaction_product
 * @property int $id_transaction
 * @property int $id_product
 * @property int $transaction_product_qty
 * @property int $transaction_product_price
 * @property int $transaction_product_subtotal
 * @property string $transaction_product_note
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Product $product
 * @property \App\Http\Models\Transaction $transaction
 *
 * @package App\Models
 */
class TransactionProduct extends Model
{
    protected $primaryKey = 'id_transaction_product';

    protected $casts = [
        'id_transaction' => 'int',
        'id_product' => 'int',
        'transaction_product_qty' => 'int',
//      'transaction_product_price' => 'int',
        'transaction_product_subtotal' => 'int',
        'transaction_variant_subtotal' => 'double'
    ];

    protected $fillable = [
        'id_transaction',
        'id_product',
        'id_product_variant_group',
        'type',
        'id_outlet',
        'id_brand',
        'id_user',
        'id_user_hair_stylist',
        'transaction_product_qty',
        'transaction_product_bundling_qty',
        'transaction_product_price',
        'transaction_product_price_base',
        'transaction_product_price_tax',
        'transaction_product_subtotal',
        'transaction_product_net',
        'transaction_product_note',
        'transaction_product_discount',
        'transaction_product_discount_all',
        'transaction_product_base_discount',
        'transaction_product_qty_discount',
        'transaction_variant_subtotal',
        'id_transaction_bundling_product',
        'id_bundling_product',
        'transaction_product_bundling_price',
        'transaction_product_bundling_discount',
        'transaction_product_bundling_charged_outlet',
        'transaction_product_bundling_charged_central',
        'transaction_product_completed_at',
        'reject_at',
        'reject_reason',
        'mdr_product'
    ];
    
    public function modifiers()
    {
        return $this->hasMany(\App\Http\Models\TransactionProductModifier::class, 'id_transaction_product');
    }
    
    public function variants()
    {
        return $this->hasMany(\Modules\ProductVariant\Entities\TransactionProductVariant::class, 'id_transaction_product');
    }
    
    public function product()
    {
        return $this->belongsTo(\App\Http\Models\Product::class, 'id_product')
        ->orWhere('products.is_inactive', '1');
    }

    public function product_variant_group()
    {
        return $this->belongsTo(\Modules\ProductVariant\Entities\ProductVariantGroup::class, 'id_product_variant_group');
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
    }

    public function hairstylist()
    {
        return $this->belongsTo(\Modules\Recruitment\Entities\UserHairStylist::class, 'id_user_hair_stylist');
    }
    
     public function getUserAttribute() {
        $user = $this->transaction->user;
        return $user;
    }

    public function getProductCategoryAttribute() {
        $category = $this->product->category;
        return $category;
    }

    public function getPhotoAttibute() {
        $photo = $this->product->photos;
        return $photo;
    }

    public function getCityAttribute() {
        $city = $this->transaction->user->city;
        return $city;
    }

    public function getProvinceAttibute() {
        $province = $this->transaction->user->city->province;
        return $province;
    }

    public function transaction_product_service()
    {
        return $this->hasOne(\Modules\Transaction\Entities\TransactionProductService::class, 'id_transaction_product');
    }

    public function transaction_product_promos()
    {
        return $this->hasMany(\Modules\Transaction\Entities\TransactionProductPromo::class, 'id_transaction_product');
    }

    public function transaction_breakdown()
    {
        return $this->hasMany(\Modules\Transaction\Entities\TransactionBreakdown::class, 'id_transaction_product');
    }

    public function breakdown(){
        $id_product = $this->id_product;
        $id_transaction = $this->id_transaction;
        $trx_product_subtotal = $this->transaction_product_subtotal;
        $trans_grandtotal = $this->transaction->transaction_grandtotal;
        $subtotal_grandtotal = $trans_grandtotal ? $trx_product_subtotal/$trans_grandtotal : 0;
        $product_uses = $this->product->product_icount_use;
        $total_material = 0;
        foreach($product_uses ?? [] as $key => $product_use){
            $detail_product_use[$key] = ProductIcount::where('id_product_icount',$product_use['id_product_icount'])->first();
            if($product_use['unit']==$detail_product_use[$key]['unit1']){
                $total_use[$key] = $product_use['qty']*$detail_product_use[$key]['unit_price_1'];
            }
            if($product_use['unit']==$detail_product_use[$key]['unit2']){
                $total_use[$key] = $product_use['qty']*$detail_product_use[$key]['unit_price_2'];
            }
            if($product_use['unit']==$detail_product_use[$key]['unit3']){
                $total_use[$key] = $product_use['qty']*$detail_product_use[$key]['unit_price_3'];
            }
            $total_material = $total_use[$key] + $total_material;
        }
        $material = [
            "id_transaction_product" => $this->id_transaction_product,
            "type"                   => 'material',
            "value"                  => $total_material
        ];
        $send = $this->transaction_breakdown()->updateOrCreate(["type" => $material['type']],["value"=> $material['value']]);
        if($send){
            $hair_stylist = $this->hairstylist;
            if (!$hair_stylist) {
                $hair_stylist = optional($this->transaction_product_service)->user_hair_stylist;
            }
            $id_group_hs =  $hair_stylist['id_hairstylist_group'];
            $sub_total = $this->transaction_product_subtotal;
            $group = HairstylistGroupCommission::where('id_hairstylist_group',$id_group_hs)->where('id_product',$id_product)->first() ?? [];
            $fee_hs = [
                "id_transaction_product" => $this->id_transaction_product,
                "type"                   => 'fee_hs',
            ];
            if($group){
                if($group['percent']==0){
                    $fee_hs['value'] = $group['commission_percent'];
                }else{
                    $fee_hs['value'] = ($group['commission_percent']/100) * $sub_total;
                }
                
            }else{
                $defaultCommission = ProductCommissionDefault::where('id_product', $id_product)->first();
                if ($defaultCommission) {
                    if($defaultCommission['percent']==0){
                        $fee_hs['value'] = $defaultCommission['commission'];
                    }else{
                        $fee_hs['value'] = ($defaultCommission['commission']/100) * $sub_total;
                    }
                } else {
                    $defaultGlobal = Setting::where('key','global_commission_product')->first();
                    if (!$defaultGlobal) {
                        $fee_hs['value'] = '';
                    } else {
                        if($defaultGlobal['value']==0){
                            $fee_hs['value'] = $defaultGlobal['value_text'];
                        }else{
                            $fee_hs['value'] = ($defaultGlobal['value_text']/100) * $sub_total;
                        }
                    }
                }
            }
            $send = $this->transaction_breakdown()->updateOrCreate(["type" => $fee_hs['type']],["value"=> $fee_hs['value']]);
            if($send){
                $payment = TransactionPaymentMidtran::where('id_transaction',$id_transaction)->first();
                if($payment){
                    $method = 'Midtran';
                    $payment = ucfirst(strtolower($payment['payment_type']));
                }else{
                    $payment = TransactionPaymentXendit::where('id_transaction',$id_transaction)->first();
                    if($payment){
                        $method = 'Xendit';
                        $payment = ucfirst(strtolower($payment['type']));
                    }else{
                        $method = null;
                        $payment = null;
                    }
                }
                $availablePayment = config('payment_method');
                $setting  = json_decode(MyHelper::setting('active_payment_methods', 'value_text', '[]'), true) ?? [];
                foreach($setting as $s => $set){
                    $availablePayment[$set['code']]['method'] = $set['code'] ?? false;
                }
                $payment_method = '';
                foreach($availablePayment as $av_pay){
                    if($av_pay['payment_gateway'] == $method && $av_pay['payment_method'] == $payment){
                        $payment_method = $av_pay['method'];
                    }
                }$fee_payment = [
                    "id_transaction_product" => $this->id_transaction_product,
                    "type"                   => 'fee_payment',
                ];
                $mdr = MDR::where('payment_name',$payment_method)->first();
                if($mdr['percent_type']=='Nominal'){
                    $fee_payment['value'] = $mdr['mdr'] * $subtotal_grandtotal;
                }elseif($mdr['percent_type']=='Percent'){
                    $fee_payment['value'] = (($mdr['mdr']/100)*$trans_grandtotal) * $subtotal_grandtotal;
                }else{
                    $fee_payment['value'] = null;
                }
                
                if($fee_payment['value']){
                    $send = $this->transaction_breakdown()->updateOrCreate(["type" => $fee_payment['type']],["value"=> $fee_payment['value']]);
                    if($send){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    
}
