<?php

namespace Modules\PortalPartner\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletPortalReport extends Model
{
    protected $table = 'outlet_portal_reports';
    protected $primaryKey = 'id_outlet_portal_report';
   
    protected $fillable = [
        'id_outlet',
        'date',
        'jumlah',
        'revenue',
        'grand_total',
        'diskon',
        'tax',
        'mdr',
        'net_sales',
        'net_sales_mdr',
        'count_hs',
        'refund_product',
    ];
}
