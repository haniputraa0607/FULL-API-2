<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class InitBranch extends Model
{
    protected $table = 'init_branchs';
	protected $primaryKey = "id_init_branch";
    protected $fillable = [
        'id_partner',
        'id_location',
        'id_sales_order',
        'id_company',
        'no_voucher',
        'amount',
        'tax',
        'taxt_value',
        'netto',
        'id_sales_order_detail',
        'id_item',
        'qty',
        'unit',
        'ratio',
        'unit_ratio',
        'price',
        'detail_name',
        'disc',
        'disc_value',
        'disc_rp',
        'description',
        'outstanding',
        'item'
    ];
}
