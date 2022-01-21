<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class NewStepsLog extends Model
{
    protected $table = 'new_steps_logs';
	protected $primaryKey = "id_new_steps_log";

	protected $fillable = [
        'index',
        'id_partner',
        'id_location',
		'follow_up',
		'note',
        'attachment'
	];
}
