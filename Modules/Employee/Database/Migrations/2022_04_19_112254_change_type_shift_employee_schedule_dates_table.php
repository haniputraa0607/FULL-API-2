<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeShiftEmployeeScheduleDatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `employee_schedule_dates` CHANGE `shift` `shift` VARCHAR(191) NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `employee_schedule_dates` CHANGE `shift` `shift` enum("Non Shift", "Morning", "Midldle", "Evening") NOT NULL');
    }
}
