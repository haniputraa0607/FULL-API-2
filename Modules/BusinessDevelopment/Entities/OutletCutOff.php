<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCutOff extends Model
{
        protected $table = 'outlet_cut_off';
	protected $primaryKey = "id_outlet_cut_off";

	protected $fillable = [
                'id_partner',
		'id_outlet',
                'id_outlet_manage',
		'note',
		'date',
		'status',
		'title',
                'created_at',
                'updated_at' 
	];
        public function lampiran(){
            return $this->hasMany(OutletCutOffDocument::class, 'id_outlet_cut_off');
        }
        public function outlet(){
            return $this->belongsto(Outlet::class, 'id_outlet');
        }
}
