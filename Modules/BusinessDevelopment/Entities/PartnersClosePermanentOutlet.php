<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class PartnersClosePermanentOutlet extends Model
{
    protected $table = 'partners_close_permanent_outlet';
	protected $primaryKey = "id_partners_close_permanent_outlet";

	protected $fillable = [
        'id_partners_close_permanent',
        'id_outlet',
        'created_at',
        'updated_at' 
	];
}
