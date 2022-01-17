<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletStartetToLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->bigInteger('id_outlet_starter_bundling')->nullable()->after('id_partner');
        });
        DB::statement('ALTER TABLE `partners` CHANGE `status_steps` `status_steps` ENUM("On Follow Up","Finished Follow Up","Input Data Partner","Survey Location","Finished Survey Location","Select Location","Calculation","Confirmation Letter","Payment") default NULL;');
        DB::statement('ALTER TABLE `steps_logs` CHANGE `follow_up` `follow_up` ENUM("Follow Up","Input Data Partner","Survey Location","Select Location","Calculation","Confirmation Letter","Payment") default NULL;');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('id_outlet_starter_bundling');
        });
        DB::statement('ALTER TABLE `partners` CHANGE `status_steps` `status_steps` ENUM("On Follow Up","Finished Follow Up","Input Data Partner","Survey Location","Finished Survey Location","Select Location","Confirmation Letter","Payment") default NULL;');
        DB::statement('ALTER TABLE `steps_logs` CHANGE `follow_up` `follow_up` ENUM("Follow Up","Input Data Partner","Survey Location","Select Location","Confirmation Letter","Payment") default NULL;');
    }
}
