<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class CategoryAssetInventory extends Model
{
    protected $table = 'asset_inventory_categorys';

    protected $primaryKey = 'id_asset_inventory_category';
    
    protected $fillable = [
        'name_category_asset_inventory',
        'created_at',
        'updated_at',
    ];
   
}
