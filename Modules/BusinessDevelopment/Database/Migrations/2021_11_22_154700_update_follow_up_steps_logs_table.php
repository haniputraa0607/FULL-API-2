<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateFollowUpStepsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `steps_logs` CHANGE `follow_up` `follow_up` ENUM("Follow Up","Survey Location","Calculation","Confirmation Letter","Payment") default "Follow Up";');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `steps_logs` CHANGE `follow_up` `follow_up` ENUM("Follow Up 1","Follow Up 2","Follow Up 3","Follow Up 4","Follow Up 5","Follow Up 6","Approved","Survey Location","Calculation","Confirmation Letter","Payment") default "Follow Up 1";');
    }
}
