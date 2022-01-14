<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnStepsLogAndPartnerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `partners` CHANGE `status_steps` `status_steps` ENUM("On Follow Up","Finished Follow Up","Input Data Partner","Survey Location","Finished Survey Location","Select Location","Confirmation Letter","Payment") default NULL;');
        DB::statement('ALTER TABLE `steps_logs` CHANGE `follow_up` `follow_up` ENUM("Follow Up","Input Data Partner","Survey Location","Select Location","Confirmation Letter","Payment") default NULL;');
        Schema::table('locations', function (Blueprint $table) {
            $table->integer('width')->nullable()->after('submited_by');
            $table->integer('height')->nullable()->after('width');
            $table->string('location_type')->nullable()->after('pic_contact');
            $table->string('location_image')->nullable()->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `partners` CHANGE `status_steps` `status_steps` ENUM("On Follow Up","Finished Follow Up","Survey Location","Calculation","Confirmation Letter","Payment") default NULL;');
        DB::statement('ALTER TABLE `steps_logs` CHANGE `follow_up` `follow_up` ENUM("Follow Up","Survey Location","Calculation","Confirmation Letter","Payment") default NULL;');
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('width');
            $table->dropColumn('height');
            $table->dropColumn('location_type');
            $table->dropColumn('location_image');
        });
    }
}
