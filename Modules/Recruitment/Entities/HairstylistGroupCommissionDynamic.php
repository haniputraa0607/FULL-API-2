<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupCommissionDynamic extends Model
{
    protected $table = 'hairstylist_group_commission_dynamics';
	protected $primaryKey = 'id_hairstylist_group_commission_dynamic';


	protected $fillable = [
		'id_hairstylist_group_commission',
		'operator',
		'qty',
		'value'
	];
}
