<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Outlet;
use App\Lib\Icount;

class EmployeeSalesPayment extends Model
{
    protected $table = 'employee_sales_payments';

    protected $primaryKey = 'id_employee_sales_payment';
    
    protected $fillable = [
        'BusinessPartnerID',
        'SalesInvoiceID',
        'amount',
        'status',
        'created_at',
        'updated_at',
    ];
    
}
