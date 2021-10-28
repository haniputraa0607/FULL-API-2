<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class PartnersCloseTemporaryDocument extends Model
{
    protected $table = 'partners_close_temporary_document';
	protected $primaryKey = "id_partners_close_temporary_document";

	protected $fillable = [
                'id_partners_close_temporary',
		'title',
		'note',
		'attachment',
                'created_at',
                'updated_at' 
	];
}
