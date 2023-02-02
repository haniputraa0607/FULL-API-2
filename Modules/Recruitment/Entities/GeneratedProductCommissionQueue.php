<?php

namespace Modules\Recruitment\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

class GeneratedProductCommissionQueue extends Eloquent
{
        
	CONST STATUS_EXPORT_RUNNING = 'Running';
	CONST STATUS_EXPORT_READY = 'Ready';

	protected $table = 'hairstylist_generated_product_commission_queues';
	protected $primaryKey = 'id_hairstylist_generated_product_commission_queue';

	protected $fillable = [
		'start_date',
		'end_date',
		'status',
	];
}
