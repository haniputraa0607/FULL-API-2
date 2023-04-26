<?php

namespace Modules\PortalPartner\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletReportJob extends Model
{
    protected $table = 'outlet_report_jobs';
    protected $primaryKey = 'id_outlet_report_job';

    protected $fillable = [
        'date',
        'status_export',
    ];
}
