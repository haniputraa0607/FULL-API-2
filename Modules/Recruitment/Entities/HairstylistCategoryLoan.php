<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistCategoryLoan extends Model
{
    protected $table = 'hairstylist_category_loans';
	protected $primaryKey = 'id_hairstylist_category_loan';

	protected $fillable = [
        'name_category_loan',
        'created_at',   
        'updated_at'
	];
}
