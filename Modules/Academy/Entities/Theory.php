<?php

namespace Modules\Academy\Entities;

use Illuminate\Database\Eloquent\Model;

class Theory extends Model
{
    protected $table = 'theories';

    protected $primaryKey = 'id_theory';
    
    protected $fillable = [
        'id_theory_category',
        'theory_title',
        'minimum_score'
    ];
}
