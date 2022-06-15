<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupFixedIncentiveDetailDefault extends Model
{
	protected $table = 'hairstylist_group_default_fixed_incentive_details';
	protected $primaryKey = 'id_hairstylist_group_default_fixed_incentive_detail';


	protected $fillable = [
        'id_hairstylist_group_default_fixed_incentive',
        'range',
        'value',
        'created_at',   
        'updated_at'
	];
}
