<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistLoan extends Model
{
    protected $table = 'hairstylist_loans';
	protected $primaryKey = 'id_hairstylist_loan';

	protected $fillable = [
        'id_user_hair_stylist',
        'id_hairstylist_category_loan',
        'effective_date',
        'amount',
        'installment',
        'type',
        'notes',
        'status_loan',
        'id_hairstylist_sales_payment',
        'created_at',   
        'updated_at'
	];
        public function loan(){
            return $this->hasMany(HairstylistLoanReturn::class, 'id_hairstylist_loan');
        }
}
