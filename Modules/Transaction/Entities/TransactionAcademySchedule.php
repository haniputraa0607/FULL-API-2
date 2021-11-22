<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionAcademySchedule extends Model
{
    protected $table = 'transaction_academy_schedules';

    protected $primaryKey = 'id_transaction_academy_schedules';

    protected $fillable   = [
        'id_transaction_academy',
        'id_user',
        'transaction_academy_schedule_status',
        'meeting',
        'schedule_date',
        'change_schedule',
        'count_change_schedule'
    ];
}
