<?php

namespace Modules\ChartOfAccount\Entities;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
        protected $table = 'chart_of_account';
	protected $primaryKey = "id_chart_of_account";

	protected $fillable = [
                'ChartOfAccountID',
		'CompanyID',
		'GroupAccountID',
		'AccountNo',
		'Description',
		'ParentID',
		'IsChildest',
		'IsSuspended',
		'IsBank',
		'Type',
		'IsDeleted',
		'is_actived',
                'created_at',
                'updated_at' 
	];
   
}
