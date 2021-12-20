<?php

namespace Modules\PromoCampaign\Entities;

use Illuminate\Database\Eloquent\Model;

class PromoCampaignService extends Model
{
    public $timestamps = false;
    protected $fillable = [
    	'service',
    	'id_promo_campaign'
    ];

    public function promo_campaign()
	{
        return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'id_promo_campaign', 'id_promo_campaign');
	}
}
