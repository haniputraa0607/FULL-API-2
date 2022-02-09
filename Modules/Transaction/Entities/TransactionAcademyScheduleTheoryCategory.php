<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionAcademyScheduleTheoryCategory extends Model
{
    protected $table = 'transaction_academy_schedule_theory_categories';

    protected $primaryKey = 'id_transaction_academy_schedule_theory_category';

    protected $fillable   = [
        'id_theory_category',
        'id_transaction_academy',
        'conclusion_score'
    ];
}
