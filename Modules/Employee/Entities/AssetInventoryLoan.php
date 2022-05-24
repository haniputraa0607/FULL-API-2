<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class AssetInventoryLoan extends Model
{
    protected $table = 'asset_inventory_loans';

    protected $primaryKey = 'id_asset_inventory_loan';
    
    protected $fillable = [
        'id_asset_inventory_log',
        'id_asset_inventory',
        'status_loan',
        'start_date_loan',
        'end_date_loan',
        'qty_loan',
        'long',
        'long_loan',
        'notes',
        'attachment',
        'created_at',
        'updated_at',
    ];
   
}