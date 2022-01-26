<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletChangeLocation extends Model
{
        protected $table = 'outlet_change_location';
	protected $primaryKey = "id_outlet_change_location";

	protected $fillable = [
                'id_partner',
                'id_outlet_manage',
		'to_id_outlet',
		'id_outlet',
		'id_location',
                'to_id_location',
		'date',
                'status',
		'status_steps',
                'created_at',
                'updated_at' 
	];
         public function steps(){
        return $this->hasMany(OutletChangeLocationSteps::class, 'id_outlet_change_location');
        }
        public function first_location()
        {
            return $this->belongsTo(Location::class, 'to_id_location');
        }
         public function confirmation(){
             return $this->hasMany(OutletChangeLocationConfirmationLetter::class, 'id_outlet_change_location');
        }
} 
