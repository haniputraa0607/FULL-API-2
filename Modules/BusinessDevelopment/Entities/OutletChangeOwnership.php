<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletChangeOwnership extends Model
{
        protected $table = 'outlet_change_ownership';
	protected $primaryKey = "id_outlet_change_ownership";

	protected $fillable = [
                'id_partner',
		'id_outlet',
                'to_id_partner',
		'note',
		'date',
		'status',
		'title',
                'created_at',
                'updated_at' 
	];
        public function lampiran(){
            return $this->hasMany(OutletChangeOwnershipDocument::class, 'id_outlet_change_ownership');
        }
        public function outlet(){
            return $this->belongsto(Outlet::class, 'id_outlet');
        }
        public function to_id_partner(){
            return $this->belongsto(Partner::class, 'to_id_partner');
        }
}
