<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionAcademyScheduleTheory extends Model
{
    protected $table = 'transaction_academy_schedule_theories';

    protected $primaryKey = 'id_transaction_academy_schedule_theory';

    protected $fillable   = [
        'id_transaction_academy_schedule',
        'id_transaction_academy_schedule_theory_category',
        'id_theory',
        'theory_title',
        'score'
    ];
}
