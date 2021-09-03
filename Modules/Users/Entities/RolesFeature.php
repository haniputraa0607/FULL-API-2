<?php

namespace Modules\Users\Entities;

use Illuminate\Database\Eloquent\Model;

class RolesFeature extends Model
{
	protected $primaryKey = 'id_roles_feature';

	protected $fillable = [
		'id_role',
		'id_feature'
	];
}
