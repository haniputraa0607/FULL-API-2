<?php

namespace Modules\Recruitment\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

class ExportPayrollQueue extends Eloquent
{
        
	CONST STATUS_EXPORT_RUNNING = 'Running';
	CONST STATUS_EXPORT_READY = 'Ready';
	CONST STATUS_EXPORT_DELETED = 'Deleted';

	protected $table = 'export_payroll_queues';
	protected $primaryKey = 'id_export_payroll_queue';

	protected $fillable = [
		'id_outlet',
		'name_outlet',
		'start_date',
		'end_date',
		'url_export',
		'status_export',
                'type_export'
	];
}
