<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupInsentifRumus extends Model
{
	protected $table = 'hairstylist_group_insentif_rumus';
	protected $primaryKey = 'id_hairstylist_group_insentif_rumus';


	protected $fillable = [
		'id_hairstylist_group',
		'id_hairstylist_group_insentif',
                'created_at',   
                'updated_at'
	];
}
