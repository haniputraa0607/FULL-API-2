<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletManage extends Model
{
        protected $table = 'outlet_manage';
	protected $primaryKey = "id_outlet_manage";

	protected $fillable = [
                'id_partner',
		'id_outlet',
		'date',
		'type',
		'status',
                'created_at',
                'updated_at' 
	];
}
