<?php

namespace Modules\PortalPartner\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletReportQueueJob extends Model
{
    protected $table = 'outlet_report_queue_jobs';
    protected $primaryKey = 'id_outlet_report_queue_job';

    protected $fillable = [
        'id_outlet',
        'start_date',
        'end_date',
        'status',
    ];
}
