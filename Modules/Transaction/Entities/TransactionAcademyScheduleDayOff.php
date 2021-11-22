<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionAcademyScheduleDayOff extends Model
{
    protected $table = 'transaction_academy_schedule_day_off';

    protected $primaryKey = 'id_transaction_academy_schedule_day_off';

    protected $fillable   = [
        'id_transaction_academy',
        'id_transaction_academy_schedule',
        'schedule_date_old',
        'schedule_date_new',
        'description',
        'approve_by',
        'approve_date',
        'reject_by',
        'reject_date'
    ];
}
