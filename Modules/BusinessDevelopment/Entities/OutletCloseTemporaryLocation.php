<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCloseTemporaryLocation extends Model
{
        protected $table = 'outlet_close_temporary_location';
	protected $primaryKey = "id_outlet_close_temporary_location";

	protected $fillable = [
                'id_outlet_close_temporary',
		'from_id_city',
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
