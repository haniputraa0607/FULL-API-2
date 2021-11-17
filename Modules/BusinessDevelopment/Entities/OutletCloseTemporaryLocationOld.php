<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCloseTemporaryLocationOld extends Model
{
        protected $table = 'outlet_close_temporary_location_old';
	protected $primaryKey = "id_outlet_close_temporary_location_old";

	protected $fillable = [
                'id_outlet_close_temporary',
		'id_city',
		'id_location',
		'name',
		'mall',
		'address',
		'longitude',
		'latitude',
		'id_brand',
		'location_large',
		'rental_price',
		'service_charge',
		'promotion_levy',
		'renovation_cost',
		'partnership_fee',
		'start_date',
		'end_date',
		'notes',
		'income',
                'created_at',
                'updated_at' 
	];
}
