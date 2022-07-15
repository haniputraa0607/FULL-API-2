<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistSalesPayment extends Model
{
    protected $table = 'hairstylist_sales_payments';
	protected $primaryKey = 'id_hairstylist_sales_payment';

	protected $fillable = [
         'BusinessPartnerID',
        'SalesInvoiceID',
        'amount',
        'type',
        'status',
        'created_at',   
        'updated_at'
	];
}
