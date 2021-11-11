<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class PartnersBecomesIxobox extends Model
{
    protected $table = 'partners_becomes_ixobox';
	protected $primaryKey = "id_partners_becomes_ixobox";
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
        return $this->hasMany(PartnersBecomesIxoboxDocument::class, 'id_partners_becomes_ixobox');
    }
}
