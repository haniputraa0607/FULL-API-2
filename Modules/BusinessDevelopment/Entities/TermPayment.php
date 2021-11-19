<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class TermPayment extends Model
{
    protected $table = 'term_of_payments';
	protected $primaryKey = "id_term_of_payment";

	protected $fillable = [
        'id_company',
		'name',
		'duration',
        'is_deleted'
	];
}
