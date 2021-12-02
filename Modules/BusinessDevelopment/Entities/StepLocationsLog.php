<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class StepLocationsLog extends Model
{
    protected $table = 'step_locations_logs';
	protected $primaryKey = "id_step_locations_log";

	protected $fillable = [
        'id_location',
		'follow_up',
		'note',
        'attachment'
	];
}
