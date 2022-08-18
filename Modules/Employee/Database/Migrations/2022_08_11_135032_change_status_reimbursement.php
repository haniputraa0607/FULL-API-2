<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStatusReimbursement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       DB::statement('ALTER TABLE `employee_reimbursements` CHANGE `status` `status` ENUM("Pending","Manager Approved","Director Approved","HRGA Approved","Fat Dept Approved","Approved","Successed","Rejected") default "Pending";');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      DB::statement('ALTER TABLE `employee_reimbursements` CHANGE `status` `status` ENUM("Pending","Approved","Successed","Rejected") default "Pending";');
    }
}
