<?php

namespace Modules\PortalPartner\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletReport extends Model
{
    protected $table = 'outlet_reports';
    protected $primaryKey = 'id_outlet_report';

    protected $fillable = [
        'id_outlet',
        'date'
    ];
}
