<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCloseTemporaryStep extends Model
{
        protected $table = 'outlet_close_temporary_steps';
	protected $primaryKey = "id_outlet_close_temporary_step";

	protected $fillable = [
        'id_outlet_close_temporary',
        'follow_up',
	'note',
        'attachment'
	];
}
