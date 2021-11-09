<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCutOffDocument extends Model
{
    protected $table = 'outlet_cut_off_document';
	protected $primaryKey = "id_outlet_cut_off_document";

	protected $fillable = [
                'id_outlet_cut_off',
		'title',
		'note',
		'attachment',
                'created_at',
                'updated_at' 
	];
}
