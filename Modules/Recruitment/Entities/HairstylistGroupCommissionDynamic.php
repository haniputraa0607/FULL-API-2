<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Recruitment\Entities\HairstylistGroupCommission;

class HairstylistGroupCommissionDynamic extends Model
{
    protected $table = 'hairstylist_group_commission_dynamics';
	protected $primaryKey = 'id_hairstylist_group_commission_dynamic';
	public $timestamps = false;

	protected $fillable = [
		'id_hairstylist_group_commission',
		'operator',
		'qty',
		'value'
	];

    public function default(){
        return $this->belongsTo(HairstylistGroupCommission::class, 'id_hairstylist_group_commission');
    }
}
