<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupPotonganRumus extends Model
{
	protected $table = 'hairstylist_group_potongan_rumus';
	protected $primaryKey = 'id_hairstylist_group_potongan_rumus';


	protected $fillable = [
		'id_hairstylist_group',
		'id_hairstylist_group_potongan',
                'created_at',   
                'updated_at'
	];
}
