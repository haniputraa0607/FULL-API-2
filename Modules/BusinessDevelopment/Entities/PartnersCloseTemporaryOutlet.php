<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class PartnersCloseTemporaryOutlet extends Model
{
    protected $table = 'partners_close_temporary_outlet';
	protected $primaryKey = "id_partners_close_temporary_outlet";

	protected $fillable = [
                'id_partners_close_temporary',
                'id_outlet',
                'created_at',
                'updated_at' 
	];
}
