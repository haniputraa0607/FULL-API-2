<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistLoanIcount extends Model
{
    protected $table = 'hairstylist_loan_return_icounts';
	protected $primaryKey = 'id_hairstylist_loan_return_icount';

	protected $fillable = [
        'id_hairstylist_loan_return',
        'SalesPaymentID',
        'SalesInvoiceID',
        'BusinessPartnerID',
        'CompanyID',
        'BranchID',
        'VoucherNo',
        'value_detail',
        'created_at',   
        'updated_at'
	];
}
