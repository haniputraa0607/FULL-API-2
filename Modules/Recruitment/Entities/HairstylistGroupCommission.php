<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupCommission extends Model
{
	protected $table = 'hairstylist_group_commissions';
	protected $primaryKey = 'id_hairstylist_group_commission';


	protected $fillable = [
		'id_hairstylist_group',
		'id_product',
		'percent',
		'commission_percent',
        'created_at',   
        'updated_at',
		'dynamic'
	];

	public function dynamic_rule(){
        return $this->hasMany(Modules\Recruitment\Entities\HairstylistGroupCommissionDynamic::class, 'id_hairstylist_group_commission');
    }
}
