<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class AssetInventory extends Model
{
    protected $table = 'asset_inventorys';

    protected $primaryKey = 'id_asset_inventory';
    
    protected $fillable = [
        'id_asset_inventory_category',
        'name_asset_inventory',
        'code',
        'qty',
        'created_at',
        'updated_at',
    ];
   
}
