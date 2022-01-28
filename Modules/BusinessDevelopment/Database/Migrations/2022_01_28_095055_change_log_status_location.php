<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeLogStatusLocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `locations` CHANGE `step_loc` `step_loc` ENUM("Survey Location","Input Data Location","On Follow Up","Finished Follow Up","Approved") default NULL;');
        DB::statement('ALTER TABLE `step_locations_logs` CHANGE `follow_up` `follow_up` ENUM("Survey Location","Input Data Location","Follow Up","Approved") default "Survey Location";');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `locations` CHANGE `step_loc` `step_loc` ENUM("On Follow Up","Finished Follow Up","Survey Location","Approved") default NULL;');
        DB::statement('ALTER TABLE `step_locations_logs` CHANGE `follow_up` `follow_up` ENUM("Follow Up","Survey Location","Approved") default "Follow Up";');
    }
}
