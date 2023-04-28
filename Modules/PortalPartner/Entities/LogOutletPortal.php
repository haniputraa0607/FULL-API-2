<?php

namespace Modules\PortalPartner\Entities;

use Illuminate\Database\Eloquent\Model;

class LogOutletPortal extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'log_outlet_portals';
    protected $primaryKey = 'id_log_outlet_portal';

    protected $fillable = [
        'id_outlet',
        'error',
    ];
}
