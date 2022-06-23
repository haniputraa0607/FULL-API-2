<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistGroupProteksi extends Model
{
	protected $primaryKey = 'id_hairstylist_group_proteksi';
	protected $table = 'hairstylist_group_proteksi';

	protected $fillable = [
		'id_hairstylist_group',
		'value',
                'created_at',   
                'updated_at'
	];

}
