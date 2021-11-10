<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletChangeOwnershipDocument extends Model
{
    protected $table = 'outlet_change_ownership_document';
	protected $primaryKey = "id_outlet_change_ownership_document";

	protected $fillable = [
                'id_outlet_change_ownership',
		'title',
		'note',
		'attachment',
                'created_at',
                'updated_at' 
	];
}
