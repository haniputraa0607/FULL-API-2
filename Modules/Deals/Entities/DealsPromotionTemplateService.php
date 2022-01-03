<?php

namespace Modules\Deals\Entities;

use Illuminate\Database\Eloquent\Model;

class DealsPromotionTemplateService extends Model
{
    public $timestamps = false;
    protected $fillable = [
    	'service',
    	'id_deals'
    ];

    public function deals_promotion_template()
	{
        return $this->belongsTo(\App\Http\Models\DealsPromotionTemplate::class, 'id_deals', 'id_deals_promotion_template');
	}
}
