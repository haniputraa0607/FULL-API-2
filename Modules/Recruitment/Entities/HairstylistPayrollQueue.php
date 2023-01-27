<?php

namespace Modules\Recruitment\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

class HairstylistPayrollQueue extends Eloquent
{
        
	CONST STATUS_EXPORT_RUNNING = 'Running';
	CONST STATUS_EXPORT_READY = 'Ready';

	protected $table = 'hairstylist_payroll_queues';
	protected $primaryKey = 'id_hairstylist_payroll_queue';

	protected $fillable = [
		'month',
		'year',
		'message',
		'type',
		'status_export',
	];
}
