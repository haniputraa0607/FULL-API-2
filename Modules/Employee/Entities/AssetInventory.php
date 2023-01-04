<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class AssetInventory extends Model
{
    protected $table = 'asset_inventorys';

    protected $primaryKey = 'id_asset_inventory_log';
    
    protected $fillable = [
        'id_asset_inventory_category',
        'name_asset_inventory',
        'code',
        'qty',
        'available',
        'created_at',
        'updated_at',
    ];
   
}
