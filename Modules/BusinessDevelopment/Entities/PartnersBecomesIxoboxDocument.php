<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class PartnersBecomesIxoboxDocument extends Model
{
    protected $table = 'partners_becomes_ixobox_document';
	protected $primaryKey = "id_partners_becomes_ixobox_document";

	protected $fillable = [
        'id_partners_becomes_ixobox',
		'title',
		'note',
		'attachment',
        'created_at',
        'updated_at' 
	];
}
