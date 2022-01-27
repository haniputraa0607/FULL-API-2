<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletChangeLocationConfirmationLetter extends Model
{
        protected $table = 'outlet_change_location_confirmation_letter';
	protected $primaryKey = "id_outlet_change_location_confirmation_letter";

	protected $fillable = [
                'id_outlet_change_location',
		'no_letter',
		'location',
		'date',
		'attachment',
                'created_at',
                'updated_at' 
	];
} 
