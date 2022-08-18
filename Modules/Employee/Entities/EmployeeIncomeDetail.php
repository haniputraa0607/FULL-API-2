<?php

namespace Modules\Employee\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use DateInterval;
use DatePeriod;
use DateTime;
use DB;
use Illuminate\Database\Eloquent\Model;
use App\Lib\Icount;

class EmployeeIncomeDetail extends Model
{
    public $primaryKey  = 'id_employee_income_detail';
    protected $table = 'employee_income_details';
    protected $fillable = [
        'id_employee_income',
        'source',
        'reference',
        'id_outlet',
        'amount',
        'name_income',
        'type'
    ];
}