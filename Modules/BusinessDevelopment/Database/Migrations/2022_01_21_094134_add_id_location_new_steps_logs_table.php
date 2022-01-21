<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdLocationNewStepsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('new_steps_logs', function (Blueprint $table) {
            $table->integer('id_location')->after('id_partner');
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->integer('length')->after('height');
            $table->enum('company_type',["PT IMA","PT IMS"])->after('location_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('new_steps_logs', function (Blueprint $table) {
            $table->dropColumn('id_location');
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('length');
            $table->dropColumn('company_type',["PT IMA","PT IMS"]);
        });
    }
}
