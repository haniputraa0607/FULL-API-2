<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class PartnersBecomesIxoboxOutlet extends Model
{
    protected $table = 'partners_becomes_ixobox_outlet';
	protected $primaryKey = "id_partners_becomes_ixobox_outlet";

	protected $fillable = [
        'id_partners_becomes_ixobox',
        'id_outlet',
        'created_at',
        'updated_at' 
	];
}
