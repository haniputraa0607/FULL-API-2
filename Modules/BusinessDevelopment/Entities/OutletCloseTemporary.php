<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCloseTemporary extends Model
{
        protected $table = 'outlet_close_temporary';
	protected $primaryKey = "id_outlet_close_temporary";

	protected $fillable = [
                'id_partner',
		'id_outlet',
		'note',
		'date',
		'status',
		'jenis',
		'title',
                'created_at',
                'updated_at' 
	];
        public function lampiran(){
            return $this->hasMany(OutletCloseTemporaryDocument::class, 'id_outlet_close_temporary');
        }
}
