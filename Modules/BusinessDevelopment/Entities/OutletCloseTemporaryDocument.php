<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCloseTemporaryDocument extends Model
{
    protected $table = 'outlet_close_temporary_document';
	protected $primaryKey = "id_outlet_close_temporary_document";

	protected $fillable = [
                'id_outlet_close_temporary',
		'title',
		'note',
		'attachment',
                'created_at',
                'updated_at' 
	];
}
