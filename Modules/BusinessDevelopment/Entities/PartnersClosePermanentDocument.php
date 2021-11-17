<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class PartnersClosePermanentDocument extends Model
{
    protected $table = 'partners_close_permanent_document';
	protected $primaryKey = "id_partners_close_permanent_document";

	protected $fillable = [
        'id_partners_close_permanent',
		'title',
		'note',
		'attachment',
        'created_at',
        'updated_at' 
	];
}
