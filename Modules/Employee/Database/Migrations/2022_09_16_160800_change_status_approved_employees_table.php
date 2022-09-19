<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStatusApprovedEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `employees` CHANGE `status_approved` `status_approved` ENUM("Submitted","Interview Invitation","Interview Result","Psikotest","HRGA","Contract","Approved","Probation Value","Success") NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `employees` CHANGE `status_approved` `status_approved` ENUM("Submitted","Interview Invitation","Interview Result","Psikotest","HRGA","Contract","Approved","Success") NULL;');
    }
}
