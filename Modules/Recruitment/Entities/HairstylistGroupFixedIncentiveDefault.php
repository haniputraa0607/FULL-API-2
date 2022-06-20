<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupFixedIncentiveDefault extends Model
{
	protected $table = 'hairstylist_group_default_fixed_incentives';
	protected $primaryKey = 'id_hairstylist_group_default_fixed_incentive';


	protected $fillable = [
        'name_fixed_incentive',
        'status',
        'type',
        'formula',
        'created_at',   
        'updated_at'
	];
        public function detail(){
            return $this->hasMany(HairstylistGroupFixedIncentiveDetailDefault::class, 'id_hairstylist_group_default_fixed_incentive')->orderBy('range','desc');
        }
}
