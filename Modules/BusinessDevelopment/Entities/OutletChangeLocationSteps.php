<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletChangeLocationSteps extends Model
{
        protected $table = 'outlet_change_location_steps';
	protected $primaryKey = "id_outlet_change_location_steps";

	protected $fillable = [
                  'id_outlet_change_location',
                'follow_up',
                'note',
                'attachment',
                'created_at',
                'updated_at' 
	];
} 
