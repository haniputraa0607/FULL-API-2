<?php

namespace Modules\Project\Entities;

use App\Http\Models\City;
use Illuminate\Database\Eloquent\Model;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;

class InvoiceBap extends Model
{
    protected $primaryKey = 'id_invoice_bap';
    protected $table = 'invoice_bap';
    protected $fillable = [ 
        'id_project',
        'id_sales_invoice',
        'id_business_partner',
        'id_branch',
        'dpp',
        'dpp_tax',
        'tax',
        'tax_value',
        'tax_date',
        'netto',
        'amount',
        'outstanding',
        'value_detail',
        'created_at',
        'updated_at'
    ];
}