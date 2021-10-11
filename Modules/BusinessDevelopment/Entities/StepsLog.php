<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class StepsLog extends Model
{
    protected $table = 'steps_logs';
	protected $primaryKey = "id_steps_log";

	protected $fillable = [
        'id_partner',
		'follow_up',
		'note',
        'attachment'
	];
}
