<?php

namespace Modules\Project\Entities;

use App\Http\Models\City;
use Illuminate\Database\Eloquent\Model;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;

class PurchaseSpk extends Model
{
    protected $primaryKey = 'id_purchase_spk';
    protected $table = 'purchase_spk';
    protected $fillable = [ 
        'id_project',
        'id_request_purchase',
        'id_business_partner',
        'id_branch',
        'value_detail',
        'created_at',
        'updated_at'
    ];
}