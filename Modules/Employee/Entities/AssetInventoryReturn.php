<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class AssetInventoryReturn extends Model
{
    protected $table = 'asset_inventory_returns';

    protected $primaryKey = 'id_asset_inventory_return';
    
    protected $fillable = [
        'id_asset_inventory_log',
        'id_asset_inventory',
        'id_asset_inventory_loan',
        'date_return',
        'notes',
        'attachment',
        'created_at',
        'updated_at',
    ];
   
}