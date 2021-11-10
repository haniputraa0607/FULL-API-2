<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class PartnersCloseTemporary extends Model
{
    protected $table = 'partners_close_temporary';
	protected $primaryKey = "id_partners_close_temporary";

	protected $fillable = [
                'id_partner',
		'title',
		'note',
		'close_date',
		'start_date',
		'status',
                'created_at',
                'updated_at' 
	];
        public function partner(){
            return $this->belongsTo(Partner::class, 'id_partner');
        }
        public function lampiran(){
            return $this->hasMany(PartnersCloseTemporaryDocument::class, 'id_partners_close_temporary');
        }
}
