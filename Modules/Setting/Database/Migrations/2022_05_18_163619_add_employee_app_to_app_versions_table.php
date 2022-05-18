<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEmployeeAppToAppVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_versions', function (Blueprint $table) {
            DB::statement("ALTER TABLE app_versions CHANGE COLUMN app_type app_type ENUM('Android', 'IOS', 'OutletApp', 'MitraApp', 'WebApp', 'MitraAppIOS', 'EmployeeAndroid', 'EmployeeIOS') NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_versions', function (Blueprint $table) {
            DB::statement("ALTER TABLE app_versions CHANGE COLUMN app_type app_type ENUM('Android', 'IOS', 'OutletApp', 'MitraApp', 'WebApp', 'MitraAppIOS') NOT NULL");
        });
    }
}
