<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class AssetInventoryLog extends Model
{
    protected $table = 'asset_inventory_logs';

    protected $primaryKey = 'id_asset_inventory_log';
    
    protected $fillable = [
        'id_asset_inventory',
        'id_user',
        'id_approved',
        'status_asset_inventory',
        'type_asset_inventory',
        'notes',
        'attachment',
        'qty_logs',
        'date_action',
        'created_at',
        'updated_at',
    ];
   public function loan()
    {
            return $this->hasOne(\Modules\Employee\Entities\AssetInventoryLoan::class, 'id_asset_inventory_log');
    }
   public function user()
    {
            return $this->hasOne(\App\Http\Models\User::class,'id','id_user');
    }
   public function approve()
    {
            return $this->hasOne(\App\Http\Models\User::class,'id','id_approved');
    }
    
}