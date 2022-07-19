<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupFixedIncentive extends Model
{
	protected $table = 'hairstylist_group_fixed_incentives';
	protected $primaryKey = 'id_hairstylist_group_fixed_incentive';


	protected $fillable = [
        'id_hairstylist_group_default_fixed_incentive_detail',
        'id_hairstylist_group',
        'value',
        'created_at',   
        'updated_at'
	];
}
