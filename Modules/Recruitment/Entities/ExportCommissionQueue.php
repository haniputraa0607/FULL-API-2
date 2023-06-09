<?php

namespace Modules\Recruitment\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

class ExportCommissionQueue extends Eloquent
{
        
	CONST STATUS_EXPORT_RUNNING = 'Running';
	CONST STATUS_EXPORT_READY = 'Ready';
	CONST STATUS_EXPORT_DELETED = 'Deleted';

	protected $table = 'export_commission_queues';
	protected $primaryKey = 'id_export_commission_queue';

	protected $fillable = [
		'id_outlet',
		'name_outlet',
		'start_date',
		'end_date',
		'url_export',
		'status_export'
	];
}
