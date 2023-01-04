<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStatusEmployeeTimeOff extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `employee_time_off` CHANGE `status` `status` ENUM("Pending","Manager Approved", "HRGA Approved") default "Pending";');
        DB::statement('ALTER TABLE `employee_overtime` CHANGE `status` `status` ENUM("Pending","Manager Approved", "HRGA Approved") default "Pending";');
        DB::statement('ALTER TABLE `employee_time_off_documents` CHANGE `type` `type` ENUM("Manager Approved", "HRGA Approved") default "Manager Approved";');
        DB::statement('ALTER TABLE `employee_overtime_documents` CHANGE `type` `type` ENUM("Manager Approved", "HRGA Approved") default "Manager Approved";');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

       DB::statement('ALTER TABLE `employee_time_off` CHANGE `status` `status` ENUM("Pending","Manager Approved", "Director Approved", "HRGA Appporved", "Fat Dept Approved", "Approved") default "Pending";');
       DB::statement('ALTER TABLE `employee_overtime` CHANGE `status` `status` ENUM("Pending","Manager Approved", "Director Approved", "HRGA Appporved", "Fat Dept Approved", "Approved") default "Pending";');
       DB::statement('ALTER TABLE `employee_time_off_documents` CHANGE `type` `type` ENUM("Manager Approved", "Director Approved", "HRGA Appporved", "Fat Dept Approved", "Approved") default "Manager Approved";');
       DB::statement('ALTER TABLE `employee_overtime_documents` CHANGE `type` `type` ENUM("Manager Approved", "Director Approved", "HRGA Appporved", "Fat Dept Approved", "Approved") default "Manager Approved";');

    }
}
