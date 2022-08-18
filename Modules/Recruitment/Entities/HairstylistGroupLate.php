<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupLate extends Model
{
	protected $table = 'hairstylist_group_lates';
	protected $primaryKey = 'id_hairstylist_group_late';


	protected $fillable = [
		'id_hairstylist_group',
		'id_hairstylist_group_default_late',
		'value',
                'created_at',   
                'updated_at'
	];
}
