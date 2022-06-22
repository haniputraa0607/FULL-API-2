<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataReimbursementDateDisburse extends Migration
{
     public function up()
    {
        Schema::table('employee_reimbursements', function (Blueprint $table) {
          
            $table->datetime('date_disburse')->nullable();
        });
    }
    public function down()
    {
        Schema::table('employee_reimbursements', function (Blueprint $table) {
            $table->dropColumn('date_disburse')->nullable();
        });
    }
}
