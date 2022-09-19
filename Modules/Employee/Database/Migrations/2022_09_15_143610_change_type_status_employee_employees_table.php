<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeStatusEmployeeEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `employees` CHANGE `status_employee` `status_employee` ENUM("Contract","Permanent","Probation") NULL;');



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `employees` CHANGE `status_employee` `status_employee` TINYINT(1) NULL;');

    }
}
