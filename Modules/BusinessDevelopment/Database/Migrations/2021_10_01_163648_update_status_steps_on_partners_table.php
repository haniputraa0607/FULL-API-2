<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStatusStepsOnPartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `partners` CHANGE `status_steps` `status_steps` ENUM("On Follow Up","Finished Follow Up","Survey Location","Calculation","Confirmation Letter","Payment") NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `partners` CHANGE `status_steps` `status_steps` ENUM("Follow Up","Survey Location","Calculation","Confirmation Letter","Payment") NULL;');
    }
}
