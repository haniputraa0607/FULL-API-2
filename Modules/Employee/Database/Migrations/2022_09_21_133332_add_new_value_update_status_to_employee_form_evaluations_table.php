<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewValueUpdateStatusToEmployeeFormEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `employee_form_evaluations` CHANGE `update_status` `update_status` ENUM("Permanent","Terminated","Extension","Not Change") NOT NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `employee_form_evaluations` CHANGE `update_status` `update_status` ENUM("Permanent","Terminated","Extension") NOT NULL;');
    }
}
