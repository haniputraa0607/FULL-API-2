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
		'id_outlet_manage',
		'date',
		'status',
		'jenis',
		'title',
                'created_at',
                'updated_at' 
	];
        public function outlet_step(){
        return $this->hasMany(OutletCloseTemporaryStep::class, 'id_outlet_close_temporary');
        }
        public function outlet_confirmation(){
            return $this->hasMany(OutletCloseTemporaryConfirmationLetter::class, 'id_outlet_close_temporary');
        }
        public function outlet_survey(){
            return $this->hasMany(OutletCloseTemporaryFormSurvey::class, 'id_outlet_close_temporary');
        }
        public function lampiran(){
            return $this->hasMany(OutletCloseTemporaryDocument::class, 'id_outlet_close_temporary');
        }
} 
